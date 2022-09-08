<?php

namespace App\Models;

use App\Classes\Helper;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Base\User as UserBase;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\SubscriptionManager;
use App\Traits\WalletManager;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends UserBase implements MustVerifyEmail, JWTSubject
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use SoftDeletes;
    use SubscriptionManager;
    use WalletManager;

    public function sendPasswordResetNotification($token) {
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token));
    }
    
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    } 

    /**
     * Check of the user has completed their registration
     *
     * @return bool
     */
    public function completedRegistration(): bool
    {
        return $this->getPhone() ? true : false;
    }  

    public function Pawns()
    {
        return $this->hasMany(UserPawns::class)->orderBy('id', 'DESC');
    }

    public function bank()
    {
        return $this->hasOne(UserBank::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function decorate()
    {
        Parent::decorate();
        
        $this->has_valid_subscription = $this->hasValidSubscription();
        $this->subscription_expires_in = $this->geSubscriptiontDaysToExpire();
        $this->subscription_expires_soon = $this->subscriptionExpiringSoon();
        $this->has_subscribed_once = $this->hasSubscribedOnce();
        $this->walletBalance = UserWallet::getWalletBalaceForUserFormated('ngn', $this);
        $this->walletBalanceNumber = UserWallet::getWalletBalaceForUser('ngn', $this);
        $this->totalPawned = UserPawns::getTotalPawned($this);

        foreach ([
            'created_at',
            'updated_at',
        ] as $date) {
            $this->{"_" . $date} = Helper::formatDate($this->{$date});
        }

        return $this;
    }

    public function withdraw(
        string $wallet,
        float $amount,
        UserBank $bank = null,
        string $note = null,
        Carbon $date = null
    ): ?float{
        
        $user = $this;

        $bank = UserBank::where('user_id', $user->id)->first();
        
        $newBalance = $this->withdrawFromWallet($amount, $note);

        $payoutRequest = UserPayoutRequest::create([
            'amount' => $amount,
            'disburse_amount' => $amount,
            'user_id' => $user->id,
            'entity_id' => 1,
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
