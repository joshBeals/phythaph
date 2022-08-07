<?php
namespace App\Traits;

use App\Classes\Helper;
use App\Classes\InterestCalculator;

trait InvestmentManager
{
    static $ANNUM_DAYS = 365;
    static $FULL_TENOR_RATE = true;

    /**
     * Calculate maturity value and interest and assign the values to the object
     *
     * @param float $amountPurchased        The total amount of investment purchased
     *
     * @return Self
     */
    public function calculateMaturity(float $amountPurchased = null): Self
    {
        $unit = $this->unit_purchased ?? 1;
        $tenor = $this->tenor;
        $rate = $this->rate;
        if (!$amountPurchased) {
            if (isset($this->investment_total)) {
                $amountPurchased = $this->investment_total / 100;
            } else {
                $amountPurchased = $this->unit_price * 1;
            }
        }

        if (!$amountPurchased || !$unit || !$tenor || !$rate) {
            return $this;
        }

        $calculation = $this->interest_calculation;
        // The system calculates full tenor rate instead of per day/month/year rate
        $interest = static::$FULL_TENOR_RATE ? $rate / $tenor : $rate;
        $commenceDate = $this->invested_at;

        switch (\strtolower($calculation)) {
            case 'per_day':
                $calculation = InterestCalculator::perDayInterest($amountPurchased, $interest, $tenor);
                break;
            case 'per_month':
                $calculation = InterestCalculator::perMonthInterest($amountPurchased, $interest, $tenor, $commenceDate, 0, false);
                break;
            default:
                $calculation = InterestCalculator::simpleInterest($amountPurchased, $interest, $tenor);
        }

        $this->breakdown = $calculation;
        $this->maturity_interest = $calculation->maturity_interest;
        $this->maturity_value = $calculation->maturity_value;

        $this->days_run = Helper::dateDiff($this->invested_at);
        if ($this->days_run < $calculation->tenor_in_days) {
            $this->maturity_interest_today = $this->days_run * $calculation->daily_interest;
            $this->maturity_value_today = round($amountPurchased + $this->maturity_interest_today, 2);

            $this->is_matured = false;

        } else {
            $this->maturity_interest_today = $this->maturity_interest;
            $this->maturity_value_today = $this->maturity_value;

            $this->is_matured = true;
        }

        return $this;
    }

    /**
     * Check if the investment has matured,
     * mark it as completed and do * the needful.
     *
     * @return Self
     */
    public function checkMaturity(): self
    {
        // If mature_at is still in the past
        // or the investment is already completed
        if ($this->mature_at->gt(now())
            || 'completed' === $this->status) {
            return $this;
        }

        // \Illuminate\Support\Facades\Log::debug("About to complete investment for " . $this->user->email);

        // By now the investment is mature
        // or is not completed yet
        $this->status = 'completed';
        $this->save();

        $this->onCompleted();

        return $this;

    }

    /**
     * Perform actions when the investment is completed
     *
     * @return Self
     */
    protected function onCompleted(): Self
    {
        return $this;

    }
}
