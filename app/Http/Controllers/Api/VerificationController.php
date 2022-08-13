<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\User;

/**
 * @group Auth APIs
 */
class VerificationController extends Controller
{

    /**
     * Email Verification
     */
    public function verify($user_id, Request $request) {
        try {
            if (!$request->hasValidSignature()) {
                return Helper::apiFail("Invalid/Expired url provided.");
                // return redirect(config('app.frontend_url') . '/email/verify/fail');
            }
        
            $user = User::findOrFail($user_id);
        
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        
            return Helper::apiSuccess('Email verification successful!');
            // return redirect(config('app.frontend_url') . '/email/verify/success');
        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
        
    }
    
    /**
     * Resend Verification Email
     *
     *
     *@unauthenticated
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Email verification link sent on your email!",
     *    }
     *@response status=400 scenario="Email already verified!" {
     *    "success": false,
     *    "message": "Email already verified!"
     *  }
     */
    public function resend($user_id, Request $request) {
        try {
            
            $user = User::findOrFail($user_id);

            if ($user->hasVerifiedEmail()) {
                return Helper::apiFail("Email already verified!");
            }
        
            $user->sendEmailVerificationNotification();
        
            return Helper::apiSuccess("Email verification link sent on your email!");
        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
