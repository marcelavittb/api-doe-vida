<?php 

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HemocentroController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AgendamentoController;

// ==========================================
// ROTAS DE AUTENTICAÇÃO
// ==========================================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Essa rota responde para o React quem é o usuário logado
    Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user() 
        ]);
    });
});

// ==========================================
// ROTAS DE USUÁRIOS
// ==========================================
Route::post('/auth/users', [UserController::class, 'store'])->middleware('auth:sanctum');
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// GRUPO DE ROTAS PROTEGIDAS POR AUTENTICAÇÃO - (Comentado conforme original)
// Route::group(['prefix' => 'users', 'middleware' => 'auth:sanctum'], function() {
//     Route::get('/', [UserController::class, 'index']);
//     Route::get('/{id}', [UserController::class, 'show']);
//     Route::put('/{id}', [UserController::class, 'update']);
//     Route::delete('/{id}', [UserController::class, 'destroy']);
// });

// ==========================================
// ROTAS PARA OS HEMOCENTROS
// ==========================================
Route::post('/auth/hemocentros', [HemocentroController::class, 'store'])->middleware('auth:sanctum');
Route::get('/hemocentros', [HemocentroController::class, 'index']);
Route::get('/hemocentros/{hemocentro}', [HemocentroController::class, 'show']);
Route::put('/auth/hemocentros/{hemocentro}', [HemocentroController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/auth/hemocentros/{hemocentro}', [HemocentroController::class, 'destroy'])->middleware('auth:sanctum');

// ==========================================
// ROTAS DE AGENDAMENTOS
// ==========================================
Route::post('/auth/agendamentos', [AgendamentoController::class, 'store'])->middleware('auth:sanctum');
Route::get('/agendamentos', [AgendamentoController::class, 'index'])->middleware('auth:sanctum');
