<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ForgotPasswordController;

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

Route::prefix('auth')->group(function () {
    Route::post('register', "AuthController@register")->name('auth.register');
    Route::post('login', "AuthController@login")->name('auth.login');
    Route::get('email/verify/{id}', 'VerificationController@verify')->name('verification.verify');
    Route::get('email/resend/{id}', 'VerificationController@resend')->name('verification.resend');
    Route::post('password/forgot-password','ForgotPasswordController@sendResetLinkResponse')->name('passwords.sent');
    Route::post('password/reset', 'ForgotPasswordController@sendResetResponse')->name('passwords.reset');
});
