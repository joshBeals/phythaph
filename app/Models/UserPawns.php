<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPawns extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, SoftDeletes;

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function pawn_files()
    {
        return $this->hasMany(PawnFiles::class, 'pawn_id')->orderBy('id', 'DESC');
    }

    public function getCustomerNameAttribute()
    {
        return $this->user()->first()->name;
    }

    public function getFiles()
    {
        $files = $this->pawn_files;

        if (!$files) {
            return [];
        }

        foreach ($files as $file) {
            $file->detail = Files::where(['id' => $file->file_id])->get();
        }

        return $files;
    }

}
