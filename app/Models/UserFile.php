<?php

namespace App\Models;

use App\Models\Base\Model;

class UserFile extends Model
{
    protected $fillable = ['user_id', 'file_type_id', 'file_id'];
}
