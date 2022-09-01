<?php

namespace App\Models;

use App\Models\Base\Model;

class UserBankRecipientCode extends Model
{
    public function bank()
    {
        return $this->belongsTo(UserBank::class, 'bank_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
