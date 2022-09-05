<?php

namespace App\Models;

use App\Classes\Helper;
use App\Exceptions\NotAWalletTypeException;
use App\Models\Base\Model;
use App\Traits\WalletManager;

/**
 * Wallet balance is in Kobo
 *
 * A user can have more than one balance specified by account_type
 *
 * account_type:
 * savings_naira,investment
 */
class UserWallet extends Model
{

    /**
     * @var ACCOUNT_TYPES   The types of wallet account available at the moment
     */
    const ACCOUNT_TYPES = [
        'ngn', 'usd', 'gbp', 'aed'
    ];

    /**
     * @var ACCOUNT_OPERATIONS   Operations that can be performed on accounts
     */
    const ACCOUNT_OPERATIONS = [
        'deposit', 'withdrawal', 'others',
    ];

    protected $dates = [
        'last_deposit_at', 'last_withdrawal_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the balance/value of a wallet for a user;
     *
     * @param string $wallet        The wallet in question
     */
    public static function getWalletBalaceForUser(string $wallet, User $user = null): float
    {

        // if (!in_array($wallet, self::ACCOUNT_TYPES)) {
        //     throw new NotAWalletTypeException;
        // }

        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return 0;
        }

        $get = Self::where('account_type', $wallet)
            ->where('user_id', $user->id)->first();

        return $get ? $get->balance / 100 : 0;

    }

    public static function getWalletBalaceForUserFormated(string $wallet, User $user = null): string
    {

        // if (!in_array($wallet, self::ACCOUNT_TYPES)) {
        //     throw new NotAWalletTypeException;
        // }

        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return 0;
        }

        $get = Self::where('account_type', $wallet)
            ->where('user_id', $user->id)->first();

        return $get ? Helper::formatToCurrency($get->balance / 100) : Helper::formatToCurrency(0);

    }

    /**
     * Get the id of a wallet for a user;
     *
     * @param string $wallet        The wallet in question
     */
    public static function getWalletInfoForUser(string $wallet, User $user = null): ?Self
    {

        // if (!in_array($wallet, self::ACCOUNT_TYPES)) {
        //     throw new NotAWalletTypeException;
        // }

        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return null;
        }

        $get = Self::where('account_type', $wallet)
            ->where('user_id', $user->id)->first();

        return $get ?? null;

    }

    public function decorate()
    {

        Parent::decorate();

        foreach ([
            'created_at',
            'updated_at',
        ] as $date) {
            $this->{"_" . $date} = Helper::formatDate($this->{$date});
        }

        return $this;

    }

}
