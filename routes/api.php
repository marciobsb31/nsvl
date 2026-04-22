<?php

use App\Http\Controllers\Auth\GovBrAuthController;
use App\Http\Controllers\Auth\UsuarioController;
use App\Http\Controllers\EsferaController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanoAcaoIdentificacaoController;
use App\Http\Controllers\PlanoAcaoDiagnosticoController;
use App\Http\Controllers\PlanoAcaoEixoController;
use App\Http\Controllers\PlanoAcaoObservacaoController;
use App\Http\Controllers\PlanoAcaoAnexoController;
use App\Http\Controllers\PlanoAcaoEnvioController;
use App\Http\Controllers\PlanoAcaoGestaoController;
use App\Http\Controllers\SolicitacaoCadastroController;
use App\Http\Controllers\StatusSolicitacaoController;
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

    Route::prefix('usuario')->group(function () {
        Route::get('/', [UsuarioController::class, 'me']);
    });

    Route::prefix('contextos')->group(function () {
        Route::get('/', [TrocaContextoController::class, 'index']);
        Route::post('selecionar', [TrocaContextoController::class, 'selecionar']);
    });

    Route::get('/perfis', [PerfilController::class, 'index'])->name('perfis.index');
    Route::get('/status-solicitacao', StatusSolicitacaoController::class);

    Route::prefix('plano-acao')->group(function () {
        Route::get('/gestao', [PlanoAcaoGestaoController::class, 'index'])
            ->middleware('permission:plano_acao.visualizar');
        Route::get('/gestao/{id}', [PlanoAcaoGestaoController::class, 'show'])
            ->middleware('permission:plano_acao.visualizar');
        Route::get('/gestao/{id}/historico', [PlanoAcaoGestaoController::class, 'historico'])
            ->middleware('permission:plano_acao.visualizar');
        Route::post('/gestao/{id}/aprovar', [PlanoAcaoGestaoController::class, 'aprovar'])
            ->middleware('permission:plano_acao.visualizar');
        Route::post('/gestao/{id}/solicitar-ajustes', [PlanoAcaoGestaoController::class, 'solicitarAjustes'])
            ->middleware('permission:plano_acao.visualizar');
        Route::get('/identificacao', [PlanoAcaoIdentificacaoController::class, 'show']);
        Route::post('/identificacao', [PlanoAcaoIdentificacaoController::class, 'store']);
        Route::get('/diagnostico', [PlanoAcaoDiagnosticoController::class, 'show']);
        Route::post('/diagnostico', [PlanoAcaoDiagnosticoController::class, 'store']);
        Route::get('/eixo/{eixo}', [PlanoAcaoEixoController::class, 'show'])->where('eixo', '[1-4]');
        Route::post('/eixo/{eixo}', [PlanoAcaoEixoController::class, 'store'])->where('eixo', '[1-4]');
        Route::get('/observacoes', [PlanoAcaoObservacaoController::class, 'show']);
        Route::post('/observacoes', [PlanoAcaoObservacaoController::class, 'store']);
        Route::get('/anexos', [PlanoAcaoAnexoController::class, 'index']);
        Route::post('/anexos', [PlanoAcaoAnexoController::class, 'store']);
        Route::delete('/anexos/{id}', [PlanoAcaoAnexoController::class, 'destroy']);
        Route::get('/envio', [PlanoAcaoEnvioController::class, 'show']);
        Route::post('/envio', [PlanoAcaoEnvioController::class, 'store']);
        Route::post('/envio/enviar', [PlanoAcaoEnvioController::class, 'submit']);
    });

    Route::prefix('solicitacoes-cadastro')->group(function () {
        Route::get('/', [SolicitacaoCadastroController::class, 'index'])
            ->middleware('permission:solicitacoes_cadastro.visualizar');
        Route::post('/{id}/perfis', [SolicitacaoCadastroController::class, 'adicionarPerfilVinculado'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
        Route::patch('/{id}/perfis/{perfilUsuarioId}/ativar', [SolicitacaoCadastroController::class, 'ativarPerfilVinculado'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
        Route::patch('/{id}/perfis/{perfilUsuarioId}/desativar', [SolicitacaoCadastroController::class, 'desativarPerfilVinculado'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
        Route::get('/{solicitacao_cadastro}', [SolicitacaoCadastroController::class, 'show'])
            ->middleware('permission:solicitacoes_cadastro.visualizar');
        Route::patch('/{solicitacao_cadastro}', [SolicitacaoCadastroController::class, 'update'])
            ->middleware('permission:solicitacoes_cadastro.analisar');
    });

});
