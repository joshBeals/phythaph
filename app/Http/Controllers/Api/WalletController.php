<?php

namespace App\Http\Controllers\Api;

use App\Classes\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Models\UserWalletBalanceHistory;
use Carbon\Carbon;

/**
 * @group Wallet APIs
 */
class WalletController extends Controller
{
    /**
     * Fetch Wallet History
     *
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Request successful",
     *    "data": {
     *          "walletHistory": {...}
     *      }
     *  }
     *@response status=404 scenario=Error" {
     *    "success": false,
     *    "message": Error"
     *  }
     */
    public function index(Request $request){
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $walletHistory = UserWalletBalanceHistory::where('user_id', $user->id)->orderBy('id', 'DESC');

            return Helper::apiSuccess(['walletHistory' => $walletHistory]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

    public function fundWallet(Request $request){
        $user = $request->user();
        
        $obj = new \StdClass;

        $amount = $request->amount;

        $obj->description = "Wallet Topup";
        $obj->user_id = $user->id;
        $obj->amount = $amount;
        $obj->type = 'wallet_topup';
        $obj->scope = 'wallet_topup';

        $txn = Transaction::initialize(floatval($amount), $obj);

        $save = $user->depositToWallet($amount, $obj->description, $txn);

        return redirect(url()->previous());
    }

    /**
     * Withdraw Funds
     *
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Withdrawal successful"
     *  }
     *@response status=404 scenario=Error" {
     *    "success": false,
     *    "message": Error"
     *  }
     */
    public function withdrawFunds(Request $request, $amount){
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $save = $user->withdraw('ngn', floatval($amount));

            return Helper::apiSuccess("Withdrawal successful");

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
