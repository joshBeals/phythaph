<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, SoftDeletes;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'type',
        'requirements',
    ];

    protected $casts = [
        'requirements' => 'array',
    ];

    public function research_produsts()
    {
        return $this->hasMany(ResearchProduct::class);
    }
}
