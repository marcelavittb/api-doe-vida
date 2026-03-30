<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/login', [AuthController::class, 'login']);

    // Route::post('/password/send-code', [AuthController::class, 'sendPasswordResetCode']);
    // Route::post('/password/verify-code', [AuthController::class, 'verifyPassword']);
    // Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    // Route::post('/refresh-token', [AuthController::class, 'refreshToken']);

    // Route::post('/logout', [AuthController::class, 'logout']);
});
