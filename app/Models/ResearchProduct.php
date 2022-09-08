<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResearchProduct extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'features',
        'other_features',
        'prices',
    ];

    protected $casts = [
        'other_features' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
