<?php

namespace App\Models;

use App\Models\Base\Model;

class UserWalletBalanceHistory extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(UserWallet::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        // 'previous_balance',
        'amount',
        // 'new_balance',
        'description',
        'transaction_id',
    ];

}
