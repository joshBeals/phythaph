<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('customer', 'UserCrudController');
    Route::crud('admin', 'AdminCrudController');
    Route::crud('category', 'CategoryCrudController');
    Route::crud('research-product', 'ResearchProductCrudController');
    Route::crud('currency', 'CurrencyCrudController');
    Route::crud('faq', 'FaqCrudController');
    Route::crud('user-pawns', 'UserPawnsCrudController');
    
    Route::crud('subscription-plans', 'SubscriptionPlansCrudController');
    Route::crud('transaction', 'TransactionCrudController');
    
    Route::post('customer/{user}/update-plan', 'UserCrudController@subscribe')->name('backoffice.plan.subscription');
    Route::post('customer/{user}/fund', 'UserCrudController@fundWallet')->name('backoffice.plan.wallet');
    Route::post('customer/{user}/withdraw', 'UserCrudController@fundWallet')->name('backoffice.plan.wallet');
}); // this should be the absolute last line of this file