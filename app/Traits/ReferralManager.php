<?php
namespace App\Traits;

use App\User;

trait ReferralManager
{
    public function users()
    {
        return $this->hasMany(User::class, 'referral_code');
    }

    /**
     * Get the banking information of this referral
     *
     * @return null|StdClass
     */
    public function getBankInfo(): ?\StdClass
    {
        $bank = $this->bank()->first();
        if ($bank) {
            return $bank->getInfo();
        }

        return null;
    }
}
