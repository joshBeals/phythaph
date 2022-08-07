<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Classes\InterestCalculator;
use App\Models\LoanRepayment;
use App\Models\LoanStatusAuditTrail as Auditor;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait LoanManager
{

    /**
     * Decline the loan
     *
     * @param Model|null $reason    The reason loan was declied
     * @return Self
     */
    public function decline(Model $reason = null): Self
    {
        if ($reason->exists()) {
            $this->decline_reason = $reason->id;
        }

        $this->changeStatus('declined');

        return $this;

    }

    /**
     * Decline the loan
     * @return Self
     */
    public function cancel(): Self
    {
        $this->changeStatus('cancelled');
        return $this;
    }

    /**
     * Change the loan status
     *
     * @return Self
     */
    public function changeStatus(string $status): Self
    {
        $this->status = $status;
        // $this->save();

        DB::table($this->getTable())->where('id', $this->id)->update([
            'status' => $status,
        ]);

        \App\Events\LoanStatusChanged::dispatch($this);

        return $this;
    }

    /**
     * Get the unit, i.e, day, month for a loan based on loan type
     *
     * @return string
     */
    public function getTenorUnit(): ?array
    {
        try {
            $calculation = $this->interest_calculation ?? $this->loanType()->first()->interest_calculation ?? null;
            if (!$calculation) {
                return null;
            }

            $unit = $calculation === 'monthly' ? 'month' : 'day';
            return [
                $unit, str_plural($unit),
            ];

        } catch (\Throwable $th) {
            return null;
        }

    }

    /**
     * Get the current due of the loan as at today
     * @return float
     */
    public function getCurrentDue(): float
    {
        // return $this->offer_amount ? $this->offer_amount : $this->amount;

        if (!in_array($this->status, ['running', 'overdue', 'tenor_overdue'])) {
            return 0;
        }

        $totalRepayments = $this->getTotalRepayments();
        $due = $this->due_payable - $totalRepayments;

        // Overdue
        if ($this->isOverdue()) {
            $overdueAmount = $this->getOverdueInterest();
            $due += $overdueAmount;
        }

        $due + $this->outstanding_interest;

        if ($due <= 0 && $this->status != 'completed') {
            $this->changeStatus('completed');
        }

        return $due;

    }

    /**
     * Get the current principal
     *
     * @return float
     */
    public function getCurrentPrincipal()
    {
        $principal = $this->offer_amount;
        if ($this->current_principal && intval($this->current_principal) != 0) {
            $principal = $this->current_principal;
        }

        return $principal;
    }

    /**
     * Get current interest, not factor in the outstanding interest
     *
     * @param int|null $day    Number of days default to total numbers of days loan has run or last payment made
     * @return float
     */
    public function getOverdueInterest()
    {
        // Total Days overdue
        $overdueDays = $this->getOverdueDays();

        // If payment was made after overdue
        if ($this->interest_initial_date->gte($this->due_date)) {
            $overdueDays = $this->getExactRunDaysAfterLastPayment();
        }

        return InterestCalculator::perDayInterest($this->current_principal, $this->overdue_interest ?? 1, $overdueDays)->maturity_interest;

    }

    /**
     * If the customer wants to pay before the due date, they will pay a penalty
     *
     * @return float
     */
    public function getBeforeDuePaymentCharge(): float
    {
        $beforeDue = $this->getCurrentDue();
        $principal = $this->getCurrentPrincipal();

        if (!$this->isRunning()) {
            return 0;
        }

        if ($this->due_date->gt(now()) && $this->before_due_percentage && $this->before_due_percentage !== 0) {
            $beforeDue = (($this->before_due_percentage / 100) * $principal) + $principal;
        }

        return $beforeDue;

    }

    /**
     * Determine if the loan is still running
     * @return bool
     */
    public function isRunning(): bool
    {
        return in_array($this->status, ['running', 'tenor_overdue', 'overdue']);
    }

    /**
     * Determine if the loan is still completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed']);
    }

    /**
     * Determine if the loan is past due
     * @return bool
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['tenor_overdue', 'overdue']) || $this->due_date->lt(now());
    }

    /**
     * Set this loan to running
     */
    public function setToRunning(bool $save = true): Self
    {

        if (!$this->offer_date || !$this->due_date) {
            $this->refresh();

            $loanType = $this->loanType()->first();

            $this->tenor = $tenor = intval($this->tenor ?? $loanType->tenor);

            if (!$this->before_due_percentage) {
                $this->before_due_percentage = $loanType->before_due_percentage;
            }

            $offer_date = now();
            $this->offer_amount = doubleval($this->offer_amount);
            $this->current_principal = $this->offer_amount;
            $this->offer_date = $offer_date;
            if (!$this->interest_initial_date) {
                $this->interest_initial_date = $offer_date;
            }

            $breakdown = $this->computeBreakdown();

            // In the cause of testing the chargeback
            $this->due_date = Carbon::parse($offer_date)->addDays($breakdown->tenor_in_days + $breakdown->retention_period);

            $this->due_payable = $breakdown->maturity_value;
            $this->next_repayment_date = $breakdown->repayment_start_day;

            $this->admin_rate = $breakdown->admin_charge_rate;
            $this->other_charge_rate = $breakdown->other_charge_rate;
            $this->other_charge_name = $breakdown->other_charge_name;

            $this->admin_charge_method = $breakdown->admin_charge_method;
            $this->other_charge_method = $breakdown->other_charge_method;
            $this->remove_charges_at_source = $breakdown->remove_charges_at_source;

            if ($save) {
                $this->save();
            }
        }

        return $this;

    }

    /**
     * Decorate loan with more information
     *
     * @return Self
     */
    public function decorate(): Self
    {

        $loanType = $this->loanType()->first();
        $breakdown = $this->computeBreakdown();

        $this->breakdown = $breakdown;

        $this->amount_or_offer = $this->offer_amount ? $this->offer_amount : $this->amount;
        $this->amount_request_or_offer = $this->offer_amount ? 'offered' : 'requested';
        $this->request_date_human = $this->date = Helper::readableDate($this->created_at);
        $this->request_date_short = Helper::shortDate($this->created_at);
        $this->request_date_long = Helper::formatDate($this->created_at);

        $this->_amount = Helper::formatToCurrency($this->amount);
        $this->_offer_amount = Helper::formatToCurrency($this->offer_amount ?? 0);

        $this->_due_payable = Helper::formatToCurrency($this->due_payable ?? 0);
        $this->due_today = $this->getCurrentDue();
        $this->_due_today = Helper::formatToCurrency($this->due_today ?? 0);

        $this->_purpose = Str::title($this->purpose);
        $this->_status = Str::title($this->status);

        $this->offer_date_human = Helper::readableDate($this->offer_date);
        $this->offer_date_short = Helper::shortDate($this->offer_date);
        $this->offer_date_long = Helper::formatDate($this->offer_date);

        $this->due_date_human = Helper::readableDate($this->due_date);
        $this->due_date_short = Helper::shortDate($this->due_date);
        $this->due_date_long = Helper::formatDate($this->due_date);

        $this->_admin_rate = Helper::formatToCurrency($this->admin_rate ?? 0);
        $this->_other_charge_rate = Helper::formatToCurrency($this->other_charge_rate ?? 0);

        $this->admin_rate_rate = 0;
        $this->other_charge_rate_rate = 0;

        $unit = $this->getTenorUnit();
        $this->_unit = sizeOf($unit) ? $unit[0] : null;
        $this->_unit_plural = sizeOf($unit) > 1 ? $unit[1] : null;

        if ($this->admin_charge_method === 'percentage') {
            $this->admin_rate_rate = $loanType->admin_charge_rate;
        }
        if ($this->other_charge_method === 'percentage') {
            $this->other_charge_rate_rate = $loanType->other_charge_rate;
        }

        $badge = '';
        switch ($this->status) {
            case 'pending':
                $badge = 'primary';
                break;
            case 'declined':
            case 'cancelled':
                $badge = 'danger';
                break;
            case 'completed':
            case 'approved':
            case 'running':
                $badge = 'success';
                break;
            default:
                $badge = 'info';
        }

        $this->badge = $this->badge_color = $badge;

        return $this;
    }

    /**
     * Get the repayments of this transaction removing the duplicates
     *
     * @param bool $orderAsending = false   Get it in ascneding or descending order
     */
    public function getRepayments(bool $orderAsending = false)
    {
        return $this->repayments()->get();

        $repayments = $orderAsending ? $this->orderedRepayments()->get() : $this->repayments()->get();
        return $repayments;

    }

    /**
     * Compute the loan schedule as discovered on loandisk
     */
    public function computeSchedule(): ?array
    {

        if (!in_array($this->status, ['running', 'tenor_overdue', 'overdue', 'completed'])) {
            return null;
        }

        // Gather all the repayments
        // $repayments = $this->orderedRepayments;
        /**
         * 11/06/2020
         * Use getRepayments to removed all duplicate payments from repayments
         */
        $repayments = $this->getRepayments(true);
        $returnRepayment = clone ($repayments);
        $offerDate = Carbon::parse($this->offer_date);
        $offerAmount = (float) $this->offer_amount;
        $interest = (float) $this->interest;

        // Grab the minimum tenor a loan can run
        $minTenor = Self::MIN_TENOR;
        $tenor = $this->getRawOriginal('tenor');

        // This is the date the schedule will start calculating
        $initCalculationDate = $offerDate->copy()->addDay($minTenor);

        // This is the date the schedule will end
        $finalCalculationDate = now();

        // The princpal changes as schedules are calculated
        $currentPrincipal = $offerAmount;

        // if it is a completed loan,
        // finalCalculationDate should be the date it was merked as complete
        // Or the date of last payment
        if ($this->status == 'completed') {
            $completedAuditTrail = $this->auditTrail()->where('status', 'completed')->orderBy('id', 'desc')->first();

            if ($completedAuditTrail) {
                $finalCalculationDate = $completedAuditTrail->created_at;
            } else {
                // Thats wierd but it happens
                if (!$repayments || !$repayments->count() || !$repayments->last()) {
                    return null;
                } else {
                    $finalCalculationDate = $repayments->last()->date;
                }
            }
        }

        // Based on the the initial and final dates
        //  If the loan was repaid before due date
        // calculate based on offer date,
        // else calculate based on initCalculationDate
        if ($offerDate->copy()->startOfDay()->gte($finalCalculationDate->copy()->startOfDay())) {
            // dd([$offerDate, $finalCalculationDate]);
            $daysRun = $finalCalculationDate->diffInDays($offerDate);
        } else {
            $daysRun = $initCalculationDate->diffInDays($finalCalculationDate);
        }

        $overdueInterest = $this->overdue_interest ?? 1;

        $singleDayInterest = $this->computeMaturity(null, null, null)->daily_interest;

        // The minimum intrest factoring in the minTenor
        $minInterest = $singleDayInterest * $minTenor;

        $numberOfRepayments = $repayments->count();

        // From to $initCalculationDate
        //  The increases as each days in the loop
        $thisDay = 1;

        // Initial 7 days interst
        $interestAccumulation = $singleDayInterest * $minTenor;

        // The remaining yet to pay
        $outstanding = $currentPrincipal + ($interestAccumulation);

        // Accumulation of how much the loan is per day as it runs
        $overall = $offerAmount + $interestAccumulation;

        // Add the first schedule which is the offer date
        $schedules = [
            [
                'date' => $offerDate->format('d M Y'),
                'description' => 'Principal',
                'principal' => 0.00,
                'interest' => 0.00,
                'repayment' => 0.00,
                'due' => 0.00,
                'outstanding' => 0.00,
                'overall' => 0.00,
                'principal_balance' => $currentPrincipal,
            ],
        ];

        // Check if any repayments was made on or before the min tenor
        // Add if to the schedue
        if ($numberOfRepayments) {
            // Keep looping until there is not payment before the minimum tenor date
            while (true) {
                // dd($repayments[0]);
                if ($repayments->count()
                    && $initCalculationDate->copy()->startOfDay()->gte($repayments[0]->date->copy()->startOfDay())) {
                    $repayment = $repayments[0];
                    $amount = $repayment->amount;

                    if (!$amount) {
                        continue;
                    }

                    $outstanding = $outstanding - $amount;
                    $currentPrincipal = $outstanding < $currentPrincipal ? $outstanding : $currentPrincipal;
                    $overdueSingleDayInterest = ($overdueInterest / 100) * $currentPrincipal;
                    $singleDayInterest = $this->isOverdue() ? $overdueSingleDayInterest : $this->computeMaturity($currentPrincipal, $interest, 1)->maturity_interest;
                    $minInterest = $singleDayInterest;

                    $payment = [
                        'date' => (string) $repayment->date->format('d M Y'),
                        'payment_date' => (string) $repayment->date,
                        'description' => 'Repayment',
                        'principal' => 0.00,
                        'interest' => 0,
                        'repayment' => (float) $amount,
                        'due' => (float) (-1 * $amount),
                        'outstanding' => $outstanding,
                        'overall' => $overall,
                        'principal_balance' => $currentPrincipal,
                        'excess_payment' => $currentPrincipal < 0,
                    ];

                    array_push($schedules, $payment);

                    // Remove the first repayment
                    $repayments->shift();

                } else {
                    break;
                }
            }
        }

        // At this point the loan might be paid in full
        // If that is the case stop calculation
        if ($currentPrincipal > 0) {

            // Add the initial day of interest which is the min tenor day
            $dueDay = [
                'date' => (string) $initCalculationDate->format('d M Y'),
                'description' => 'Interest',
                'principal' => $currentPrincipal,
                'interest' => $minInterest,
                'repayment' => 0.00,
                'due' => $currentPrincipal + $minInterest,
                'outstanding' => $outstanding,
                'overall' => $overall,
                'principal_balance' => $currentPrincipal,
            ];

            array_push($schedules, $dueDay);

            // Loop through other days and add them to the schedule
            for ($run = 0; $run < $daysRun; $run++) {
                if ($outstanding > 0) {
                    $outstanding = $outstanding + ($singleDayInterest);
                    $overall = $overall + $singleDayInterest;
                    $todaysDate = $initCalculationDate->copy()->addDay($thisDay);
                    $today = [
                        'date' => (string) $todaysDate->format('d M Y'),
                        'description' => 'Interest',
                        'principal' => $currentPrincipal,
                        'interest' => $singleDayInterest,
                        'repayment' => 0.00,
                        'due' => $currentPrincipal + ($singleDayInterest),
                        'outstanding' => $outstanding,
                        'overall' => $overall,
                        'principal_balance' => $currentPrincipal,
                    ];

                    array_push($schedules, $today);
                }

                // Check if there is a repayment today
                while (true) {
                    if ($repayments->count()
                        && $todaysDate->copy()->startOfDay()->eq($repayments[0]->date->copy()->startOfDay())) {
                        $repayment = $repayments[0];
                        $amount = $repayment->amount;
                        if (!$amount) {
                            continue;
                        }
                        $outstanding = $outstanding - $amount;
                        $currentPrincipal = $outstanding < $currentPrincipal ? $outstanding : $currentPrincipal;
                        $overdueSingleDayInterest = ($overdueInterest / 100) * $currentPrincipal;
                        $singleDayInterest = $this->isOverdue() ? $overdueSingleDayInterest : $this->computeMaturity($currentPrincipal, $interest, 1)->maturity_interest;
                        $payment = [
                            'date' => (string) $todaysDate->format('d M Y'),
                            'payment_date' => (string) $repayment->date,
                            'description' => 'Repayment',
                            'principal' => 0.00,
                            'interest' => 0,
                            'repayment' => (float) $amount,
                            'due' => (float) (-1 * $amount),
                            'outstanding' => $outstanding,
                            'overall' => $overall,
                            'principal_balance' => $currentPrincipal,
                            'excess_payment' => $currentPrincipal < 0,
                        ];

                        array_push($schedules, $payment);

                        // Remove the first repayment
                        $repayments->shift();

                    } else {
                        break;
                    }
                }
                // if principal is less than 0 and break

                $thisDay++;
            }

            // if principal is 0 or less or is a completed loan and the repayment is still remaining
            // Add all the repayments as negative to customer
            if ($repayments->count()) {
                foreach ($repayments as $repayment) {
                    $amount = $repayment->amount;
                    if (!$amount) {
                        continue;
                    }
                    $outstanding = $outstanding - $amount;
                    $currentPrincipal = $outstanding < $currentPrincipal ? $outstanding : $currentPrincipal;
                    $overdueSingleDayInterest = ($overdueInterest / 100) * $currentPrincipal;
                    $singleDayInterest = $this->isOverdue() ? $overdueSingleDayInterest : $this->computeMaturity($currentPrincipal, $interest, 1)->maturity_interest;

                    $payment = [
                        'date' => (string) $todaysDate->format('d M Y'),
                        'payment_date' => (string) $repayment->date,
                        'description' => 'Repayment',
                        'principal' => 0.00,
                        'interest' => 0,
                        'repayment' => (float) $amount,
                        'due' => (float) (-1 * $amount),
                        'outstanding' => $outstanding,
                        'overall' => $overall,
                        'principal_balance' => $currentPrincipal,
                        'excess_payment' => $currentPrincipal < 0,
                    ];

                    array_push($schedules, $payment);
                }
            }

            // If its a completed loan and principal is still more than 0 and its past the completion date above
            // compute from the finalCalculationDate to today.
            if ($this->status == 'completed' && $currentPrincipal > 0) {
                // First check if there is a waiver, if there is, terminate the loan
                $waiver = $this->waiver;
                if ($waiver) {
                    $currentPrincipal = 0;
                    $outstanding = 0;
                    $reason = GlobalVars::$waiverReasons[$waiver->waiver_reason] ?? $waiver->waiver_reason;
                    $waived = [
                        'date' => (string) $waiver->date->format('d M Y'),
                        'description' => "Waiver of {$waiver->waiver_amount} by {$waiver->waived_by_who->name} due to {$reason}",
                        'principal' => 0.00,
                        'interest' => 0.00,
                        'repayment' => 0.00,
                        'due' => (float) (-1 * $waiver->waiver_amount),
                        'outstanding' => 0,
                        'overall' => $outstanding,
                        'principal_balance' => $currentPrincipal,
                        'is_waiver' => true,
                    ];

                    array_push($schedules, $waived);

                } else {
                    $remainingDays = $finalCalculationDate->diffInDays(now());
                    $finalCalculationDate = now();
                    $daysRun = $initCalculationDate->diffInDays($finalCalculationDate);

                    for ($run = 0; $run < $remainingDays; $run++) {
                        $outstanding = $outstanding + ($singleDayInterest);
                        $overall = $overall + $singleDayInterest;
                        $todaysDate = $initCalculationDate->copy()->addDay($thisDay);
                        $today = [
                            'date' => (string) $todaysDate->format('d M Y'),
                            'description' => 'Interest',
                            'principal' => $currentPrincipal,
                            'interest' => $singleDayInterest,
                            'repayment' => 0.00,
                            'due' => $currentPrincipal + ($singleDayInterest),
                            'outstanding' => $outstanding,
                            'overall' => $overall,
                            'principal_balance' => $currentPrincipal,
                        ];

                        array_push($schedules, $today);

                        $thisDay++;

                    }

                }
            }
        }

        // If outstanding is deficit, complete the loan
        if ($outstanding < 0 && $this->status != 'completed') {
            $this->changeStatus('completed');
            // DB::table('loans')->where('id', $this->id)->update(['status' => 'completed']);
        }

        // Before due charge outstanding
        $beforeDue = $outstanding;
        if ($this->due_date->gt(now())) {
            $beforeDue = (($this->before_due_percentage / 100) * $outstanding) + $outstanding;
        }

        return [
            'offer_date' => $offerDate,
            'offer_amount' => $offerAmount,
            'interest' => $interest,
            'min_tenor' => $minTenor,
            'tenor' => $tenor,
            'init_calculation_date' => (string) $initCalculationDate,
            'final_calculation_date' => (string) $finalCalculationDate,
            'days_run' => $daysRun + $minTenor,
            'actual_days_run' => $daysRun,
            'days_to_calculate' => $daysRun,
            'outstanding' => round($outstanding + $this->outstanding_interest, 2),
            'overall' => $overall,
            'principal_balance' => $currentPrincipal,
            'outstanding_interest' => $this->outstanding_interest,
            'schedules' => $schedules,
            'up_front_payment' => round($beforeDue, 2),
        ];

    }

    /**
     * This returns the exact days loan run
     * since the time it was disbused and or set to running
     */
    public function getExactLoanRunningDays()
    {
        $d = Carbon::now()->diffInDays(Carbon::parse($this->offer_date));
        return $d <= 0 ? 1 : $d;
    }

    /**
     * This factors in the minimum days loans must roan
     */
    public function getLoanRunningDays()
    {
        $diff = $this->getExactLoanRunningDays();
        $minDays = Self::MIN_TENOR ?? 1;
        // Make sure user can only pay for a specific days or more
        if ($diff <= $minDays) {
            $diff = $minDays;
        }

        return $diff;
    }

    /**
     * Returns the number of days the loan has been running
     * since the last payment factoring the min days rule
     */
    public function getRunDaysAfterLastPayment()
    {
        if (!$this->interest_initial_date) {
            return $this->getLoanRunningDays();
        }

        $d = Carbon::now()->diffInDays(Carbon::parse($this->interest_initial_date));
        $e = $this->getExactLoanRunningDays();

        // If the person has made payment before,
        // just to make sure the 7 days rule is not applicable twice

        return $e <= Self::MIN_TENOR ? Self::MIN_TENOR : $d;
    }

    /**
     * Returns the number of days the loan has been running
     * since the last payment without factoring the min days rule
     */

    public function getExactRunDaysAfterLastPayment()
    {
        if (!$this->interest_initial_date) {
            return $this->getLoanRunningDays();
        }

        return Carbon::now()->diffInDays(Carbon::parse($this->interest_initial_date));
    }

    public function getOverdueDays(): int
    {
        return $this->due_date->gt(now()) ? 0 : now()->diffInDays($this->due_date);
    }

    /**
     * Get total repayments of a loan removing wwaivers
     */
    public function getTotalRepayments(): ?float
    {
        $payments = $this->getRepayments();

        if (!$payments || !$payments->count()) {
            return 0;
        }

        $total = 0;

        foreach ($payments as $payment) {
            if (!$payment->is_waived) {
                $total += $payment->transaction->amount;
            }

        }

        return round(doubleval($total / 100), 2);

    }

    /**
     * Record a part or full payment against a loan
     *
     * @param double $amount        The amount to record
     * @param string|null $txn_ref  The transaction reference for the payment
     * @param string|null $description  The description for the payment
     * @param string|null $payment_method  The payment method, processor or transfer
     * @param string|null $customer_code  The customer code made payemnt to
     * @param float $charges  Payment aggregator charge
     * @param Carbon\Carbon $payment_date  The date of payment
     *
     * @return bool If the record was succesfull
     */
    public function recordPayment(
        float $amount,
        string $txn_ref = null,
        string $description = '',
        string $payment_method = "transfer",
        string $customer_code = '',
        float $charges = null,
        Carbon $payment_date = null
    ) {

        try {
            $loan = $this;
            $loan->refresh();
            $lastPayment = $loan->getRunDaysAfterLastPayment();
            $daysToCalculate = $lastPayment;
            $diffToday = 0;

            // Withold the initial current due before recording payments
            if ($payment_date) {
                $lastPayment = $loan->getRunDaysAfterLastPayment();
                $diffToday = Carbon::now()->diffInDays($payment_date);

                $daysToCalculate = $lastPayment - $diffToday;
            }

            $currentDue = $loan->getCurrentDue();
            $currentPrincipal = $loan->getCurrentPrincipal();

            $user_id = $loan->user_id;

            $amountAfterProcessorCharges = $amount;

            if ($amountAfterProcessorCharges >= $currentDue) {
                $loan->current_principal = 0;
                $loan->outstanding_interest = 0;
                $loan->status = 'completed';
                $loan->changeStatus('completed');

            } else {

                if ($loan->isOverdue()) {

                    $overdueInterest = $this->getOverdueInterest();
                    if ($amountAfterProcessorCharges >= $overdueInterest) {
                        $loan->current_principal = $currentDue - $amountAfterProcessorCharges;
                        $loan->outstanding_interest = 0;
                    } else {
                        $loan->outstanding_interest = $overdueInterest - $amountAfterProcessorCharges;
                    }

                }

                $loan->interest_initial_date = now()->subDays($diffToday);

            }

            if (!isset($txn_ref) || $txn_ref == "") {
                $txn_ref = Helper::makeTxnRef();
            }

            $payment_date = $payment_date ?? now();

            $repayReason = "Loan #{$loan->id} Repayment";

            $txnHistory = Transaction::where('reference', $txn_ref)->first();
            if (!$txnHistory) {

                $txnHistory = new Transaction;
                $txnHistory->user_id = $loan->user_id;
                $txnHistory->reference = $txn_ref;
                $txnHistory->amount = $amount * 100;
                $txnHistory->payment_method = $payment_method;
                $txnHistory->description = $repayReason;
                $txnHistory->entity_id = $loan->id;
                $txnHistory->currency = 'NGN';
                $txnHistory->ip_address = Helper::getIp();
                $txnHistory->customer_code = $customer_code;
                $txnHistory->type = 'loan_repayment';

                $txnHistory->created_at = $payment_date;
                $txnHistory->paid_at = $payment_date;

                $txnHistory->save();
            }

            // Log to loan repayment
            $pm = new LoanRepayment;
            $pm->user_id = $loan->user_id;
            $pm->transaction_id = $txnHistory->id;
            $pm->loan_id = $loan->id;
            $pm->no_of_days = $loan->getExactLoanRunningDays() - $diffToday;

            if ($payment_date) {
                $pm->created_at = $payment_date;
            }
            $pm->save();

            // Store the Audit trail
            $q = [
                'user_id' => $loan->user_id,
                'loan_id' => $loan->id,
                'status' => "payment",
                'description' => @$description ?? $repayReason,
                'entered_by' => null,
            ];

            $admin = backpack_user();
            if ($admin) {
                $q['entered_by'] = $admin->id;
            }

            Auditor::create($q);

            $loan->save();

            $due = $loan->getCurrentDue();
            $loan->dbUpdate(['current_due' => $due], true);
            return true;

        } catch (\Throwable $th) {
            $this->log($th);
            return false;
        }

    }

    /**
     * Attempt to recover loan based on a specified amount or current due
     *
     * @param float|null $charge_amount     The amount to charge or current due
     * @param UserCard|null $cardToCharge        The card to charge
     *
     * @return bool
     */
    public function attemptRepay($charge_amount = null, $cardToCharge = null)
    {

        $loan = $this;

        // Factoring in if the person pays before due
        $due = $loan->getBeforeDuePaymentCharge();

        if ($loan->isCompleted()) {
            return true;
        }

        // Get the current user
        $user = $loan->user()->first();

        // Get the user card;
        $card = $cardToCharge ?? $user->getDefaultCard();

        if (!$card || !$card->reusable) {
            return false;
        }

        if ($due < 0 || ($charge_amount && $charge_amount > $due)) {
            return;
        }

        // Get the amount;
        $amount = $charge_amount ?? $due;

        $data = [
            'authorization_code' => $card->authorization_code,
            'email' => $user->email,
            'amount' => $due * 100,
            'currency' => 'NGN',
            'reference' => Helper::makeTxnRef(),
            'metadata' => [
                'scope' => 'loan_repay',
                'loan_id' => $loan->id,
                'user_id' => $user->id,
                'custom_fields' => [
                    [
                        'display_name' => 'Scope',
                        'variable_name' => 'scope',
                        'value' => 'loan_repay',
                    ],
                    [
                        'display_name' => 'Loan ID',
                        'variable_name' => 'loan_id',
                        'value' => $loan->id,
                    ],
                    [
                        'display_name' => 'User ID',
                        'variable_name' => 'user_id',
                        'value' => $user->id,
                    ],
                    [
                        'display_name' => 'Amount Due',
                        'variable_name' => 'amount_due',
                        'value' => $due,
                    ],
                ],
            ],
        ];

        $endpoint = 'charge_authorization';

        if ($charge_amount && ($due > $charge_amount) && $loan->isOverdue()) {
            $data['at_least'] = $charge_amount * 100;
            $endpoint = 'partial_debit';
        }

        // dd($data);

        // Try to charge the user
        try {
            $papi = new PaystackApi();
            $result = $papi->send("/transaction/$endpoint", $data);

            // Let webhook handle the rest
            return true;

        } catch (\Throwable $e) {
            Log::debug($e->getMessage());
            return false;
        }
    }

    /**
     * Get the maturity value based on supplied amount
     * or interest or the offer amount and interest of the loan
     *
     * @param float $amount         The amount to compute
     * @param float $interest       The interest per annum
     * @param int $tenor            The number of days loan will run
     * @param Carbon $commenceDate  The day the calculaion commenced
     * @param string $calculation   What the calculation is based on
     *
     * @return null|Object
     */
    public function computeMaturity(
        float $amount = null,
        float $interest = null,
        int $tenor = null,
        Carbon $commenceDate = null,
        int $retention_period = 0,
        string $calculation = null
    ): ?\StdClass{
        $loanType = $this->loanType()->first();

        if (!$amount) {
            $amount = $this->offer_amount ?? $this->amount;
        }

        if (!$commenceDate) {
            $commenceDate = $this->offer_date ?? now();
        }

        if (!$interest || !$tenor || !$calculation) {
            if (!$interest) {
                $interest = $this->interest;
                // Still no interest
                if (!$interest) {
                    if ($loanType) {
                        $interest = $loanType->interest_rate;
                    }
                }
            }
            if (!$tenor) {
                $tenor = $this->tenor;
                // Still no interest
                if (!$tenor) {
                    if ($loanType) {
                        $tenor = $loanType->max_tenor;
                    }
                }

            }
            if (!$calculation) {
                $calculation = $this->interest_calculation;
                // Still no interest
                if (!$calculation) {
                    if ($loanType) {
                        $calculation = $loanType->interest_calculation;
                    }
                }

            }
        }

        if (!$amount || !$interest || !$tenor || !$calculation) {
            return null;
        }

        $maturity_interest = 0;
        $maturity_value = 0;

        switch ($calculation) {
            case 'per_day':
                $calculation = InterestCalculator::perDayInterest($amount, $interest, $tenor);
                break;
            case 'per_month':
                $calculation = InterestCalculator::perMonthInterest($amount, $interest, $tenor, $commenceDate, $retention_period, $loanType->ignore_weekened_repayments);
                break;
            default:
                $calculation = InterestCalculator::simpleInterest($amount, $interest, $tenor);
        }

        // $maturity_value = $calculation->maturity_value;
        // $maturity_interest = $calculation->maturity_interest;
        // return (Object) compact('maturity_interest', 'maturity_value', 'calculation');
        return (Object) $calculation;

    }

    /**
     * Compute the breakdown of the loan used for loan request
     * @return null|Self
     */
    public function computeBreakdown(): ?Object
    {
        $loanType = $this->loanType()->first();

        $amount = $this->offer_amount ?? $this->amount;

        if (!$loanType || !$amount || !$this->tenor) {
            return null;
        }

        $interest = $this->interest ?? $loanType->interest_rate;

        if ($this->status === 'pending') {
            $commenceDate = now();
        } else {
            $commenceDate = $this->offer_date ?? $this->created_at ?? now();
        }

        $retention_period = $loan->retention_period ?? $loanType->retention_period;

        // The loan repayment plan
        $repayment_plan = $this->repayment_plan ?? $loanType->repayment_plan;

        // Number of days to compute the installments
        $repayment_interval_days = $loanType->getRepaymentPlanDays()[$repayment_plan];

        // Number of days before first repayment start to be computed
        // Apply monatorium for only daily loans
        if ($repayment_interval_days === 1) {
            $repayment_start_days = $retention_period;
        } else {
            $repayment_start_days = $repayment_interval_days;
        }

        // When the first repayment is computed
        $repayment_start_day = $commenceDate->copy()->addDay($repayment_start_days);

        if ($loanType->ignore_weekened_repayments) {
            while (true) {
                if ($repayment_start_day->isWeekend()) {
                    $repayment_start_day = $repayment_start_day->addDay(1);
                } else {
                    break;
                }
            }
        }

        $breakdown = $this->computeMaturity($amount, $interest, $this->tenor, $commenceDate, $retention_period);

        $breakdown->amount = $amount;
        $breakdown->tenor = $this->tenor;
        $breakdown->commencement_date = $commenceDate;
        $breakdown->due_date = $commenceDate->copy()->addDays($breakdown->tenor_in_days);
        $breakdown->interest_calculation = $this->interest_calculation ?? $loanType->interest_calculation;

        $charges = $this->computeCharges($amount);

        $breakdown->admin_charge_method = $charges->admin_charge_method;
        $breakdown->admin_charge_rate = $charges->admin_charge_rate;
        $breakdown->admin_charge = $charges->admin_charge;
        $breakdown->other_charge_method = $charges->other_charge_method;
        $breakdown->other_charge_rate = $charges->other_charge_rate;
        $breakdown->other_charge = $charges->other_charge;
        $breakdown->other_charge_name = $charges->other_charge_name;

        $breakdown->total_charges = $charges->total_charges;

        $breakdown->remove_charges_at_source = $charges->remove_charges_at_source;

        $breakdown->amount_after_charges = $breakdown->remove_charges_at_source ?
        $amount - ($breakdown->total_charges) : $amount;

        // Days before the first installment commence calculations
        $breakdown->retention_period = $retention_period;

        // The loan repayment plan
        $breakdown->repayment_plan = $repayment_plan;

        // Number of days to compute the installments
        $breakdown->repayment_interval_days = $repayment_interval_days;

        // When the first repayment is computed
        $breakdown->repayment_start_day = $repayment_start_day;

        // Total number of days (window) the installment is paid
        $breakdown->total_installment_days = ($breakdown->total_number_of_computed_days ?? $breakdown->tenor_in_days ?? $breakdown->tenor);

        // Amount to be paid in installment
        $breakdown->repayment_installment_amount = $breakdown->{$breakdown->repayment_plan . "_repayment"} ?? round(($breakdown->repayment_interval_days / ($breakdown->total_installment_days)) * $breakdown->maturity_value, 2);
        $breakdown->repayment_installment_count = ceil($breakdown->total_installment_days / $breakdown->repayment_interval_days);

        $breakdown->installments = [
            [
                'repayment_date' => $breakdown->repayment_start_day,
                'amount' => $breakdown->repayment_installment_amount,
                '_amount' => Helper::formatToCurrency($breakdown->repayment_installment_amount),
            ],
        ];

        $lastRepaymentDate = $breakdown->repayment_start_day->copy();
        $totalRepaymentAmount = $breakdown->repayment_installment_amount;

        for ($installment = 1; $installment < $breakdown->repayment_installment_count; $installment++) {
            $repaymentAmount = round(
                (($totalRepaymentAmount + $breakdown->repayment_installment_amount) <= $breakdown->maturity_value
                    ? $breakdown->repayment_installment_amount
                    : $breakdown->maturity_value - $totalRepaymentAmount),
                2);
            $repaymentDate = $lastRepaymentDate->addDay($breakdown->repayment_interval_days);
            if ($loanType->ignore_weekened_repayments) {
                while (true) {
                    if ($repaymentDate->isWeekend()) {
                        $repaymentDate = $lastRepaymentDate->addDay($breakdown->repayment_interval_days);
                    } else {
                        break;
                    }
                }
            }
            if ($repaymentAmount <= 0) {
                break;
            }

            $breakdown->installments[] = [
                'repayment_date' => $repaymentDate->lte($breakdown->due_date) ? $repaymentDate : $breakdown->due_date,
                'amount' => $repaymentAmount,
                '_amount' => Helper::formatToCurrency($repaymentAmount),
            ];

            $totalRepaymentAmount += $repaymentAmount;
            $lastRepaymentDate = $repaymentDate->copy();
        }

        // If some small amount of money is still remaining on the breakdown
        if ($totalRepaymentAmount < $breakdown->maturity_value) {
            $remainAmount = $breakdown->maturity_value - $totalRepaymentAmount;
            if ($remainAmount > 1) {
                $lastAmount = $breakdown->installments[sizeOf($breakdown->installments) - 1]['amount'];
                $breakdown->installments[sizeOf($breakdown->installments) - 1]['amount'] = $remainAmount + $lastAmount;
                $breakdown->installments[sizeOf($breakdown->installments) - 1]['_amount'] = Helper::formatToCurrency($remainAmount + $lastAmount);
            }
        }

        $breakdown->actual_installment_count = sizeOf($breakdown->installments);

        // Decorators
        $breakdown->_admin_charge = Helper::formatToCurrency($breakdown->admin_charge ?? 0);
        $breakdown->_other_charge = Helper::formatToCurrency($breakdown->other_charge ?? 0);
        $breakdown->_total_charges = Helper::formatToCurrency($breakdown->total_charges ?? 0);
        $breakdown->_amount = Helper::formatToCurrency($breakdown->amount ?? 0);
        $breakdown->_amount_after_charges = Helper::formatToCurrency($breakdown->amount_after_charges ?? 0);
        $breakdown->_maturity_value = Helper::formatToCurrency($breakdown->maturity_value ?? 0);
        $breakdown->_repayment_installment = Helper::formatToCurrency($breakdown->repayment_installment_amount ?? 0);
        $breakdown->_repayment_installment = Helper::formatToCurrency($breakdown->repayment_installment_amount ?? 0);
        $breakdown->_repayment_plan = Helper::titleCase($breakdown->repayment_plan);
        $breakdown->_due_date = Helper::shortDate($breakdown->due_date);
        $breakdown->_repayment_start_day = Helper::shortDate($breakdown->repayment_start_day);

        // $loanType->decorate();
        // $breakdown->loan_type = $loanType;
        // $breakdown->loan = (clone $this)->decorate();

        // echo json_encode($breakdown);
        // die();

        return $breakdown;

    }

    /**
     * Compute any admin charges regardless of when it is deducted
     *
     * @return StdClass
     */
    public function computeCharges(float $amount): \StdClass
    {
        $charges = new \StdClass;

        $loanType = $this->loanType()->first();
        $adminChargeMethod = $this->admin_charge_method ?? $loanType->admin_charge_method;
        $otherChargeMethod = $this->other_charge_method ?? $loanType->other_charge_method;

        $adminCharge = $this->admin_rate != 0 ? $this->admin_rate : $loanType->admin_charge_rate;
        $otherCharge = $this->other_charge_rate != 0 ? $this->other_charge_rate : $loanType->other_charge_rate;

        $charges->admin_charge_rate = floatval($adminCharge);
        $charges->other_charge_rate = floatval($otherCharge);
        $charges->other_charge_name = $this->other_charge_name ?? $loanType->other_charge_name;

        $charges->admin_charge = !$adminCharge ? 0 : ($adminChargeMethod === 'fixed' ? floatval($adminCharge) : ($adminCharge / 100) * $amount);
        $charges->other_charge = !$otherCharge ? 0 : ($otherChargeMethod === 'fixed' ? floatval($otherCharge) : ($otherCharge / 100) * $amount);

        $charges->total_charges = $charges->admin_charge + $charges->other_charge;

        $charges->admin_charge_method = $adminChargeMethod;
        $charges->other_charge_method = $otherChargeMethod;
        $charges->remove_charges_at_source = $this->remove_charges_at_source ?? $loanType->remove_charges_at_source;

        return $charges;
    }

    /**
     * Get the date of the next installment payment
     * Used to record next installment payment
     *
     * @return Carbon
     */
    public function getNextRepaymentDate(): ?Carbon
    {
        if (!$this->isRunning()) {
            return null;
        }

        $installments = $this->computeBreakdown()->installments;

        $nextRepayment = null;

        foreach ($installments as $v) {
            if ($v['repayment_date']->gt(now())) {
                $nextRepayment = $v['repayment_date'];
                break;
            }
        }

        if (!$nextRepayment) {
            $nextRepayment = $installments[sizeOf($installments) - 1]['repayment_date'];
        }

        return $nextRepayment;

    }

    /**
     * Get the list of loan installments that is due
     */
    public function getRepaymentBreakdown(): ?\StdClass
    {
        $nextRepayment = $this->getNextRepaymentDate();
        $b = $this->computeBreakdown();
        $installments = $b->installments;
        $repayments = $this->repayments()->get();

        $breakdown = new \StdClass;

        $breakdown->total_repayments = $this->getTotalRepayments();

        // Get the balance due
        $breakdown->total_outstanding = $this->getCurrentDue();
        // Get the balance due with before_due_charge if due date is not here
        $breakdown->up_front_payment = $this->getBeforeDuePaymentCharge();

        $breakdown->breakdown = $b;

        // Get all the list installments yet to be paid
        $breakdown->outstanding_installments = [];
        $breakdown->installments = [];

        foreach ($installments as $in) {

            $in['status'] = 'unpaid';

            // Dont add future dates except the next installment
            if ($in['repayment_date']->gt(now()) && !$in['repayment_date']->isSameDay($nextRepayment)) {
                $breakdown->installments[] = $in;
                continue;
            }

            // Check if a repayment for this installment exists
            $check = $repayments->first(function ($v, $k) use ($in) {
                return $v->created_at->isSameDay($in['repayment_date']);
            });

            if (!$check) {
                $breakdown->outstanding_installments[] = $in;
                if (!$in['repayment_date']->isSameDay($nextRepayment)) {
                    $in['status'] = 'missed';
                }

            } else {
                $in['status'] = 'paid';
            }
            $breakdown->installments[] = $in;
        }

        // Get the total installment yet to be paid
        $outstanding = collect($breakdown->outstanding_installments)->sum('amount');
        $breakdown->total_outstanding_installments = $outstanding < $breakdown->total_outstanding ? $outstanding : $breakdown->total_outstanding;

        return $breakdown;
    }

}
