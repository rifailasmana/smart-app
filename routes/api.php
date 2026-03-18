<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

use App\Http\Controllers\BillingController;
use App\Http\Controllers\TableManagementController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Table Management Routes
    Route::post('/tables/move', [TableManagementController::class, 'moveTable']);
    Route::post('/tables/merge', [TableManagementController::class, 'mergeTables']);

    // Billing Routes
    Route::post('/billing/split', [BillingController::class, 'splitBill']);
    Route::post('/billing/coupon', [BillingController::class, 'applyCoupon']);
});
