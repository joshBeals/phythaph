<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\FilterApiController;
use App\Http\Controllers\Api\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('filter')->group(function () {
    Route::get('/currencies', 'FilterApiController@currencies');
});

Route::prefix('auth')->group(function () {
    Route::post('register', "AuthController@register")->name('auth.register');
    Route::post('login', "AuthController@login")->name('auth.login');
    Route::get('email/verify/{id}', 'VerificationController@verify')->name('verification.verify');
    Route::get('email/resend/{id}', 'VerificationController@resend')->name('verification.resend');
    Route::post('password/forgot-password','ForgotPasswordController@sendResetLinkResponse')->name('passwords.sent');
    Route::post('password/reset', 'ForgotPasswordController@sendResetResponse')->name('passwords.reset');
});

Route::group([
    'middleware' => ['auth.api'],
], function () {
    Route::post('setup', "AuthController@saveSetup")->name('setup');
    Route::get("/auth/logout", "AuthController@logout")->name('auth.logout');

    Route::get('/user', "AuthController@getUser")->name('user');
    Route::get('/me', "AuthController@me")->name('user_detail');

    Route::middleware('registration_completion_api')->group(function () {
        Route::get('/categories', "CategoryController@index")->name('categories');
    });
    
});