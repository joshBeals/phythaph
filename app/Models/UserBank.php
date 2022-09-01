<?php

namespace App\Models;

use App\Models\Base\Model;
use App\Traits\Bank;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserBank extends Model
{
    use Bank, SoftDeletes;

    public function __construct()
    {
        Parent::__construct();
        $this->entityDescription = "Customer";
        $this->entityRelationshipField = "user_id";

    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A proxy for user relationship
     * used by the user bank trait
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
