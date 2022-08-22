<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Base\User as UserBase;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends UserBase implements MustVerifyEmail, JWTSubject
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use SoftDeletes;

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

    public function decorate()
    {

        Parent::decorate();

        return $this;

    }

    public function Pawns()
    {
        return $this->hasMany(UserPawns::class)->orderBy('id', 'DESC');
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
}
