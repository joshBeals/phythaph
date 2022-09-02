<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Classes\Helper;

class UserSells extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, SoftDeletes;

    protected $hidden = [
        'deleted_at',
    ];

    public function User()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function sell_files()
    {
        return $this->hasMany(PawnFiles::class, 'pawn_id')->orderBy('id', 'DESC');
    }

    public function getCustomerNameAttribute()
    {
        return $this->user()->first()->name;
    }

    public function getFiles()
    {
        $files = $this->sell_files;

        if (!$files) {
            return [];
        }

        foreach ($files as $file) {
            $file->detail = Files::where(['id' => $file->file_id])->get();
        }

        return $files;
    }

    public function decorate()
    {

        Parent::decorate();

        $this->category_name = $this->category->name ?? '';
        $this->sell_files = $this->getFiles();

        foreach ([
            'created_at',
            'updated_at',
        ] as $date) {
            $this->{"_" . $date} = Helper::formatDate($this->{$date});
        }

        return $this;

    }
}
