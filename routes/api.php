<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HemocentroController;
use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\TriagemController;


// ======================================================
// 🔓 ROTAS PÚBLICAS (SEM LOGIN)
// ======================================================
Route::prefix('auth')->group(function () {

    // 🔐 Auth básico
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // 🔑 Recuperação de senha
    Route::post('/forgot-password', function (Request $request) {
        return Password::sendResetLink($request->only('email'));
    });

    Route::post('/reset-password', function (Request $request) {
        return Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = bcrypt($password);
                $user->save();
            }
        );
    });
});


// ======================================================
// 🔐 ROTAS PROTEGIDAS (AUTH)
// ======================================================
Route::middleware('auth:sanctum')->group(function () {

    // --------------------------------------------------
    // 👤 USUÁRIO LOGADO
    // --------------------------------------------------
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'user'  => $request->user(),
            'roles' => $request->user()->getRoleNames()
        ]);
    });

    // ==================================================
    // 👤 USUÁRIOS
    // ==================================================
    Route::middleware(['auth:sanctum', 'role:doador'])->get('/me/doacoes', function (Request $request) {
        return $request->user()->agendamentos;
    });
    // 🔴 ADMIN + FUNCIONÁRIO → cria e deleta
    Route::middleware('role:admin|funcionario')->group(function () {
        Route::post('/auth/users', [UserController::class, 'store']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // 🟡 DIRETOR → atualiza
    Route::middleware('role:diretor')->group(function () {
        Route::put('/users/{id}', [UserController::class, 'update']);
    });

    // 🔵 VISUALIZAÇÃO
    Route::middleware('role:funcionario|diretor|admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
    });

    // ==================================================
    // 🏥 HEMOCENTROS
    // ==================================================

    // 🔴 ADMIN + DIRETOR → CRUD
    Route::middleware('role:admin|diretor')->group(function () {
        Route::post('/hemocentros', [HemocentroController::class, 'store']);
        Route::put('/hemocentros/{hemocentro}', [HemocentroController::class, 'update']);
        Route::delete('/hemocentros/{hemocentro}', [HemocentroController::class, 'destroy']);
    });

    // 🔓 TODOS LOGADOS → leitura
    Route::get('/hemocentros', [HemocentroController::class, 'index']);
    Route::get('/hemocentros/{hemocentro}', [HemocentroController::class, 'show']);

    // ==================================================
    // 📅 AGENDAMENTOS
    // ==================================================

    // 🟢 DOADOR → CRUD próprio
    Route::middleware('role:doador')->group(function () {
        Route::post('/agendamentos', [AgendamentoController::class, 'store']);
        Route::get('/me/agendamentos', [AgendamentoController::class, 'meus']);
        Route::put('/agendamentos/{id}', [AgendamentoController::class, 'update']);
        Route::delete('/agendamentos/{id}', [AgendamentoController::class, 'destroy']);
    });

    // 🔵 FUNCIONÁRIO / DIRETOR / ADMIN → visualizar todos
    Route::middleware('role:funcionario|diretor|admin')->group(function () {
        Route::get('/agendamentos', [AgendamentoController::class, 'index']);
    });

    // ==================================================
    // 🩺 TRIAGEM
    // ==================================================

    Route::middleware('role:funcionario|diretor')->group(function () {
        Route::apiResource('triagens', TriagemController::class);
    });

});