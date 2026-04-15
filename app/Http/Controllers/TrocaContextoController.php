<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\AuditLog;
use App\Models\PerfilUsuario;
use App\Models\UsuarioContexto;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contexto', description: 'Perfil ativo do usuário')]
class TrocaContextoController extends Controller
{
    public function index()
    {
        $contextos = auth()->user()
            ->perfisUsuario()
            ->with([
                'perfil.esfera',
                'abrangencia.esfera',
            ])
            ->where('ativo', true)
            ->get();

        $data = $contextos->map(function ($ctx) {
            return [
                'id'         => $ctx->id,
                'perfil'     => $ctx->perfil?->nome,
                'esfera'     => $ctx->perfil?->esfera?->nome,
                'localidade' => $ctx->abrangencia?->nome,
            ];
        });

        return response()->json($data);
    }

    public function selecionar(ContextoRequest $request)
    {
        $usuario = auth()->user();

        $perfilUsuario = $usuario->perfisUsuario()
            ->where('id', $request->contexto_id)
            ->first();

        if (! $perfilUsuario) {
            return response()->json(['message' => 'Contexto inválido ou não pertence ao usuário.'],
                Response::HTTP_FORBIDDEN);
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

        // Atualizar usuario_contexto com o novo perfil e sua abrangência associada
        $perfilUsuarioAtivo = PerfilUsuario::find($perfilUsuarioId);
        UsuarioContexto::updateOrCreate(
            ['usuario_id' => $user->id],
            [
                'perfil_usuario_id'      => $perfilUsuarioId,
                'usuario_abrangencia_id' => $perfilUsuarioAtivo?->usuario_abrangencia_id,
            ]
        );

        $this->audit->log(
            'contexto.troca',
            $user->id,
            [
                'perfil_anterior_id'       => $perfilAnterior?->pivot->id,
                'perfil_anterior_nome'     => $perfilAnterior?->nome,
                'novo_perfil_id'           => $perfilUsuarioId,
                'novo_perfil_nome'         => $novoPerfilPivot->nome,
                'usuario_abrangencia_id'   => $perfilUsuarioAtivo?->usuario_abrangencia_id,
            ],
            AuditLog::TIPO_UPDATE,
            'perfil_usuario',
            $perfilUsuarioId
        );

        return response()->json([
            'message' => 'Contexto alterado com sucesso!',
        ]);
    }
}
