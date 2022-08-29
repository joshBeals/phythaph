<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\SubscriptionPlans;

/**
 * @group Open (No Auth) APIs
 */
class SubscriptionController extends Controller
{
    /**
     * Fetch Subscription Plans
     * 
     *
     *@unauthenticated
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "plans": {
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
            $plans = SubscriptionPlans::all();
            return Helper::apiSuccess(['plans' => $plans]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
