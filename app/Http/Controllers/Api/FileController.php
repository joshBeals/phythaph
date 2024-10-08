<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use Illuminate\Support\Facades\Storage;
use App\Models\Files;
use App\Models\UserPawns;
use App\Models\PawnFiles;
use App\Models\UserSells;
use App\Models\SellFiles;

/**
 * @group Pawn APIs
 */
class FileController extends Controller
{

     /**
     * Upload file
     * 
     * The title parameter can either be ('image' or 'file')
     *
     *@bodyParam file file required
     *@bodyParam title string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "File Uploaded Successfully",
     *    }
     *@response status=400 scenario="Failure" {
     *    "success": false,
     *    "message": "No file uploaded"
     *  }
     */
    public function fileUpload(Request $request, $id = null){
        try {
            if (!$request->has('file')) {
                return Helper::apiFail('No file uploaded');
            }

            $pawn = UserPawns::find($id);

            if(!$pawn){
                return Helper::apiFail('No file uploaded');
            }

            UserPawns::where('id', $id)->update(['status'=>'documents received']);

            $title = 'others';

            if ($request->has('title')) {
                $title = $request->input('title');
            }

            $path = false;
            $disk = config('filesystems.default');
            $fileSize = '';
            $mime = '';

            $data = Storage::putFile('pawn_uploads', $request->file('file'));
            $path = Storage::disk($disk)->url($data);
            $fileSize = Storage::size($data);
            $mime = Storage::mimeType($data);

            if (!$path) {
                return Helper::apiFail('Could not upload file');
            }

            // Store the path to the database
            $file = new Files;
            $file->disk_name = $disk;
            $file->file_path = $path;
            $file->file_size = $fileSize;
            $file->content_type = $mime;
            $file->title = $title;
            $file->description = '';
            $file->is_public = false;

            if (!$file->save()) {
                return Helper::apiFail('No file uploaded');
            }

            if (PawnFiles::create([
                'pawn_id' => $pawn->id,
                'file_id' => $file->id,
            ])) {
                return Helper::apiSuccess('File Uploaded Successfully');
            } else {
                return Helper::apiFail('No file uploaded');
            }

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

     /**
     * Upload sell file
     * 
     * The title parameter can either be ('image' or 'file')
     *
     *@bodyParam file file required
     *@bodyParam title string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "File Uploaded Successfully",
     *    }
     *@response status=400 scenario="Failure" {
     *    "success": false,
     *    "message": "No file uploaded"
     *  }
     */
    public function sellUpload(Request $request, $id = null){
        try {
            if (!$request->has('file')) {
                return Helper::apiFail('No file uploaded');
            }

            $sell = UserSells::find($id);

            if(!$sell){
                return Helper::apiFail('No file uploaded');
            }

            UserSells::where('id', $id)->update(['status'=>'documents received']);

            $title = 'others';

            if ($request->has('title')) {
                $title = $request->input('title');
            }

            $path = false;
            $disk = config('filesystems.default');
            $fileSize = '';
            $mime = '';

            $data = Storage::putFile('sell_uploads', $request->file('file'));
            $path = Storage::disk($disk)->url($data);
            $fileSize = Storage::size($data);
            $mime = Storage::mimeType($data);

            if (!$path) {
                return Helper::apiFail('Could not upload file');
            }

            // Store the path to the database
            $file = new Files;
            $file->disk_name = $disk;
            $file->file_path = $path;
            $file->file_size = $fileSize;
            $file->content_type = $mime;
            $file->title = $title;
            $file->description = '';
            $file->is_public = false;

            if (!$file->save()) {
                return Helper::apiFail('No file uploaded');
            }

            if (SellFiles::create([
                'sell_id' => $sell->id,
                'file_id' => $file->id,
            ])) {
                return Helper::apiSuccess('File Uploaded Successfully');
            } else {
                return Helper::apiFail('No file uploaded');
            }

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
