<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GovBrAuthController;
use App\Http\Controllers\Auth\TokenDeTesteController;
use App\Http\Controllers\EsferaController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\GerenciarPerfilController;
use App\Http\Controllers\TrocaContextoController;
use App\Http\Controllers\SolicitacaoCadastroController;

/*
|--------------------------------------------------------------------------
| NVSL API Routes
|--------------------------------------------------------------------------
*/

// -------------------------------------------------------
// Health Check — sem autenticação
// -------------------------------------------------------
Route::get('/health', [HealthController::class, 'check']);

// -------------------------------------------------------
// Esferas de atuação — dados cadastrais
// -------------------------------------------------------
Route::get('/esferas', [EsferaController::class, 'index'])
    ->name('esferas.index');

// -------------------------------------------------------
// Localidades (UF e Municípios) — dados do banco (replicados do IBGE)
// -------------------------------------------------------
Route::get('/localidades/ufs', [LocalidadeController::class, 'ufs'])
    ->name('localidades.ufs');
Route::get('/localidades/municipios', [LocalidadeController::class, 'municipios'])
    ->name('localidades.municipios');
Route::get('/localidades/completo', [LocalidadeController::class, 'completo'])
    ->name('localidades.completo');

// -------------------------------------------------------
// Auth — token de teste (apenas local/testing)
// -------------------------------------------------------
Route::get('/auth/redirect', [GovBrAuthController::class, 'redirect'])
    ->middleware('throttle:30,1')
    ->name('auth.redirect');
// ATENÇÃO: o redirect_uri do OAuth é /redirect-gov (web.php). Só cadastre /api/auth/callback no MGI se
// GOVBR_REDIRECT_URI for alterado para esse path (não é o padrão do projeto).
Route::get('/auth/callback', [GovBrAuthController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('auth.callback');
Route::post('/auth/exchange', [GovBrAuthController::class, 'exchange'])
    ->middleware('throttle:30,1')
    ->name('auth.exchange');
Route::post('/auth/token-de-teste', [TokenDeTesteController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('auth.token-de-teste');

// -------------------------------------------------------
// Solicitações de cadastro — público (criar solicitação)
// -------------------------------------------------------
Route::post('/solicitacoes-cadastro', [SolicitacaoCadastroController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('solicitacoes-cadastro.store.public');
Route::get('/solicitacoes-cadastro/verificar-cpf', [SolicitacaoCadastroController::class, 'verificarCpf'])
    ->middleware('throttle:30,1')
    ->name('solicitacoes-cadastro.verificar-cpf');

// -------------------------------------------------------
// Rotas autenticadas
// -------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [GovBrAuthController::class, 'logout'])
        ->name('auth.logout');

    Route::get('/user', [\App\Http\Controllers\Auth\UserController::class, 'me'])
        ->name('user.me');

    Route::get('/perfis', [\App\Http\Controllers\PerfilController::class, 'index'])
        ->name('perfis.index');

    // Troca de contexto
    Route::get('/user/perfis-ativos', [TrocaContextoController::class, 'listarPerfisAtivos'])
        ->name('user.perfis-ativos');
    Route::post('/user/trocar-contexto', [TrocaContextoController::class, 'trocarContexto'])
        ->name('user.trocar-contexto');

    // Gerenciar Perfis
    Route::get('/gerenciar-perfis', [GerenciarPerfilController::class, 'index'])
        ->name('gerenciar-perfis.index');
    Route::get('/gerenciar-perfis/permissoes', [GerenciarPerfilController::class, 'permissoes'])
        ->name('gerenciar-perfis.permissoes');
    Route::post('/gerenciar-perfis', [GerenciarPerfilController::class, 'store'])
        ->name('gerenciar-perfis.store');
    Route::get('/gerenciar-perfis/{id}', [GerenciarPerfilController::class, 'show'])
        ->name('gerenciar-perfis.show');
    Route::put('/gerenciar-perfis/{id}', [GerenciarPerfilController::class, 'update'])
        ->name('gerenciar-perfis.update');
    Route::get('/gerenciar-perfis/{id}/historico', [GerenciarPerfilController::class, 'historico'])
        ->name('gerenciar-perfis.historico');

    Route::get('/solicitacoes-cadastro', [SolicitacaoCadastroController::class, 'index'])
        ->name('solicitacoes-cadastro.index');
    Route::post('/solicitacoes-cadastro/{id}/perfis', [SolicitacaoCadastroController::class, 'adicionarPerfilVinculado'])
        ->name('solicitacoes-cadastro.perfis.store');
    Route::patch('/solicitacoes-cadastro/{id}/perfis/{perfilUsuarioId}/ativar', [SolicitacaoCadastroController::class, 'ativarPerfilVinculado'])
        ->name('solicitacoes-cadastro.perfis.ativar');
    Route::patch('/solicitacoes-cadastro/{id}/perfis/{perfilUsuarioId}/desativar', [SolicitacaoCadastroController::class, 'desativarPerfilVinculado'])
        ->name('solicitacoes-cadastro.perfis.desativar');
    Route::get('/solicitacoes-cadastro/{id}', [SolicitacaoCadastroController::class, 'show'])
        ->name('solicitacoes-cadastro.show');
    Route::patch('/solicitacoes-cadastro/{id}', [SolicitacaoCadastroController::class, 'update'])
        ->name('solicitacoes-cadastro.update');
});
