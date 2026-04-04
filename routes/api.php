<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HemocentroController;


Route::prefix('auth')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/login', [AuthController::class, 'login']);

    // Route::post('/password/send-code', [AuthController::class, 'sendPasswordResetCode']);
    // Route::post('/password/verify-code', [AuthController::class, 'verifyPassword']);
    // Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    // Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    // Route::post('/logout', [AuthController::class, 'logout']);
    

});
//ROTAS PARA OS HEMOCENTROS
        Route::get   ('/hemocentros',         [HemocentroController::class, 'index']);
        Route::get   ('/hemocentros/{id}',    [HemocentroController::class, 'show']);
        Route::post  ('/hemocentros',         [HemocentroController::class, 'store']);
        Route::put   ('/hemocentros/{id}',    [HemocentroController::class, 'update']);
        Route::delete('/hemocentros/{id}',    [HemocentroController::class, 'destroy']);

