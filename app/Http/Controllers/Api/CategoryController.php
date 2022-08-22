<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\Category;

/**
 * @group Category APIs
 */
class CategoryController extends Controller
{
    /**
     * Fetch Categories
     * 
     * Get all categories
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "categories": {
     *           ...
     *       }
     *    }
     *@response status=404 scenario="User not found" {
     *    "success": false,
     *    "message": "User not found"
     *  }
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $categories = Category::all();
            return Helper::apiSuccess(['categories' => $categories]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
