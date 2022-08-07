<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Exceptions\InsufficientFundException;
use App\Exceptions\MinimumWithdrawalException;
use App\Exceptions\TopupNotAllowedException;
use App\Models\Savings\UserSavingBalanceHistory;
use App\Models\Transaction as TransactionHistory;
use App\Models\User;
use App\Models\UserBank as UserBank;
use App\Models\UserPayoutRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait SavingManager
{
    use NairaKobo;

    static $ANNUM_DAYS = 365;
    static $PRINCIPAL_FIELD = 'principal';
    static $default_rate = 10;
    static $MIN_WITHDRAWABLE_AMOUNT = 500;
    static $WITHDRAW_PENALTY_PERCENTAGE = 2.5;

    /**
     * Get the user of the saving without adding it to the object
     */
    public function getUser(): User
    {
        return $this->user()->first();
    }

    /**
     * Calculate savings interest and all values
     *
     * @return Self
     */
    public function calculate(): Self
    {
        // Dont compute on account that is completed or on_hold
        if ($this->isOnHold() || $this->isCompleted()) {

            if ($this->completed_at) {
                $this->days_run = $this->commenced_at->diffInDays($this->completed_at);

            }

            return $this;
        }

        $tenor = $this->getTenorDays(); //In days
        $rate = $this->rate;
        $principal = $this->toNaira($this->principal);
        // $this->days_run = Helper::dateDiff($this->commenced_at);
        $this->tenor_in_days = $tenor;
        $this->days_run = $this->commenced_at->diffInDays(null);

        $this->interest_days = Helper::dateDiff($this->interest_initial_date ?? $this->commenced_at);

        $this->is_mature = false;

        $this->maturity_interest = null;
        $this->maturity_value = null;
        $this->today_interest = null;
        $this->today_value = null;

        $this->today_interest = $this->interest_days === 0 ? 0 : round(($principal * ($rate) * ($this->interest_days / Self::$ANNUM_DAYS)) / 100, 2);

        $this->today_value = round($principal + $this->today_interest, 2);

        if ($tenor && !is_nan(intval($tenor))) {

            $this->maturity_interest = round(($principal * ($rate) * ($tenor / Self::$ANNUM_DAYS)) / 100, 2);
            $this->maturity_value = round($principal + $this->maturity_interest, 2);

            if ($this->days_run >= $tenor) {
                $this->today_interest = $this->maturity_interest;
                $this->today_value = $this->maturity_value;

                $this->is_mature = true;

                // $this->onMature();

            }

        }

        $this->next_withdrawal_date = null;
        $this->days_to_next_withdrawal = null;
        $this->can_withdraw = false;
        $this->can_topup = false;

        if (!$this->isCompleted()) {
            $this->next_withdrawal_date = $this->nextWithdrawalDate();
            $this->days_to_next_withdrawal = Helper::dateDiff($this->next_withdrawal_date, false);
            $this->days_to_next_withdrawal = $this->days_to_next_withdrawal < 0 ? 0 : $this->days_to_next_withdrawal;
            $this->can_withdraw = $this->canWithdraw();
            $this->can_topup = $this->canTopup($this->today_value);
            $this->is_tenorable = $this->isTenorable();
        }
        // $this->is_fully_mature = $this->isMature();

        return $this;
    }

    /**
     * Alias of calculate;
     *
     * @return Self
     */
    public function compute(): Self
    {
        return $this->calculate();
    }

    /**
     * Check if topup is allowed on this savings type
     *
     * @param float $accountBalance   The account balance to check against
     *
     * @return bool
     */
    public function canTopup(float $accountBalance = null): bool
    {

        $klass = $this->originalClass();
        if (defined("$klass::MAX_ACCOUNT_BALANCE")) {
            $maxAccountBalance = $klass::MAX_ACCOUNT_BALANCE;

            if ($accountBalance && $maxAccountBalance && ($accountBalance >= $maxAccountBalance)) {
                return false;
            }

        }

        if (defined("$klass::CAN_TOPUP")) {
            return $klass::CAN_TOPUP;
        }

        return true;

    }

    /**
     * Get this object from its original class making sure UserSaving is not
     * the value of static. This is useful to get the actual value of class constants
     * and class static values
     *
     * @return Self
     */
    protected function fromOriginalClass(): Self
    {
        $thisClass = get_class($this);
        // Bad shit, this will not give you real value;
        if ($thisClass === \App\Models\Savings\UserSaving::class) {
            $klass = "\\App\\Models\\Savings\\" . studly_case($this->type);
            $findSaving = $klass::findOrFail($this->id);
            return $findSaving;
        }
        return $this;
    }

    /**
     * Get the original class name of the saving object
     *
     * @return string
     */
    protected function originalClass(): string
    {
        return get_class($this->fromOriginalClass());
    }

    /**
     * check if this specific savings can be withdrawn
     * @param Carbon $date      An optional date to set can withdraw to
     *
     * @return bool
     */
    public function canWithdraw(Carbon $date = null): bool
    {
        $nextWithdrawDate = $this->nextWithdrawalDate();
        if ($date) {
            return $date->isSameDay($nextWithdrawDate);
        }

        // Can withdraw at anytime afterwards
        return $nextWithdrawDate && $nextWithdrawDate->lte(now());
    }

    /**
     * Check if is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    /**
     * Check if is active
     *
     * @return bool
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    /**
     * Check if is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if tenor is supported with this type of savings
     *
     * @return bool
     */
    public function isTenorable(): bool
    {
        $klass = $this->originalClass();
        return defined("$klass::TENORABLE") ? $klass::TENORABLE : true;
    }

    /**
     * Get the number of days withdrawl can be processed
     *
     * @return bool
     */
    public function getWithdrawalNotice(): ?string
    {
        $klass = $this->originalClass();
        return defined("$klass::WITHDRAWAL_NOTICE") ? $klass::WITHDRAWAL_NOTICE : 0;
    }

    /**
     * Check if the savings is mature
     *
     * @return bool
     */
    public function isMature(): bool
    {
        return (($this->mature_at && now()->gt($this->mature_at)) || $this->isCompleted()) || false;

    }

    /**
     * Check if the savings has matured,
     * mark it as completed and do the needful.
     *
     * @return Self
     */
    public function checkMaturity(): self
    {
        // Completed savings cannot mature
        // or have a next withdrawal date
        if ($this->isCompleted()) {
            return $this;
        }

        // Some saving such as Piggy Bank does not mature
        if (!$this->isTenorable()) {

            // Check if the next withdrawal date is today
            if ($this->canWithdraw()) {
                $this->onWithdrawalDay();
            }

            return $this;
        }

        if (!$this->isMature()) {
            return $this;
        }

        return $this->onMature();

    }

    /**
     * Update the model to avoid decoration issues
     *
     * @param array $data       The data to update
     *
     * @return Self
     */
    private function dbUpdateRecalculate(array $data, $calculate = true): self
    {
        DB::table($this->getTable())->where('id', $this->id)
            ->update($data);

        foreach ($data as $key => $d) {
            $this->{$key} = $d;
        }

        if ($calculate) {
            $this->refresh();
            $this->calculate();
        }

        return $this;

    }

    /**
     * Get the interest rate for this savings type
     *
     * @return float
     */
    public function getRate(): float
    {
        return $this->originalClass()::RATE ?? self::$default_rate;
    }

    /**
     * Get the tenor or minimum tenor for this savings
     *
     * @return string
     */
    public function getTenor(): ?string
    {

        if (!$this->isTenorable()) {
            return false;
        }

        $klass = $this->originalClass();

        if (defined("$klass::TENOR")) {
            return strval($klass::TENOR);
        }

        if (defined("$klass::MINIMUM_TENOR")) {
            return strval($klass::MINIMUM_TENOR);
        }

        return null;

    }

    /**
     * Get the number of days, from inception the tenor should be
     *
     * @return string
     */
    public function getTenorDays(): ?int
    {

        if (!$this->isActive()) {
            return null;
        }

        $tenor = $this->tenor ?? $this->getTenor() ?? null;

        if (!$tenor) {
            return null;
        }

        $startDate = $this->commenced_at ?? $this->interest_initial_date;

        if (!is_numeric($tenor)) {
            try {
                $tenorEnd = (clone ($startDate))->add($tenor);
                return $startDate->diffInDays($tenorEnd);

            } catch (\Throwable $th) {
                return null;
            }
        }

        return intval($tenor);

    }

    /**
     * Get the next withdrawal date for the specific saving
     *
     * @return Carbon
     */
    public function nextWithdrawalDate()
    {

        if (($this->isTenorable() && $this->next_withdrawal_date) || ($this->next_withdrawal_date && Helper::dateDiff($this->next_withdrawal_date, false) > 0)) {
            return $this->next_withdrawal_date;
        }

        return $this->computeNextWithdrawalDate();
    }

    /**
     * Compute the next withdrawal date and update
     * the date on the database
     *
     * @return Carbon
     */
    protected function computeNextWithdrawalDate(): Carbon
    {
        // For savings with fixed maturity date
        if ($this->mature_at) {
            $this->next_withdrawal_date = $this->mature_at;
        } else {
            // Not interest_initial_date becuase we want to know the
            // first time the saving was started
            $dateStarted = Helper::dateDiff($this->commenced_at);

            $klass = $this->originalClass();

            // For withdrawal Intervals
            if (defined("$klass::WITHDRAWAL_INTERVAL")) {
                $this->next_withdrawal_date = (clone (!$this->isTenorable() ? $this->commenced_at : $this->interest_initial_date ?? $this->commenced_at))->add($klass::WITHDRAWAL_INTERVAL);
            }

            // Minimum 30 days wait before first withdrawal
            else if ($dateStarted < 30) {
                // First 30 days
                $this->next_withdrawal_date = (clone ($this->commenced_at))->addDays(30);
            } else {
                // Can initiate withdrawal at anytime
                $this->next_withdrawal_date = now();
            }

        }

        $nextDate = $this->dbUpdateRecalculate([
            'next_withdrawal_date' => $this->next_withdrawal_date,
        ], false);
        return $this->next_withdrawal_date;
    }

    /**
     * If auto_save is enabled, compute the next topup date
     *
     * @param bool $update      Wheater to update after computing
     *
     * @return Carbon
     */
    public function nextAutoTopupDate(bool $update = true): ?Carbon
    {
        if (!$this->auto_save) {
            return null;
        }

        $nextDate = null;

        switch ($this->auto_save) {
            case 'daily':
                $nextDate = now()->addDay(1);
                break;
            case 'weekly':
                $nextDate = now()->addDay(7);
                break;
            case 'month_beginning':
                $nextDate = (new Carbon('first day of next month'))->addDay(4);
                break;
            case 'month_end':
                $nextDate = (new Carbon('first day of next month'))->addDay(26);
                break;
            case 'specific_day':
                if ((new Carbon('first day of this month'))->addDay($this->auto_save_specific_day - 1) < now()) {
                    if (now()->addMonth()->month == 2) {
                        if (now()->addMonth()->endOfMonth() < (new Carbon('first day of this month'))->addDay($this->auto_save_specific_day - 1)) {
                            $nextDate = now()->addMonth()->endOfMonth();
                        } else {
                            $nextDate = (new Carbon('first day of next month'))->addDay($this->auto_save_specific_day - 1);
                        }
                    } else {
                        $nextDate = (new Carbon('first day of next month'))->addDay($this->auto_save_specific_day - 1);
                    }
                } else {
                    if (now()->month == 2) {
                        if (now()->endOfMonth() < (new Carbon('first day of this month'))->addDay($this->auto_save_specific_day - 1)) {
                            $nextDate = now()->endOfMonth();
                        } else {
                            $nextDate = (new Carbon('first day of this month'))->addDay($this->auto_save_specific_day - 1);
                        }
                    } else {
                        $nextDate = (new Carbon('first day of this month'))->addDay($this->auto_save_specific_day - 1);
                    }
                }
                break;
        }

        if ($update) {
            $this->next_auto_save_date = $nextDate;
            $this->dbUpdateRecalculate([
                'next_auto_save_date' => $nextDate,
            ], false);
        }

        return $nextDate;

    }

    /**
     * Update the autosave of this saving and update the next autosave date
     *
     * @param float $amount     The amount to autosave
     * @param string $interval  The autosave interval
     *
     * @return Self
     */
    public function updateAutoSave(float $amount, string $interval = null, string $specific_day = null): self
    {
        $data = ['auto_save_amount' => $amount];

        if ($interval && $interval != $this->auto_save) {
            $data['auto_save'] = $interval;
        }

        if ($specific_day && $specific_day != $this->auto_save_specific_day && $specific_day != 0) {
            $data['auto_save_specific_day'] = $specific_day;
        } else {
            $data['auto_save_specific_day'] = null;
        }

        $this->dbUpdateRecalculate($data, false);
        $this->nextAutoTopupDate();

        return $this->calculate();

    }

    /**
     * Create a new saving, this method should be called from a derived class of UserSaving
     *
     * @param Object $detail            The details of the saving
     * @param TransactionHistory $txn   Optional transaction history object
     */
    public static function newSaving(
        Object $detail,
        TransactionHistory $txn = null
    ) {
        $user_id = $detail->user_id ?? null;

        if (!$user_id) {
            return null;
        }

        $saving = new static;

        $type = static::TYPE;
        $tenorable = $saving->isTenorable();
        $principal = doubleval($detail->principal);
        $title = $detail->title ?? null;
        if (!$title) {
            $title = Str::title(str_replace("_", "", $type)) . " Saving";
        }

        $saving->type = $type;
        $saving->user_id = $user_id;
        $saving->principal = $saving->toKobo($principal);
        $saving->rate = $saving->getRate();
        $saving->title = $title;
        $saving->description = $detail->description ?? null;

        $saving->commenced_at = isset($detail->commenced_at) && !empty($detail->commenced_at) ? Carbon::parse($detail->commenced_at) : now();
        $saving->interest_initial_date = clone ($saving->commenced_at);

        $saving->status = 'active';

        if ($tenorable) {

            $tenor = $detail->tenor ?? null;

            if (!$tenor) {
                $tenor = $saving->getTenor();
            }

            if ($tenor) {

                $saving->tenor = $tenor;
                $saving->mature_at = (clone ($saving->commenced_at))->{is_string($tenor) ? 'add' : 'addDays'}($saving->tenor);
            }
        }

        if (isset($detail->auto_save) && !empty($detail->auto_save) && $detail->auto_save !== 'never') {
            $saving->auto_save = $detail->auto_save;
            $saving->auto_save_amount = $principal;
            $saving->auto_save_specific_day = $detail->auto_save_specific_day ?? null;
        }

        $saving->save();
        $saving->onTopup($principal, $txn, null, $saving->commenced_at);

        $saving->computeNextWithdrawalDate();
        $saving->nextAutoTopupDate();

        return $saving;

    }

    /**
     * Topup on this saving
     *
     * @param float $amount             The amount to topup
     * @param TransactionHistory $txn   The transaction object
     * @param Carbon $date              The topup date which is the new commencement date
     * @param bool $autoSave            If the topup is from the auto save feature
     *
     * @throws TopupNotAllowedException
     * @return Self
     */
    public function topup(
        float $amount,
        TransactionHistory $txn,
        Carbon $date = null,
        bool $autoSave = false
    ): self {

        $this->calculate();

        if (!$this->canTopup($this->today_value ?? null)) {
            $klass = $this->originalClass();
            throw new TopupNotAllowedException(defined("$klass::TYPE") ? Self::TYPES[$klass::TYPE] : null);
        }

        // This calls calculate under the hood
        if ($this->isMature()) {
            return $this;
        }

        // Turn the new commencement date to today
        $newCommenced_at = $date ?? now();

        $newPrincipal = $this->toNaira($this->principal);

        // Add the new amount to the current principal
        $newPrincipal += $amount;

        // Add the current interest to the principal
        $newPrincipal += $this->today_interest;

        // Do this before manually updating the database
        $this->onTopup($amount, $txn, $newPrincipal, $newCommenced_at, $autoSave);

        // Save new values to the database
        $update = [
            'principal' => $this->toKobo($newPrincipal),
            'interest_initial_date' => $newCommenced_at,
        ];

        // If the account is formerlly on hold
        if ($this->isOnHold()) {
            $update['status'] = 'active';
        }

        $this->nextAutoTopupDate();
        return $this->dbUpdateRecalculate($update);

    }

    /**
     * Withdraw from this saving regardless of if the day is the
     * withdrawal date. This is only used for withdrawal when the
     * savings is still active.
     *
     * @param float $amount             The amount to topup
     * @param UserBank $bank            The bank to pay to
     * @param Carbon $date              The withdrawal date
     * @param string $note              Note on the disbursal
     *
     * @return Self
     */

    public function withdraw(float $amount = null, UserBank $bank = null, Carbon $date = null, string $note = null): self
    {

        if (!$this->isActive()) {
            return $this;
        }

        $this->compute();

        $canWithdraw = $this->canWithdraw($date);

        // No need to handle exceptions, exceptions will be passed on
        $computeWithdrawal = $this->calculateWithdrawal($amount, null, $date);

        unset($computeWithdrawal['penalty_rate']);

        // Needed later
        $amount = $computeWithdrawal['amount'];

        $user = $this->getUser();

        if (!$bank) {
            $bank = $user->bank;
        }

        // Log into payout request
        $payoutRequest = UserPayoutRequest::create(array_merge([
            'user_id' => $user->id,
            'entity_id' => $this->id,
            'note' => $note,
            'source' => 'savings',
            'bank_id' => $bank && $bank->id ? $bank->id : null,
        ], $computeWithdrawal));

        if ($date) {
            $payoutRequest->created_at = $date;
            $payoutRequest->save();
        }

        // Remove amount from principal
        $newPrincipal = $this->today_value - $amount;

        $updateData = [
            'principal' => $newPrincipal > 0 ? $this->toKobo($newPrincipal) : 0,
            'interest_initial_date' => $date ?? now(),
        ];

        // Only a non-tenorable account can be placed on hold
        if ($newPrincipal < 1) {
            if (!$this->isTenorable()) {
                $updateData['status'] = 'on_hold';
            } else {
                $updateData['status'] = 'completed';
                $this->completed(true);

            }
        }

        // Log to savings balance history
        $this->onWithdraw($amount, null, $newPrincipal, $date);

        // Then update the saving itself
        $this->dbUpdateRecalculate($updateData);

        return $this;

    }

    /**
     * Calculate penalty for a given amount.
     * This is a useful for external resource.
     *
     * @param float $amount             The amount to calculate
     * @param float $penaltyRate        Optional rate to use to calculate the penalty
     * @param Carbon $date              Optional date to check against
     */
    public function calculateWithdrawal(float $amount = null, float $penaltyRate = null, Carbon $date = null)
    {
        $this->compute();

        $canWithdraw = $this->canWithdraw($date);

        if (!$amount) {
            $amount = $this->today_value;
        }

        if (!$penaltyRate) {
            $penaltyRate = self::$WITHDRAW_PENALTY_PERCENTAGE;
        }

        if ($amount > $this->today_value) {
            throw new InsufficientFundException;
        }

        $toDisburse = $amount;

        $penalty = null;

        if (!$canWithdraw) {
            $penalty = ($penaltyRate / 100 * $amount);
            $toDisburse = $amount - $penalty;
        }

        if ($this->originalClass()::$MIN_WITHDRAWABLE_AMOUNT > $toDisburse) {
            throw new MinimumWithdrawalException($this->originalClass()::$MIN_WITHDRAWABLE_AMOUNT);
        }

        $calc = [
            'amount' => round($amount, 2),
            'can_withdraw' => $canWithdraw,
            'penalty' => round($penalty, 2),
            'penalty_rate' => round($penaltyRate, 2),
            'disburse_amount' => round($toDisburse, 2),
        ];

        if (!$canWithdraw) {
            $calc['next_withdrawal_date'] = $this->nextWithdrawalDate();
        }

        return $calc;

    }

    /**
     * A totally overridable method to move funds to wallet
     *
     * @param float $amount     The amount to move
     */
    protected function moveToWallet(float $amount): Self
    {
        return $this;
    }

    /**
     * Overridable perform actions when the saving is completed
     *
     * @return Self
     */
    protected function onCompleted(): Self
    {

        // Send an email to the customer on completion

        // Send a notification to admin

        // Oveeride this

        return $this;

    }

    /**
     * Perform actions when the saving is completed
     *
     * @param bool $skipWallet      Skip add balance to wallet
     *
     * @return Self
     */
    private function completed($skipWallet = false): Self
    {

        if (!$this->isTenorable()) {
            return $this;
        }

        $this->compute();

        if ($this->today_value >= 1 && !$skipWallet) {
            $value = $this->today_value;

            // Record in savings balance history
            $this->onWithdraw($value, null, 0);

            // Put the money in the savings wallet
            $this->moveToWallet($value);

        }

        // By now the saving is mature
        // or is not completed yet
        $this->dbUpdateRecalculate([
            'status' => 'completed',
            'principal' => 0,
            'completed_at' => now(),
        ]);

        // Send an email to the customer on completion

        // Send a notification to admin

        // Oveeride this
        $this->onCompleted();

        return $this;

    }

    /**
     * Perform balance history actions when new topup on saving
     * principal is already in kobo
     *
     * @param float $amount             The amount to topup
     * @param TransactionHistory $txn   The transaction object
     * @param float $newPrincipal       The new computed principal
     * @param Carbon $date              The payment date
     * @param bool $autoSave            If the topup is from the auto save feature
     *
     * @return Self
     */
    protected function onTopup(
        float $amount,
        TransactionHistory $txn,
        float $newPrincipal = null,
        Carbon $date = null,
        bool $autoSave = false

    ): Self {

        // Record the transaction to UserSavingBalanceHistory
        UserSavingBalanceHistory::create([
            'user_id' => $this->getUser()->id,
            'saving_id' => $this->id,
            'type' => UserSavingBalanceHistory::TYPES[0],
            'amount' => $this->toKobo($amount),
            'previous_rate' => !$newPrincipal ? null : $this->rate,
            'new_rate' => $this->getRate(),
            'previous_principal' => !$newPrincipal ? null : $this->principal,
            'new_principal' => !$newPrincipal ? $this->principal : $this->toKobo($newPrincipal),
            'transaction_id' => $txn ? $txn->id : null,
            'paid_at' => $date ?? now(),
            'via_auto_save' => $autoSave,
        ]);

        return $this;

    }
    /**
     * Perform balance history actions when new withdrawal is made on savings
     * Principal is already in Kobo
     *
     * @param float $amount             The amount to topup
     * @param TransactionHistory $txn   The transaction object
     * @param float $newPrincipal       The new computed principal
     * @param Carbon $date              The payment date
     *
     * @return Self
     */
    protected function onWithdraw(
        float $amount,
        TransactionHistory $txn = null,
        float $newPrincipal = 0,
        Carbon $date = null
    ): Self {

        $this->compute();

        $value = $this->today_value ?? 0;

        // Record the transaction to UserSavingBalanceHistory
        UserSavingBalanceHistory::create([
            'user_id' => $this->getUser()->id,
            'saving_id' => $this->id,
            'type' => UserSavingBalanceHistory::TYPES[1],
            'amount' => $this->toKobo($amount),
            'previous_rate' => $this->rate,
            'new_rate' => $this->getRate(),
            'previous_principal' => $this->principal,
            'previous_value' => $this->toKobo($value),
            'new_principal' => $this->toKobo($newPrincipal),
            'transaction_id' => $txn ? $txn->id : null,
            'paid_at' => $date ?? now(),
        ]);

        return $this;
    }

    /**
     * Non-overidable trigger called with the savings is mature
     *
     * @return self
     */
    private function onMature(): self
    {

        // Completed or is not a tenorable savings
        if ($this->isCompleted() || !$this->isTenorable()) {
            return $this;
        }

        $this->completed();

        return $this;

    }

    /**
     * Overidable Action to take on withdrawal day
     *
     * @return self
     */
    protected function onWithdrawalDay(): self
    {
        // Possibly send an email to the customer on the information
        return $this;
    }
}
