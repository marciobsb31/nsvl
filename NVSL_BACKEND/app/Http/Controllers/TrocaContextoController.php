<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\AuditLog;
use App\Models\PerfilUsuario;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrocaContextoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    /**
     * GET /api/user/perfis-ativos
     *
     * Lista todos os perfis ativos e vigentes do usuário autenticado,
     * indicando qual é o perfil atualmente em uso.
     */
    public function listarPerfisAtivos(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $perfisVigentes = $user->perfisVigentes();

        return response()->json([
            'data' => $perfisVigentes->map(fn ($p) => [
                'perfil_usuario_id'     => $p->pivot->id,
                'perfil_id'             => $p->id,
                'nome'                  => $p->nome,
                'esfera'                => $p->esfera,
                'uf'                    => $p->pivot->uf,
                'municipio'             => $p->pivot->municipio,
                'orgao'                 => $p->pivot->orgao,
                'data_inicio_vigencia'  => $p->pivot->data_inicio_vigencia,
                'data_fim_vigencia'     => $p->pivot->data_fim_vigencia,
                'ativo'                 => $p->pivot->id == $user->perfil_usuario_ativo_id,
            ])->values(),
            'perfil_usuario_ativo_id' => $user->perfil_usuario_ativo_id,
        ]);
    }

    /**
     * POST /api/user/trocar-contexto
     *
     * Altera o perfil ativo do usuário autenticado. Atualiza esfera_atuacao,
     * uf_lotacao e municipio_lotacao do usuário para refletir o novo contexto.
     * Registra log de auditoria com perfil anterior e novo.
     */
    public function trocarContexto(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $request->validate([
            'perfil_usuario_id' => ['required', 'integer'],
        ], [
            'perfil_usuario_id.required' => 'Informe o perfil a ser ativado.',
        ]);

        $perfilUsuarioId = (int) $request->input('perfil_usuario_id');

        $perfisVigentes = $user->perfisVigentes();
        $novoPerfilPivot = $perfisVigentes->first(
            fn ($p) => $p->pivot->id == $perfilUsuarioId
        );

        if (!$novoPerfilPivot) {
            throw ApiException::forbidden('O perfil selecionado não está ativo ou não pertence ao seu cadastro.');
        }

        $perfilAnteriorId = $user->perfil_usuario_ativo_id;
        $perfilAnteriorNome = null;

        if ($perfilAnteriorId) {
            $anterior = $perfisVigentes->first(
                fn ($p) => $p->pivot->id == $perfilAnteriorId
            );
            $perfilAnteriorNome = $anterior?->nome;
        }

        $user->update([
            'perfil_usuario_ativo_id' => $perfilUsuarioId,
            'esfera_atuacao'          => $novoPerfilPivot->esfera,
            'uf_lotacao'              => $novoPerfilPivot->pivot->uf,
            'municipio_lotacao'       => $novoPerfilPivot->pivot->municipio,
        ]);

        $this->audit->log(
            'contexto.troca',
            $user->id,
            [
                'perfil_anterior_id'   => $perfilAnteriorId,
                'perfil_anterior_nome' => $perfilAnteriorNome,
                'novo_perfil_id'       => $perfilUsuarioId,
                'novo_perfil_nome'     => $novoPerfilPivot->nome,
                'nova_esfera'          => $novoPerfilPivot->esfera,
                'nova_uf'              => $novoPerfilPivot->pivot->uf,
                'novo_municipio'       => $novoPerfilPivot->pivot->municipio,
                'novo_orgao'           => $novoPerfilPivot->pivot->orgao,
            ],
            AuditLog::TIPO_UPDATE,
            'perfil_usuario',
            $perfilUsuarioId
        );

        $user->refresh();

        return response()->json([
            'message' => 'Contexto alterado com sucesso.',
            'user'    => $user->toSafeArray(),
        ]);
    }
}
