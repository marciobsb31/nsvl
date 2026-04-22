<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Resources\PlanoAcaoAnexoResource;
use App\Models\PlanoAcaoAnexo;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Anexos', description: 'Seção 8: anexos do plano de ação')]
class PlanoAcaoAnexoController extends Controller
{
    private const TIPOS_PERMITIDOS = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv',
        'image/png',
        'image/jpeg',
    ];

    private const TAMANHO_MAXIMO_BYTES = 10 * 1024 * 1024; // 10 MB

    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/anexos',
        summary: 'Lista os anexos do plano de ação do usuário autenticado',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Anexos'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de anexos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $anexos = PlanoAcaoAnexo::query()
            ->where('usuario_id', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->audit->log(
            'plano_acao.anexos.visualizar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            TipoAuditoria::VIEW->name,
            'plano_acao_anexos',
            null
        );

        return response()->json([
            'data' => PlanoAcaoAnexoResource::collection($anexos),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/anexos',
        summary: 'Faz upload de um arquivo de anexo',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Anexos'],
        responses: [
            new OA\Response(response: 201, description: 'Arquivo anexado com sucesso'),
            new OA\Response(response: 422, description: 'Arquivo inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'max:10240'],
        ]);

        $usuario = $request->user();
        $arquivo = $request->file('arquivo');

        $tipoMime = $arquivo->getMimeType() ?? '';
        if (! in_array($tipoMime, self::TIPOS_PERMITIDOS, true)) {
            return response()->json([
                'message' => 'Formato de arquivo não permitido.',
                'errors'  => ['arquivo' => ['Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, CSV, PNG, JPG.']],
            ], 422);
        }

        if ($arquivo->getSize() > self::TAMANHO_MAXIMO_BYTES) {
            return response()->json([
                'message' => 'Tamanho do arquivo excede o limite permitido (10 MB).',
                'errors'  => ['arquivo' => ['O arquivo deve ter no máximo 10 MB.']],
            ], 422);
        }

        $caminho = $arquivo->store("plano_acao_anexos/{$usuario->id}", 'local');

        $anexo = PlanoAcaoAnexo::create([
            'usuario_id'      => $usuario->id,
            'nome_original'   => $arquivo->getClientOriginalName(),
            'nome_armazenado' => basename((string) $caminho),
            'tipo_mime'       => $tipoMime,
            'tamanho_bytes'   => $arquivo->getSize(),
            'caminho'         => (string) $caminho,
        ]);

        $this->audit->log(
            'plano_acao.anexos.adicionar',
            $usuario->id,
            ['usuario_id' => $usuario->id, 'arquivo' => $arquivo->getClientOriginalName()],
            TipoAuditoria::INSERT->name,
            'plano_acao_anexos',
            $anexo->id
        );

        return response()->json([
            'data'    => PlanoAcaoAnexoResource::make($anexo),
            'message' => 'Arquivo anexado com sucesso.',
        ], 201);
    }

    #[OA\Delete(
        path: '/api/plano-acao/anexos/{id}',
        summary: 'Remove um anexo do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Anexos'],
        responses: [
            new OA\Response(response: 200, description: 'Arquivo removido com sucesso'),
            new OA\Response(response: 404, description: 'Arquivo não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $anexo = PlanoAcaoAnexo::query()
            ->where('id', $id)
            ->where('usuario_id', $usuario->id)
            ->firstOrFail();

        Storage::disk('local')->delete($anexo->caminho);

        $this->audit->log(
            'plano_acao.anexos.remover',
            $usuario->id,
            ['usuario_id' => $usuario->id, 'arquivo' => $anexo->nome_original],
            TipoAuditoria::DELETE->name,
            'plano_acao_anexos',
            $anexo->id
        );

        $anexo->delete();

        return response()->json(['message' => 'Arquivo removido com sucesso.']);
    }
}
