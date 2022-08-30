<?php

namespace App\Traits;

use App\Models\SubscriptionPlans;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscriptionHistory;
use Carbon\Carbon;

trait SubscriptionManager
{

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlans::class, 'plan_id');
    }

    public function subscription()
    {
        return $this->belongsTo(UserSubscriptionHistory::class, 'subscription_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscriptionHistory::class)->orderBy('id', 'DESC');
    }

    /**
     * Add subscription to a user
     *
     * @param Transaction|null $transaction     The transaction for the payment of the subscription
     * @param int|null $year     Number of years
     * @param Carbon|null $from  The date to begin the subscription from
     * @param SubscriptionPlans|null $plan    The subscription plan;
     *
     * @return Self Return the new model
     */
    public function addSubscription(
        Transaction $transaction = null,
        float $years = 1,
        Carbon $from = null,
        SubscriptionPlans $plan = null
    ): Self {

        if (!$from) {
            $from = now();
        }

        $transaction_id = null;

        if ($transaction) {
            $transaction_id = $transaction->id;
        }

        $plan_id = $this->plan_id;

        if ($plan) {
            $plan_id = $plan->id;
        }

        $currentSubscription = $this->subscription()->first();

        // The person still have sub
        // if ($currentSubscription && now()->lt($currentSubscription->to)) {
        //     $from = $currentSubscription->to;
        // }

        $to = clone $from;

        $to->addMonth($years * 12);

        $sub = UserSubscriptionHistory::create([
            'user_id' => $this->id,
            'plan_id' => $plan_id,
            'transaction_id' => $transaction_id,
            'from' => $from,
            'to' => $to,
        ]);

        if ($sub) {
            $this->plan_id = $plan_id;
            $this->subscription_id = $sub->id;

            $this->dbUpdate([
                'plan_id' => $plan_id,
                'subscription_id' => $sub->id,
            ]);

            $this->subscription = $sub;
        }

        // if ($transaction) {
        //     $transaction->entity_id = $this->id;
        //     $transaction->save();
        // }

        if($this->referred_by){
            $referred = User::where('id', $this->referred_by)->first();
            
            if($this->has_subscribed_once){
                $amount = floatval(($plan->renewal_fee / 100) * 10);
            }else{
                $amount = floatval(($plan->signon_fee / 100) * 10);
            }
        
            $obj = new \StdClass;

            $obj->description = "Referral Bonus";
            $obj->user_id = $referred->id;
            $obj->amount = $amount;
            $obj->type = 'customer_charge';
            $obj->scope = 'customer_charge';

            $txn = Transaction::initialize(floatval($amount), $obj);

            $save = $referred->depositToWallet(floatval($amount), $obj->description, $txn);
        }

        return $this;
    }

    /**
     * Process gateway response after a user makes subscription payment
     *
     * @param Object $paymentDetails The details of payment
     * @return Self
     */
    public function processSubscriptionPayment(
        Object $paymentDetails,
        Transaction $txnH,
        Object $meta = null
    ): Self {

        if (!$meta) {
            $meta = $paymentDetails->data->metadata;
        }

        $plan = $meta->plan_id ?? null;

        if (!$plan) {
            return $this;
        }

        $plan = SubscriptionPlans::findOrFail($plan);
        $currentSubscription = $this->subscription()->first();

        if ($meta->scope == "user_plan_change") {
            $this->plan_id = $plan->id;

            $this->dbUpdate([
                'plan_id' => $plan->id,
            ]);

            $currentSubscription = $this->subscription()->first();

            $currentSubscription->plan_id = $plan->id;
            $currentSubscription->save();

        } else {
            $from = now();
            // $newUser = (bool) $meta->new_user ?? null;
            // if (!$newUser && $currentSubscription) {
            if ($currentSubscription) {
                $from = Carbon::parse($this->subscription->to);
            }
            $this->addSubscription($txnH, (int) $meta->years, $from, $plan);
        }

        return $this;

    }

    /**
     * Check if a user has a valid subsciption at the moment
     *
     * @return bool
     */
    public function hasValidSubscription(): bool
    {
        $sub = $this->subscription()->first();

        if (!$sub) {
            return false;
        }

        return Carbon::now()->lt($sub->to);
    }

    /**
     * Check if a user subscription is expiring soon
     *
     * @return int
     */
    public function geSubscriptiontDaysToExpire(): int
    {

        if (!$this->hasValidSubscription()) {
            return 0;
        }

        $sub = $this->subscription()->first();

        return Carbon::now()->diffInDays($sub->to);
    }

    /**
     * Check if a user subscription is expiring soon
     *
     * @return bool
     */
    public function subscriptionExpiringSoon(): bool
    {
        return $this->geSubscriptiontDaysToExpire() < 30;
    }

    /**
     * Check if the user has ever subscribed before
     *
     * @return bool
     */
    public function hasSubscribedOnce(): bool
    {
        return !!$this->subscriptions()->count();
    }
}
