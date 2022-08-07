<?php

namespace App\Models\Base;

use App\Classes\Helper;
use App\Interfaces\Decoratable;
use App\Traits\DbUpdate;
use App\Traits\Logger;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;

/**
 * Should be best refered to as UserBase
 * as it is the base for all users
 */
abstract class User extends Authenticatable implements Decoratable
{
    use HasFactory, HasApiTokens, Notifiable, DbUpdate, Logger;

    const AVATAR_THUMBS = [
        'thumb_350' => [350, 350],
        'thumb_200' => [200, 200],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'password', 'phone',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'name', 'avatar_url',
    ];

    public function getNameAttribute()
    {
        if (isset($this->name) && !empty($this->name)) {
            return $this->name;
        }

        return $this->first_name . " " . $this->last_name;
    }

    public function getNameWithEmailAttribute()
    {

        return $this->name . " - " . $this->email;
    }

    public function scopeActive($q)
    {
        return $q->whereNotNull('email_verified_at')->where('is_blocked', false);
    }

    /**
     * Get the user phone number
     * Instead of using laravel attribute, try mane it a pure fetch
     * without adding relationship to the model
     */
    public function getPhone(): ?string
    {
        return $this->phone ?? null;
    }

    public function getAvatarUrlAttribute()
    {
        $disk = config('filesystems.default');

        if ((!$this->avatar_thumb
            || Storage::disk($disk)->missing($this->avatar_thumb))
            && (!$this->avatar
                || Storage::disk($disk)->missing($this->avatar))
        ) {
            if (Storage::disk($disk)->missing('users/_user_default.svg')) {
                // dd("dks " . Storage::disk($disk)->exists('users/_defaults.png'));
                Storage::disk($disk)->put('users/_user_default.svg', \file_get_contents(resource_path('img/_user_default.svg')));
            }
            return Storage::disk($disk)->url('users/_user_default.svg');
        }

        return Storage::disk($disk)->url($this->avatar_thumb ?? $this->avatar ?? 'users/_user_default.svg');

    }

    /**
     * @override
     *
     * decorate the user class
     *
     * @return Self
     */
    public function decorate()
    {

        $this->append('avatar_url');

        // dd($this->avatar_url);
        return $this;
    }

    /**
     * Change the password of the admin to a temporary one return the password
     *
     * @return string
     */
    public function setTemporaryPassword(): string
    {
        $password = Helper::makeTxnRef(10);
        $this->must_change_password = true;
        $this->password = Hash::make($password);
        $this->save();

        return $password;
    }
}
