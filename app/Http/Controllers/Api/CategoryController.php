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
     *
     *@unauthenticated
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "categories": {
     *           ...
     *       }
     *    }
     *@response status=400 scenario="Error" {
     *    "success": false,
     *    "message": "Error"
     *  }
     */
    public function index(Request $request)
    {
        try {
            $categories = Category::all();
            return Helper::apiSuccess(['categories' => $categories]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
