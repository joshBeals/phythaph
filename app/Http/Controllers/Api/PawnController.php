<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use App\Models\UserPawns;

/**
 * @group Pawn APIs
 */
class PawnController extends Controller
{
     /**
     * Pawn item
     * 
     *Make a new pawn request
     *
     *@bodyParam category_id string required
     *@bodyParam item_features json required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Request successful",
     *    "data": {
     *       "pawn": {...}
     *    }
     *@response status=400 scenario="Failure" {
     *    "success": false,
     *    "message": "Pawn Request Failed"
     *  }
     */
    public function create(Request $request){
        try {
            $validator = validator()->make($request->all(), [
                'category_id' => 'required',
                'item_features' => 'required',
            ]);

            if ($validator->fails()) {
                return Helper::apiFail($validator->errors()->first());
            }

            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $pawn = UserPawns::create([
                'category_id' => $request->category_id,
                'user_id' => $user->id,
                'item_features' => $request->item_features,
            ]);

            if (!$pawn) {
                return Helper::apiFail("Pawn Request Failed");
            }


            return Helper::apiSuccess(['pawn' => $pawn]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

    /**
     * Fetch User Pawns
     *
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "pawns": {
     *           ...
     *       }
     *    }
     *@response status=404 scenario="Error" {
     *    "success": false,
     *    "message": "Error"
     *  }
     */
    public function fetchUserPawns(Request $request)
    {
        try {
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $pawns = UserPawns::where('user_id', $user->id)->get();

            return Helper::apiSuccess(['pawns' => $pawns]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }    $user = $request->user();
        
    }

    /**
     * Fetch Single Pawn Item
     *
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "pawn": {
     *           ...
     *       }
     *    }
     *@response status=404 scenario=Error" {
     *    "success": false,
     *    "message": Error"
     *  }
     */
    public function fetchPawn(Request $request, $id)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $pawn = UserPawns::where(['id' => $id, 'user_id' => $user->id])->first();

            return Helper::apiSuccess(['pawn' => $pawn]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
