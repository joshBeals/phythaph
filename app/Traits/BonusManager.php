<?php

namespace App\Traits;

use App\Classes\Helper;
use App\Exceptions\CannotUpdateBonusesException;
use App\Exceptions\GreaterThanTenorException;
use App\Exceptions\NotAllowedBonusIntervalException;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

trait BonusManager
{

    protected $bonusSchedule;
    protected $bonusAmount;
    protected $bonusInterval;

    // In months
    static $allowedInvestmentBonusFrequency = [
        1, 2, 3, 6, 12,
    ];

    public function bonuses()
    {
        return $this->hasMany($this->getBonusClass())->orderBy('nth_payment', 'ASC');
    }

    public function paidBonuses()
    {
        return $this->hasMany($this->getBonusClass())->whereNotNull('remitted_at')->orderBy('nth_payment', 'ASC');
    }

    public function unpaidBonuses()
    {
        return $this->hasMany($this->getBonusClass())->whereNull('remitted_at')->orderBy('nth_payment', 'ASC');
    }

    /**
     * This is the class that is used for the
     * relationship for storing bonuses.
     *
     * If is expected that the class has some common fields
     *
     */
    protected function getBonusClass(): string
    {
        return \App\Models\UserInvestmentBonus::class;
    }

    protected function getBonusClassRelationshipField(): string
    {
        return 'user_investment_id';
    }

    protected function getStartDate(): Carbon
    {
        // User investment by default
        return $this->invested_at;
    }

    protected function getEndDate(): Carbon
    {
        // User investment by default
        return $this->mature_at;
    }

    protected function getTotal(): \double
    {
        // User investment by default
        return $this->total_investment;
    }

    protected function getTotalInterest(): float
    {
        // User investment by default
        return $this->maturity_interest;
    }

    /**
     * Get the total that has been remitted to the customer
     *
     * @return float
     */
    public function getTotalRemittedBonus(): ?float
    {
        $paid = $this->paidBonuses()->get();
        return !$paid->count() ? 0 : $paid->sum('amount');
    }

    /**
     * Get the total that has been remitted to the customer
     *
     * @return float
     */
    public function getTotalUnpaidBonus(): ?float
    {
        $unpaid = $this->unpaidBonuses()->get();
        return !$unpaid->count() ? 0 : $unpaid->sum('amount');
    }

    /**
     * Check if this entity bonus can be updated
     *
     * @return bool
     */
    public function canUpdateBonus(): bool
    {
        $first = $this->bonuses()->first();
        $paidBonuses = $this->paidBonuses()->get();
        return !($paidBonuses->count() || ($first && $first->mature_at->lt(now())));
    }

    /**
     * Compute the bonuses for this entity
     *
     * @param int $interval    The interval of bonus
     *
     * @return array
     */
    public function computeBonuses(int $interval = 3): array
    {

        if (!in_array($interval, static::$allowedInvestmentBonusFrequency)) {
            throw new NotAllowedBonusIntervalException($interval . " months");
        }

        $bonuses = [];
        $start = $this->getStartDate()->copy();
        $end = $this->getEndDate()->copy();
        $firstDate = $start->copy()->addMonths($interval);

        if ($firstDate->gt($end)) {
            throw new GreaterThanTenorException($interval . " months");
        }

        // Do some computation
        $this->decorate();

        $totalMonths = $start->diffInMonths($end);
        $interests = $this->getTotalInterest();

        if (!$interests || !$totalMonths) {
            return $bonuses;
        }

        $totalMonths = $totalMonths / $interval;
        $split = $interests / $totalMonths;

        $this->bonusAmount = $split;

        for ($int = 1; $int <= $totalMonths; $int++) {
            $bonuses[] = [
                'mature_at' => $start->copy()->addMonth($interval * $int),
                'amount' => $split,
                'nth_payment' => $int,
                'interval' => $interval,
            ];
        }

        return $bonuses;

    }

    /**
     * Create list of bonuses and store them in the bonus database
     *
     * @param int $interval
     *
     * @return bool
     */
    public function registerBonuses(int $interval = 3): Collection
    {
        if (!$this->canUpdateBonus()) {
            throw new CannotUpdateBonusesException;
        }

        $this->bonusInterval = $interval;

        // I wont mind the exception, should be caught by the consumer
        $bonuses = $this->computeBonuses($interval);

        // Delete old schedules
        $this->getBonusClass()::where(
            $this->getBonusClassRelationshipField(), $this->id
        )->delete();

        foreach ($bonuses as $b) {
            $this->getBonusClass()::create(array_merge($b, [
                'user_id' => $this->user_id,
                $this->getBonusClassRelationshipField() => $this->id,
            ]));
        }

        $this->bonusSchedule = $this->bonuses()->get();

        if ($this->bonusSchedule->count() && $this->bonusAmount) {
            $update = [
                'bonus_interval' => $interval,
                'bonus_interval_amount' => $this->bonusAmount,
            ];

            \DB::table($this->getTable())->where('id', $this->id)->update($update);

            // Set the bonus_interval for the object
            $this->bonus_interval = $interval;
            $this->bonus_interval_amount = $this->bonusAmount;
        }

        return $this->bonusSchedule;
    }

    /**
     * Remit the nth number of a bonus
     *
     * @param int $nth              The index of the bonus to remit
     * @param Carbon $remitDate     The remit date
     *
     * @return Model $bonus
     */
    public function remitBonus(int $nth, Carbon $remitDate = null): ?Model
    {

        if ($nth > 1) {
            $previous = $this->getBonusClass()::where(
                $this->getBonusClassRelationshipField(), $this->id
            )->where('nth_payment', $nth - 1)->whereNull('remitted_at')->first();

            if ($previous) {
                throw new \Exception(
                    "Cannot remit this bonus schedule as there are previous bonuses that is yet to be remitted. Remit the previous bonus first"
                );
            }

        }

        $bonus = $this->getBonusClass()::where(
            $this->getBonusClassRelationshipField(), $this->id
        )->where('nth_payment', $nth)->first();

        if (!$bonus) {
            throw new \Exception(
                "The specified bonus schedule is not found, please try again"
            );

        }

        if ($bonus->remitted_at) {
            throw new \Exception(
                "The specified bonus schedule is already remitted."
            );

        }

        $txn = Transaction::initialize(floatval($bonus->amount), (Object) [
            'scope' => 'investment_bonus_remittance',
            'type' => 'investment_bonus_remittance',
            'user_id' => $this->user_id,
            'investment_id' => $this->id,
            'bonus' => $bonus,
        ]);

        // Might throw exception
        $this->onRemitBonus($bonus, $txn);

        $txn->status = "success";
        $txn->entity_id = $bonus->id;
        $txn->type = 'investment_bonus_remittance';
        $txn->save();

        $bonus->remitted_at = $remitDate ?? now();
        $bonus->transaction_id = $txn->id;
        $bonus->save();
        return $bonus;

    }

    protected function onRemitBonus(Model $bonus, Transaction $transaction)
    {
        return $bonus->amount;
    }

}
