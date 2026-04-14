<?php

use App\Http\Controllers\Auth\GovBrAuthController;
use App\Http\Controllers\Auth\UsuarioController;
use App\Http\Controllers\EsferaController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SolicitacaoCadastroController;
use App\Http\Controllers\TrocaContextoController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'check']);

Route::get('/esferas', [EsferaController::class, 'index']);

Route::prefix('localidades')->group(function () {
    Route::get('/ufs', [LocalidadeController::class, 'ufs']);
    Route::get('/municipios/{uf}', [LocalidadeController::class, 'municipios']);
    Route::get('/completo', [LocalidadeController::class, 'completo']);
});

Route::prefix('auth')->group(function () {
    Route::get('/url', [GovBrAuthController::class, 'redirect']);
    Route::get('/redirect', [GovBrAuthController::class, 'callback']);
    Route::get('/callback', [GovBrAuthController::class, 'callback']);
    Route::post('/exchange', [GovBrAuthController::class, 'exchange']);
});
Route::prefix('solicitacoes-cadastro')->group(function () {
    Route::post('/', [SolicitacaoCadastroController::class, 'store']);
    Route::get('/verificar-cpf', [SolicitacaoCadastroController::class, 'verificarCpf']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [GovBrAuthController::class, 'logout'])->name('auth.logout');

    Route::prefix('user')->group(function () {
        Route::get('/', [UsuarioController::class, 'me']);
    });

    Route::prefix('contextos')->group(function () {
        Route::get('/', [TrocaContextoController::class, 'index']);
        Route::post('selecionar', [TrocaContextoController::class, 'selecionar']);
    });

    Route::get('/perfis', [PerfilController::class, 'index'])->name('perfis.index');

    Route::prefix('solicitacoes-cadastro')->group(function () {
        Route::get('/', [SolicitacaoCadastroController::class, 'index'])
            ->middleware('permission:solicitacoes_cadastro.visualizar');
        Route::post('/{id}/perfis', [SolicitacaoCadastroController::class, 'adicionarPerfilVinculado']);
        Route::patch('/{id}/perfis/{perfilUsuarioId}/ativar', [SolicitacaoCadastroController::class, 'ativarPerfilVinculado'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
        Route::patch('/{id}/perfis/{perfilUsuarioId}/desativar', [SolicitacaoCadastroController::class, 'desativarPerfilVinculado'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
        Route::get('/{solicitacao_cadastro}', [SolicitacaoCadastroController::class, 'show'])
            ->middleware('permission:solicitacoes_cadastro.visualizar');
        Route::patch('/{id}', [SolicitacaoCadastroController::class, 'update'])
            ->middleware('permission:solicitacoes_cadastro.editar');
    });

});
