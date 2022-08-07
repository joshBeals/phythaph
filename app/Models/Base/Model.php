<?php
namespace App\Models\Base;

use App\Interfaces\Decoratable;
use App\Traits\DbUpdate;
use App\Traits\Logger;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel implements Decoratable
{
    use HasFactory, DbUpdate, Logger;

    /**
     * Quick Crud
     */
    protected $guarded = ['id'];

    /**
     * @override
     *
     * decorate the user class
     *
     * @return Self
     */
    public function decorate()
    {
        return $this;
    }

}
