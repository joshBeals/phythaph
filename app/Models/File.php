<?php

namespace App\Models;

use App\Models\Base\Model;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    public static function deleteById($id)
    {
        $file = Self::findOrFail($id);
        Storage::disk($file->disk_name)->delete($file->file_path);
        $file->delete();
    }

    public static function getFileById($file_id)
    {
        $path = Self::find($file_id);
        if (!$path) {
            return false;
        }

        $file = new \StdClass;
        $file->title = $path->title;
        $file->date = $path->created_at->format(config('date.formats.simple'));
        $file->description = $path->description;
        $file->size = $path->file_size;
        $file->content_type = $path->content_type;
        if ($path->is_public) {
            $file->path = Storage::url($path->file_path);
        } else {
            if ($path->disk_name === 'local') {
                $file->path = Storage::disk($path->disk_name)->url($path->file_path);
            } else {
                $file->path = Storage::disk($path->disk_name)->temporaryUrl($path->file_path, now()->addMinutes(30));
            }

        }

        return $file;
    }
}
