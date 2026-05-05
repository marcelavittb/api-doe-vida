<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HemocentroController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\DoacaoController;
use App\Http\Controllers\EmailVerificationController;

// ======================================================
// 🔓 ROTAS PÚBLICAS
// ======================================================
Route::prefix('auth')->group(function () {
    Route::post('/register',       [AuthController::class, 'register']);
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password',[AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::get('/users',                    [UserController::class, 'index']);
Route::get('/users/{id}',               [UserController::class, 'show']);
Route::get('/hemocentros',              [HemocentroController::class, 'index']);
Route::get('/hemocentros/{hemocentro}', [HemocentroController::class, 'show']);

// ======================================================
// 🔐 ROTAS PROTEGIDAS
// ======================================================
Route::middleware('auth:sanctum')->group(function () {

    // Usuário logado
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'user'  => $request->user(),
            'roles' => $request->user()->getRoleNames(),
        ]);
    });

    // ==================================================
    // 👤 USUÁRIOS
    // ==================================================
    Route::put('/users/{id}',    [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    Route::prefix('auth')->group(function () {
        Route::post('/users', [UserController::class, 'store']);
    });

    // ==================================================
    // 🏥 HEMOCENTROS
    // ==================================================
    Route::prefix('auth')->group(function () {
        Route::post('/hemocentros',               [HemocentroController::class, 'store']);
        Route::put('/hemocentros/{hemocentro}',   [HemocentroController::class, 'update']);
        Route::delete('/hemocentros/{hemocentro}',[HemocentroController::class, 'destroy']);
    });

    // ==================================================
    // 📅 AGENDAMENTOS
    // ==================================================
    Route::get('/agendamentos',           [AgendamentoController::class, 'index']); // Lista p/ doador (ativos) ou funcionário (hemocentro)
    Route::get('/agendamentos/historico', [AgendamentoController::class, 'historico']); // Histórico completo do doador
    Route::get('/agendamentos/{id}',      [AgendamentoController::class, 'show']);

    Route::prefix('auth')->group(function () {
        Route::post('/agendamentos',               [AgendamentoController::class, 'store']);
        Route::post('/agendamentos/{id}/confirmar', [AgendamentoController::class, 'confirmar']); // Confirma presença/horário
        Route::post('/agendamentos/{id}/cancelar',  [AgendamentoController::class, 'cancelar']);  // Cancela agendamento
        Route::delete('/agendamentos/{id}',        [AgendamentoController::class, 'destroy']);
    });

    // ==================================================
    // 🩺 TRIAGEM
    // ==================================================
    Route::get('/triagens',     [TriagemController::class, 'index']);
    Route::get('/triagens/{id}',[TriagemController::class, 'show']);
    
    Route::prefix('auth')->group(function () {
        Route::post('/triagens',        [TriagemController::class, 'store']); // Efetivar Triagem
        Route::put('/triagens/{id}',    [TriagemController::class, 'update']);
        Route::delete('/triagens/{id}', [TriagemController::class, 'destroy']); // Cancelar/Remover Triagem
    });

    // ==================================================
    // 🩸 DOAÇÃO
    // ==================================================
    Route::get('/doacoes',     [DoacaoController::class, 'index']); // Histórico de doações
    Route::get('/doacoes/{id}',[DoacaoController::class, 'show']);

    Route::prefix('auth')->group(function () {
        Route::post('/doacoes', [DoacaoController::class, 'store']); // Registrar Doação
    });

});
