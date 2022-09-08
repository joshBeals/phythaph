<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
/**
 * @group Customer APIs
 */
class ReferralController extends Controller
{
    /**
     * Referral Details
     *
     * Get details of a user's referalls.
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message",
     *    "data": {
     *       ...
     *    }
     *@response status=400 {
     *    "success": false,
     *    "message"
     *  }
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $all = User::where('referred_by', $user->id)->get();
            $referrals = User::where('referred_by', $user->id)->get();
            $totalsignups = User::where('referred_by', $user->id)->count();
            
            $totalsubscribed = 0;
            $rewardAmount = 0;

            foreach($all as $a){
                $a->decorate();
                if($a->has_valid_subscription){
                    $rewardAmount += (($a->subscription->plan->signon_fee / 100) * 10);
                    $totalsubscribed++;
                }
            }
            return Helper::apiSuccess(['user' => $user, 'referrals' => $referrals, 'totalsubscribed' => $totalsubscribed, 'rewardAmount' => $rewardAmount], 'Referral details gotten successfully!');
        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
