<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AlertaMedicoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampanhaController;
use App\Http\Controllers\DevSeedController;
use App\Http\Controllers\DoacaoController;
use App\Http\Controllers\EstoqueController;
use App\Http\Controllers\EstatisticaController;
use App\Http\Controllers\HemocentroController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTipoSangueHistoricoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ═══════════════════════════════════════════════════════
// ROTAS PÚBLICAS — sem autenticação
// ═══════════════════════════════════════════════════════

Route::prefix('auth')->group(function () {
    Route::post('/register',        [AuthController::class, 'register']);
    Route::post('/login',           [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

Route::get('/users/{id}',               [UserController::class,    'show']);
Route::get('/hemocentros',              [HemocentroController::class, 'index']);
Route::get('/hemocentros/{hemocentro}', [HemocentroController::class, 'show']);
Route::get('/triagens/perguntas',       [TriagemController::class, 'perguntas']);
Route::post('/seed-doadores',            [DevSeedController::class, 'seedDoadores']);
Route::post('/seed-agendamentos',        [DevSeedController::class, 'seedAgendamentos']);

Route::get('/dev/preview-email-campanha', function () {
    abort_unless(app()->environment(['local', 'testing']), 404);

    $campanha = new \App\Models\Campanha([
        'titulo' => 'Campanha urgente para doadores A+',
        'subtitulo' => 'Seu tipo sanguíneo pode ajudar pacientes que precisam de transfusão.',
        'descricao' => 'Estamos reforçando o estoque do hemocentro e convidando doadores cadastrados para realizar uma nova doação nos próximos dias.',
        'tipo_sangue' => 'A+',
        'data_publi' => now(),
        'data_expiracao' => now()->addDays(7),
        'status' => true,
    ]);

    $doador = new \App\Models\User([
        'name' => 'Vinicius',
        'email' => 'vinicius@example.com',
        'tipo_sang' => 'A+',
    ]);

    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

    return view('emails.campanha', [
        'campanha' => $campanha,
        'doador' => $doador,
        'ctaUrl' => "{$frontendUrl}/login",
        'preheader' => $campanha->subtitulo,
        'bloodType' => $campanha->tipo_sangue,
        'publishDate' => optional($campanha->data_publi)->format('d/m/Y'),
        'expireDate' => optional($campanha->data_expiracao)->format('d/m/Y'),
    ]);
});

// ═══════════════════════════════════════════════════════
// ROTAS AUTENTICADAS — qualquer role logado
// ═══════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // Auth utilitários
    Route::get('/auth/me', function (Request $request) {
        $user = $request->user();
        $user->load('hemocentro');
        
        return response()->json([
            'user'        => $user,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ]);
    });
    Route::delete('/auth/minha-conta',      [AuthController::class, 'excluirConta']);
    Route::get('/auth/meus-dados',          [AuthController::class, 'meusDados']);
    Route::post('/auth/elegibilidade',      [AuthController::class, 'salvarElegibilidade']);
    Route::get('/auth/elegibilidade/atual', [AuthController::class, 'getElegibilidade']);

    // Usuários
    Route::get('/users',      [UserController::class, 'index']);
    Route::put('/users/{id}', [UserController::class, 'update']);

    // Agendamentos — leitura (controller filtra por role)
    Route::get('/agendamentos',           [AgendamentoController::class, 'index']);
    Route::get('/agendamentos/historico', [AgendamentoController::class, 'historico']);
    Route::get('/agendamentos/{id}',      [AgendamentoController::class, 'show']);

    // Agendamentos — doador cria e cancela o próprio
    Route::prefix('auth')->group(function () {
        Route::post('/agendamentos',               [AgendamentoController::class, 'store']);
        Route::post('/agendamentos/{id}/cancelar', [AgendamentoController::class, 'cancelar']);
    });

    // Triagens — leitura (controller filtra por role)
    Route::get('/triagens',      [TriagemController::class, 'index']);
    Route::get('/triagens/{id}', [TriagemController::class, 'show']);

    // Doações — leitura (controller filtra por role)
    Route::get('/doacoes',      [DoacaoController::class, 'index']);
    Route::get('/doacoes/{id}', [DoacaoController::class, 'show']);

    // Estoque — leitura
    Route::get('/estoque',      [EstoqueController::class, 'index']);
    Route::get('/estoque/{id}', [EstoqueController::class, 'show']);

    // Alertas médicos — leitura (controller filtra por role)
    Route::get('/alertas-medicos',      [AlertaMedicoController::class, 'index']);
    Route::get('/alertas-medicos/{id}', [AlertaMedicoController::class, 'show']);

    // Estatísticas — leitura por role
    Route::get('/estatisticas/funcionario', [EstatisticaController::class, 'funcionario']);
    Route::get('/estatisticas/diretor',     [EstatisticaController::class, 'diretor']);
    Route::get('/estatisticas/admin',       [EstatisticaController::class, 'admin']);

    // Campanhas — leitura
    Route::get('/campanhas',      [CampanhaController::class, 'index']);
    Route::get('/campanhas/{id}', [CampanhaController::class, 'show']);

    // Certificados — doador vê os seus
    Route::get('/certificados',          [\App\Http\Controllers\CertificadoController::class, 'index']);
    Route::get('/certificados/{id}/pdf', [\App\Http\Controllers\CertificadoController::class, 'download']);

    // Relatórios JSON (insights para dashboard/frontend)
    Route::middleware('role:funcionario,diretor,admin')->prefix('relatorios')->group(function () {
        Route::get('/resumo',              [RelatorioController::class, 'resumo']);
        Route::get('/doacoes/json',        [RelatorioController::class, 'donationsSummary']);
        Route::get('/estoque/json',        [RelatorioController::class, 'bloodStock']);
        Route::get('/performance/mensal',  [RelatorioController::class, 'performanceMonthly']);

        // Relatórios PDF (nova URL + alias para compatibilidade)
        Route::get('/doacoes/pdf',  [RelatorioController::class, 'pdfDoacoes']);
        Route::get('/estoque/pdf',  [RelatorioController::class, 'pdfEstoque']);
        Route::get('/doadores/pdf', [RelatorioController::class, 'pdfDoadores']);

        // Aliases legados (mantidos para não quebrar clientes existentes)
        Route::get('/doacoes',  [RelatorioController::class, 'pdfDoacoes']);
        Route::get('/estoque',  [RelatorioController::class, 'pdfEstoque']);
        Route::get('/doadores', [RelatorioController::class, 'pdfDoadores']);

        // Novos relatórios PDF
        Route::get('/agendamentos/pdf', [RelatorioController::class, 'pdfAgendamentos']);
        Route::get('/triagens/pdf',     [RelatorioController::class, 'pdfTriagens']);
        Route::get('/desempenho/pdf',   [RelatorioController::class, 'pdfDesempenho']);
    });

    // Roles — leitura (qualquer autenticado)
    Route::get('/roles',       [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);

    // ═══════════════════════════════════════════════════
    // ROTAS DE ESCRITA — funcionário, diretor e admin
    // ═══════════════════════════════════════════════════

    Route::middleware('role:funcionario,diretor,admin')->prefix('auth')->group(function () {
        // Agendamentos — confirmar, reabrir, excluir
        Route::post('/agendamentos/{id}/confirmar', [AgendamentoController::class, 'confirmar']);
        Route::post('/agendamentos/{id}/reabrir',   [AgendamentoController::class, 'reabrir']);
        Route::delete('/agendamentos/{id}',         [AgendamentoController::class, 'destroy']);

        // Triagem
        Route::post('/triagens',        [TriagemController::class, 'store']);
        Route::put('/triagens/{id}',    [TriagemController::class, 'update']);
        Route::delete('/triagens/{id}', [TriagemController::class, 'destroy']);

        // Doação
        Route::post('/doacoes', [DoacaoController::class, 'store']);

        // Estoque — escrita
        Route::post('/estoque',     [EstoqueController::class, 'store']);
        Route::put('/estoque/{id}', [EstoqueController::class, 'update']);

        // Alertas médicos — escrita
        Route::post('/alertas-medicos',        [AlertaMedicoController::class, 'store']);
        Route::put('/alertas-medicos/{id}',    [AlertaMedicoController::class, 'update']);
        Route::delete('/alertas-medicos/{id}', [AlertaMedicoController::class, 'destroy']);

        // Histórico tipo sanguíneo
        Route::get('/doadores/{userId}/tipo-sangue-historico',  [UserTipoSangueHistoricoController::class, 'index']);
        Route::post('/doadores/{userId}/tipo-sangue-historico', [UserTipoSangueHistoricoController::class, 'store']);
    });

    // ═══════════════════════════════════════════════════
    // ROTAS DE GESTÃO — admin e diretor
    // ═══════════════════════════════════════════════════

    Route::middleware('role:admin,diretor')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
        });
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    // ═══════════════════════════════════════════════════
    // ROTAS EXCLUSIVAS DO ADMIN
    // ═══════════════════════════════════════════════════

    Route::middleware('role:admin')->prefix('auth')->group(function () {
        // Hemocentros — escrita
        Route::post('/hemocentros',                [HemocentroController::class, 'store']);
        Route::put('/hemocentros/{hemocentro}',    [HemocentroController::class, 'update']);
        Route::delete('/hemocentros/{hemocentro}', [HemocentroController::class, 'destroy']);

        // Campanhas — escrita e disparo
        // Roles — escrita
        Route::post('/roles',          [RoleController::class, 'store']);
        Route::put('/roles/{role}',    [RoleController::class, 'update']);
        Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
    });

    Route::middleware('role_or_permission:admin|diretor,gerenciar_campanhas')->prefix('auth')->group(function () {
        Route::get('/doadores/perfil-rfmt', [UserController::class, 'perfilRfmt']);
        Route::post('/campanhas',        [CampanhaController::class, 'store']);
        Route::put('/campanhas/{id}',    [CampanhaController::class, 'update']);
        Route::delete('/campanhas/{id}', [CampanhaController::class, 'destroy']);
    });

    Route::middleware('role_or_permission:admin|diretor,disparar_campanhas')->prefix('auth')->group(function () {
        Route::post('/campanhas/{id}/disparar', [CampanhaController::class, 'disparar']);
    });
});
