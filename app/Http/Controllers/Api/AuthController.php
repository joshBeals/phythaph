<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Classes\GlobalVars;
use App\Classes\Helper;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Classes\RandomGenerator;

/**
 * @group Auth APIs
 */
class AuthController extends Controller
{
    /**
     * Register new user
     *
     *
     *@unauthenticated
     *@bodyParam first_name string required
     *@bodyParam last_name string required
     *@bodyParam email string required
     *@bodyParam password string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Registration Successful",
     *    "data": {
     *       [UserData]
     *    }
     *@response status=400 scenario="Registration Failed" {
     *    "success": false,
     *    "message": "Registration Failed"
     *  }
     */
    public function register(Request $request)
    {
        try {
            $validator = validator()->make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', Rules\Password::defaults()],
            ]);

            if ($validator->fails()) {
                return Helper::apiFail($validator->errors()->first());
            }
            
            $referrer = null;

            if($request->code){
                $getUser = User::where('referral_code', $request->code)->first();
                if($getUser){
                    $referrer = $getUser->id;
                }
            }
    
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'referral_code' => RandomGenerator::getHashedToken(8),
                'referred_by' => $referrer
            ]);

            event(new Registered($user));
            // $user->sendEmailVerificationNotification();

            $token = JWTAuth::fromUser($user);

            return Helper::apiSuccess(['user' => $user->decorate(), 'token' => $token], 'Registration Successful');
        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

    
    /**
     * User Login
     *
     *
     *@unauthenticated
     *@bodyParam email string required
     *@bodyParam password string required
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Login Successful",
     *    "data": {
     *       "user": {
     *           ...
     *       },
     *       "token": jwt_token
     *    }
     *@response status=400 scenario="Invalid Credentials" {
     *    "success": false,
     *    "message": "Invalid Credentials"
     *  }
     */
    public function login(Request $request)
    {
        try {
            if (!Auth::guard('api')->attempt($request->only('email', 'password'))) {
                return Helper::apiFail('Invalid Credentials');
            }
            $user = Auth::guard('api')->user();

            $token = JWTAuth::fromUser($user);

            $cookie = cookie('jwt', $token, 60 * 24); // Cookie will las 1 day (60 secs * 24)

            $user->decorate();

            if(!$user->referral_code){
                User::where('id', $user->id)->update(['referral_code' => RandomGenerator::getHashedToken(8)]);
            }

            return Helper::apiSuccess(['user' => $user, 'token' => $token], 'Login Successful');
        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }

    }

    /**
     * Registration Setup
     * 
     * Complete user registration.
     *
     *@bodyParam account_type string optional
     *@bodyParam phone string optional
     *@bodyParam gender string optional
     *@bodyParam birthday date optional
     *@bodyParam address string optional
     *@bodyParam house_number string optional
     *@bodyParam street string optional
     *@bodyParam lga string optional
     *@bodyParam lcda string optional
     *@bodyParam company_name string optional
     *@bodyParam company_phone string optional
     *@bodyParam country string optional
     *@bodyParam postal_code string optional
     *@bodyParam rc_number string optional
     *@bodyParam city string optional
     *@bodyParam state string optional
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Setup Complete!",
     *    "data": {
     *       "user": {
     *           ...
     *       }
     *    }
     */
    public function saveSetup(Request $request)
    {
        try {
            if ($request->input('birthday')) {

                $date = strtotime($request->input('birthday'));

                if (!$date || is_null($date) || $date == '') {
                    return Helper::apiFail("Invalid birthday (format yyyy-mm-dd)");
                }

                $birthday = date('Y-m-d', $date);

                $inputs = $request->except(['_token', 'birthday']);

            }
            $inputs = $request->except(['_token']);

            $user = $request->user();

            foreach ($inputs as $key => $value) {
                $user->{$key} = $value;
            }

            if ($request->input('birthday')) {
                $user->birthday = $birthday;
            }

            $user->save();

            return Helper::apiSuccess($user, 'Setup Complete!');

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }

    }

    /**
     * User Data
     * 
     * Get user data
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "user": {
     *           ...
     *       }
     *    }
     *@response status=404 scenario="User not found" {
     *    "success": false,
     *    "message": "User not found"
     *  }
     */
    public function getUser(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $user->decorate();
            return Helper::apiSuccess(['user' => $user]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

    /**
     * User Data
     *
     * Get user data.
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "",
     *    "data": {
     *       "user": {
     *           ...
     *       }
     *    }
     *@response status=404 scenario="User not found" {
     *    "success": false,
     *    "message": "User not found"
     *  }
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return Helper::apiFail("User not found", 404);
            }

            $user->decorate();
            return Helper::apiSuccess(['user' => $user]);

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }

    /**
     * User Logout
     *
     *
     *@response status=200 scenario=Ok {
     *    "success": true,
     *    "message": "Logout Successful"
     *  }
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::getToken(); // Ensures token is already loaded.
            JWTAuth::invalidate(true);
            return Helper::apiSuccess("Logout Successful");

        } catch (\Throwable $th) {
            return Helper::apiException($th);
        }
    }
}
