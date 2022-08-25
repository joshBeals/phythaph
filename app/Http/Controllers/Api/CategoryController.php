<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\Category;

/**
 * @group Open (No Auth) APIs
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

    public function options(Request $request)
    {
        $category = Category::where('id', $request->category_id)->first();
        $new_requirements = [];
        foreach($category->requirements as $req){
            if($req['name'] == $request->requirement){
                $temp = [];
                $temp['name'] = $req['name'];
                $temp['field'] = $req['field'];
                $options = explode("|", $req['options']);
                if (!in_array($request->option, $options)){
                    array_push($options, $request->option);
                    $new_option = implode("|", $options);
                    $temp['options'] = $new_option;
                }else{
                    $temp['options'] = $req['options'];
                }
                array_push($new_requirements, $temp);
            }else{
              array_push($new_requirements, $req);
            }
        }
        $category = Category::where('id', $request->category_id)->update(['requirements' => $new_requirements]);
        return redirect(url()->previous());
    }
}
