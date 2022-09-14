<?php

namespace App\Models;

use App\Classes\Helper;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class UserSubscriptionHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'plan_id',
        'transaction_id',
        'from',
        'to',
    ];

    protected $dates = [
        'from', 'to',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlans::class, 'plan_id');
    }

    public function getDateAttribute()
    {
        return Helper::readableDate($this->created_at);
    }
    
    public function decorate()
    {
        $this->_from = Helper::formatDate($this->from);
        $this->_to = Helper::formatDate($this->to);
        $this->plan = $this->plan()->first();
        $this->transaction = $this->transaction()->first();
        if ($this->plan) {
            $this->plan->decorate();
        }
        if ($this->transaction) {
            $this->transaction->decorate();
        }

        return $this;
    }

}