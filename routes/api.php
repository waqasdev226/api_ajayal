<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/test', function () {
    return response()->json([
        'status' => true,
        'message' => 'API working fine 🚀'
    ]);
});
Route::post('register', [\App\Http\Controllers\AuthController::class,'register']);
Route::post('registerOTP/{reference}', [\App\Http\Controllers\AuthController::class,'checkOTP']);
Route::post('login', [\App\Http\Controllers\AuthController::class,'login'])->name('login');
Route::post('checkOTP/{reference}', [\App\Http\Controllers\AuthController::class,'loginOTP']);



Route::post('forgot-password', [\App\Http\Controllers\AuthController::class,'forgotPassword'])->name('password.forgot');
Route::post('resend-otp', [\App\Http\Controllers\AuthController::class,'resendOTP'])->name('otp.resend');
Route::post('reset-otp', [\App\Http\Controllers\AuthController::class,'resetOTP'])->name('otp.request');
Route::post('reset-password', [\App\Http\Controllers\AuthController::class,'resetPassword'])->name('password.reset');



Route::post('refreshOTP/{reference}', [\App\Http\Controllers\AuthController::class,'refreshOTP']);

Route::middleware('jwt.verify')->group( function () {

    Route::prefix('auth')->group(function () {

        Route::post('logout', [\App\Http\Controllers\AuthController::class,'logout']);
        Route::post('refresh', [\App\Http\Controllers\AuthController::class,'refresh']);
        Route::post('update-me', [\App\Http\Controllers\AuthController::class,'update']);
        Route::get('me', [\App\Http\Controllers\AuthController::class,'me']);
    });

    Route::post('change-password', [\App\Http\Controllers\AuthController::class,'changePassword']);


//    Route::post('transfer',             [\App\Http\Controllers\TransactionController::class, 'transfer']);
    Route::post('withdraw',             [\App\Http\Controllers\TransactionController::class, 'withdraw']);
    Route::get('transaction',             [\App\Http\Controllers\TransactionController::class, 'index']);

});

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});


