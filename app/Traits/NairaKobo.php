<?php
namespace App\Traits;

trait NairaKobo
{

    /**
     * Convert naira to kobo
     *
     * @param float $amount     The amount
     *
     * @return float
     */
    public function toKobo(float $amount = null): ?float
    {
        if (!$amount) {
            return $amount;
        }

        return ($amount) * 100;
    }

    /**
     * Convert kobo to naira
     *
     * @param float $amount     The amount
     *
     * @return float
     */
    public function toNaira(float $amount = null): ?float
    {
        if (!$amount) {
            return $amount;
        }

        return ($amount) / 100;
    }

}
