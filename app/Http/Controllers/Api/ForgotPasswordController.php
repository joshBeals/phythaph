<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

/**
 * @group Auth APIs
 */
class ForgotPasswordController extends Controller
{
    /**
     * Send Reset Password Link
     *
     *
     *@unauthenticated
     *@bodyParam email string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Mail sent successfully",
     *    }
     *@response status=400 scenario="Error Message" {
     *    "success": false,
     *    "message": "Error Message"
     *  }
     */
    public function sendResetLinkResponse(Request $request) {
        $input = $request->only('email');

        $validator = validator()->make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return Helper::apiFail($validator->errors()->first());
        }

        $response =  Password::sendResetLink($input);

        if($response == Password::RESET_LINK_SENT){
            $message = "Mail sent successfully";
        }else{
            $message = "Email could not be sent to this email address";
        }

        //$message = $response == Password::RESET_LINK_SENT ? 'Mail send successfully' : GLOBAL_SOMETHING_WANTS_TO_WRONG;

        return Helper::apiSuccess($message);
    }

    /**
     * Reset Password
     *
     *
     *@unauthenticated
     *@bodyParam email string required
     *@bodyParam token string required
     *@bodyParam password string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Password reset successfully",
     *    }
     *@response status=400 scenario="Error Message" {
     *    "success": false,
     *    "message": "Error Message"
     *  }
     */
    public function sendResetResponse(Request $request) {
        $input = $request->only('email','token', 'password');

        $validator = validator()->make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return Helper::apiFail($validator->errors()->first());
        }

        $response = Password::reset($input, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password)])->save();
            //$user->setRememberToken(Str::random(60));
            event(new PasswordReset($user));
        });
            
        if($response == Password::PASSWORD_RESET){
            $message = "Password reset successfully";
        }else{
            $message = "Email could not be sent to this email address";
        }

        return Helper::apiSuccess($message);
    }
}
