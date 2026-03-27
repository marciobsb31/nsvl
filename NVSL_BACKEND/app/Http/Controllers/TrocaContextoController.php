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
                'data_inicio_vigencia'  => $p->pivot->data_inicio_vigencia,
                'data_fim_vigencia'     => $p->pivot->data_fim_vigencia,
                'ativo'                 => (bool) $p->pivot->ativo,
            ])->values(),
        ]);
    }

    /**
     * POST /api/user/trocar-contexto
     *
     * Altera o perfil ativo do usuário. Marca o perfil_usuario.ativo = true
     * e desmarca os demais.
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

        $perfilAnterior = $perfisVigentes->first(fn ($p) => $p->pivot->ativo);

        // Desmarcar todos os perfis do usuário e ativar o selecionado
        PerfilUsuario::where('usuario_id', $user->id)->update(['ativo' => false]);
        PerfilUsuario::where('id', $perfilUsuarioId)->update(['ativo' => true]);

        $this->audit->log(
            'contexto.troca',
            $user->id,
            [
                'perfil_anterior_id'   => $perfilAnterior?->pivot->id,
                'perfil_anterior_nome' => $perfilAnterior?->nome,
                'novo_perfil_id'       => $perfilUsuarioId,
                'novo_perfil_nome'     => $novoPerfilPivot->nome,
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
