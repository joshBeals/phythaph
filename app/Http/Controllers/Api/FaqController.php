<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\Faq;

/**
 * @group Category APIs
 */
class FaqController extends Controller
{
    /**
     * Fetch Faqs
     * 
     *
     *@unauthenticated
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "faqs": {
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
            $faqs = Faq::all();
            return Helper::apiSuccess(['faqs' => $faqs]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
