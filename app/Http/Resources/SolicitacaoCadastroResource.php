<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use App\Models\StatusSolicitacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitacaoCadastroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $statusEmAnalise = (int) $this->status_id === \App\Enums\StatusSolicitacaoEnum::EM_ANALISE->value;
        $usuarioLogado = auth()->user();
        $temPermissaoAvaliacao = (bool) $usuarioLogado?->hasPermissao('solicitacoes_cadastro.analisar');

        $payload = [
            'id'                     => $this->id,
            'usuario_id'             => $this->usuario_id,
            'nome'                   => $this->nome,
            'cpf'                    => $this->usuario->cpf,
            'telefone_institucional' => $this->telefone_institucional,
            'telefone_pessoal'       => $this->telefone_pessoal,
            'email'                  => $this->email_institucional,
            'email_institucional'    => $this->email_institucional,
            'esfera'                 => EsferaResource::make($this->esfera),
            'esfera_id'              => $this->esfera_id,
            'estado'                 => EstadoResource::make($this->ufRelacao),
            'uf_id'                  => $this->uf_id,
            'municipio'              => MunicipioResource::make($this->municipioRelacao),
            'municipio_id'           => $this->municipio_id,
            'orgao'                  => $this->orgao,
            'cargo'                  => $this->cargo,
            'status'                 => StatusSolicitacaoResource::make($this->statusSolicitacao),
            'motivo_reprovacao'      => $this->justificativa,
            'perfil_id_solicitado'   => $this->perfil_id,
            'vigencia_inicio_solicitada' => $this->formatarData($this->vigencia_inicio),
            'vigencia_fim_solicitada' => $this->formatarData($this->vigencia_fim),
            'pode_avaliar'           => $statusEmAnalise && $temPermissaoAvaliacao,
        ];

        if ($request->route('solicitacao_cadastro') === null) {
            return $payload;
        }

        $payload['perfis_vinculados'] = $this->obterPerfisVinculados();
        $payload['historico_reprovacoes'] = $this->montarHistoricoReprovacoes();

        return $payload;
    }

    private function formatarData(mixed $data): ?string
    {
        if (! $data) {
            return null;
        }

        if (is_string($data)) {
            return substr($data, 0, 10);
        }

        if (method_exists($data, 'format')) {
            return $data->format('Y-m-d');
        }

        return null;
    }

    private function obterPerfisVinculados(): array
    {
        $usuario = $this->usuario;
        if (! $usuario) {
            return [];
        }

        $hoje = now()->toDateString();
        $vinculos = $usuario->perfisUsuario()
            ->with(['perfil', 'abrangencia.esfera', 'abrangencia.uf', 'abrangencia.municipio', 'solicitacaoCadastroOrigem'])
            ->get();

        $perfis = $vinculos->map(function ($pu) use ($hoje): array {
            $inicio = $this->formatarData($pu->data_inicio_vigencia);
            $fim = $this->formatarData($pu->data_fim_vigencia);
            $ativo = (bool) ($pu->ativo ?? false);
            $vigente = $ativo && (! $inicio || $inicio <= $hoje) && (! $fim || $fim >= $hoje);

            $abrangencia = $pu->abrangencia;
            $origem = $pu->solicitacaoCadastroOrigem;

            return [
                'id'                => $pu->id,
                'perfil_usuario_id' => $pu->id,
                'ativo'             => $ativo,
                'perfil'            => $pu->perfil?->nome ?? '—',
                'vigencia_inicio'   => $inicio ?? '—',
                'vigencia_fim'      => $fim ?? '—',
                'vigente'           => $vigente,
                'esfera'            => $abrangencia?->esfera?->nome ?? '—',
                'uf'                => $abrangencia?->uf?->sigla ?? '—',
                'municipio'         => $abrangencia?->municipio?->nome ?? '—',
                'orgao'             => $origem?->orgao ?? $this->orgao ?? '—',
                'cargo'             => $origem?->cargo ?? $this->cargo ?? '—',
            ];
        })->all();

        usort($perfis, function (array $a, array $b): int {
            $ativoA = (int) (($a['ativo'] ?? false) ? 1 : 0);
            $ativoB = (int) (($b['ativo'] ?? false) ? 1 : 0);

            if ($ativoA !== $ativoB) {
                return $ativoB <=> $ativoA;
            }

            return strcmp((string) ($a['perfil'] ?? ''), (string) ($b['perfil'] ?? ''));
        });

        return $perfis;
    }

    private function montarHistoricoReprovacoes(): array
    {
        if (strtolower((string) ($this->statusSolicitacao?->nome ?? '')) !== StatusSolicitacao::REPROVADO) {
            return [];
        }

        if (! $this->justificativa_reprovacao) {
            return [];
        }

        $log = AuditLog::query()
            ->with('usuario')
            ->where('tabela_afetada', 'solicitacoes_cadastro')
            ->where('registro_id', $this->id)
            ->where('acao', 'gerenciar_cadastros.avaliacao')
            ->whereJsonContains('contexto->status', 'reprovado')
            ->orderByDesc('created_at')
            ->first();

        return [[
            'data'      => $log?->created_at?->toIso8601String() ?? $this->updated_at?->toIso8601String(),
            'motivo'    => $this->justificativa_reprovacao,
            'avaliador' => $log?->usuario?->nome,
        ]];
    }
}
