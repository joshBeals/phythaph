<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\FilterApiController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\PawnController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WalletController;

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
    Route::get('/users', 'FilterApiController@users');
});

Route::post('/option', "CategoryController@options")->name('hide');

Route::get('/categories', "CategoryController@index")->name('categories');
Route::get('/faqs', "FaqController@index")->name('faqs');
Route::get('/subscription-plans', "SubscriptionController@index")->name('subscription_plans');

Route::any('/payment/paystack-webhook', 'PaymentController@handleWebhook')->name('hide');
Route::get('/payment/callback', 'PaymentController@handleGatewayCallback')->name('hide');
Route::any('/transaction/initialize', "PaymentController@initializeTransaction")->name('initialize_txn');
Route::any('/paystack', "PaymentController@paystackApi")->name('hide');;

Route::post('/file-upload/{id?}', "FileController@fileUpload")->name('file_upload');

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
    Route::post('/fund', "WalletController@fundWallet")->name('hide');

    Route::middleware('registration_completion_api')->group(function () {
        Route::prefix('pawn')->group(function () {
            Route::post('/', "PawnController@create")->name('pawn.create');
            Route::get('/', "PawnController@fetchUserPawns")->name('pawn.fetchall');
            Route::get('/{id}', "PawnController@fetchPawn")->name('pawn.fetch');
        });
        Route::prefix('wallet')->group(function () {
            Route::get('/history', "WalletController@index")->name('history');
            Route::get('/withdraw/{amount}', "WalletController@withdrawFunds")->name('withdraw');
        });
    });
    
});