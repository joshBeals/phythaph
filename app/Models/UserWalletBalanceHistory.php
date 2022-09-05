<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Classes\Helper;

class UserWalletBalanceHistory extends Model
{
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
