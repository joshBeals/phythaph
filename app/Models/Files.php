<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Base\Model;
use Illuminate\Support\Facades\Storage;

class Files extends Model
{
    use HasFactory;

    public function pawn_files()
    {
        return $this->belongsTo(PawnFiles::class, 'file_id');
    }

    public function sell_files()
    {
        return $this->belongsTo(SellFiles::class, 'file_id');
    }
}
