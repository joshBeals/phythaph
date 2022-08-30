<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\UserWallet;
use App\Models\UserWalletBalanceHistory;
use Carbon\Carbon;

class WalletController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $user->decorate();
        // dd($user->wallets);
        $walletHistory = UserWalletBalanceHistory::where('user_id', $user->id)->orderBy('id', 'DESC')->paginate(5);
        return view('account.kyc.wallet', compact('user', 'walletHistory'));
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

    public function withdrawFunds(Request $request, $amount){
        $user = $request->user();

        $save = $user->withdraw('ngn', floatval($amount));

        return redirect(url()->previous());
    }
}
