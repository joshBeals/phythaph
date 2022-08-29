<?php
namespace App\Classes;

use App\Models\User;
use App\Models\UserBank;
use App\Models\UserPayoutRequest;
use App\Traits\WalletManager;
use Carbon\Carbon;

/**
 * @todo move this functionality to App\UserWallet
 */
class Wallet
{
    use WalletManager;

    public static function withdraw(
        User $user,
        string $wallet,
        float $amount,
        UserBank $bank = null,
        string $note = null,
        Carbon $date = null
    ): ?float{
        $tmp = (new self);

        $tmp->user = $user;
        $tmp->wallet_name = $wallet;

        $newBalance = $tmp->withdrawFromWallet($amount, $note);

        $payoutRequest = UserPayoutRequest::create([
            'amount' => $amount,
            'disburse_amount' => $amount,
            'user_id' => $user->id,
            'entity_id' => null,
            'penalty' => null,
            'note' => $note,
            'source' => 'wallet',
            'wallet' => $wallet,
            'bank_id' => $bank && $bank->id ? $bank->id : null,
        ]);

        $payoutRequest->wallet = $wallet;

        if ($date) {
            $payoutRequest->created_at = $date;
        }
        $payoutRequest->save();

        return $newBalance;

    }

}
