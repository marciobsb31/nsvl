<?php

namespace App\Http\Resources;

use App\Models\PlanoAcaoAnexo;
use App\Models\PlanoAcaoDiagnostico;
use App\Models\PlanoAcaoEixo;
use App\Models\PlanoAcaoIdentificacao;
use App\Models\PlanoAcaoObservacao;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoGestaoResource extends JsonResource
{
    /** Quando true, inclui os dados completos do plano (para detalhamento). */
    private bool $comDetalhes;

    public function __construct(mixed $resource, bool $comDetalhes = false)
    {
        parent::__construct($resource);
        $this->comDetalhes = $comDetalhes;
    }

    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'estado_ou_municipio' => $this->estadoOuMunicipio(),
            'esfera' => $this->esfera_nome,
            'uf_id' => $this->uf_id,
            'uf' => $this->uf_sigla,
            'municipio_id' => $this->municipio_id,
            'municipio' => $this->municipio_nome,
            'orgao_gestor' => $this->orgao_gestor,
            'data_envio' => $this->enviado_em?->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => self::statusLabel($this->status ?? ''),
            'vigencia_inicio' => $this->vigencia_inicio,
            'vigencia_fim' => $this->vigencia_fim,
            'responsavel_nome' => $this->responsavel_nome,
            'responsavel_cargo' => $this->responsavel_cargo,
            'responsavel_orgao' => $this->responsavel_orgao,
            'responsavel_contato' => $this->responsavel_contato,
            'justificativa_eixo_1' => $this->justificativa_eixo_1,
            'justificativa_eixo_2' => $this->justificativa_eixo_2,
            'justificativa_eixo_3' => $this->justificativa_eixo_3,
            'justificativa_eixo_4' => $this->justificativa_eixo_4,
            'enviado_em' => $this->enviado_em?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->comDetalhes) {
            $uid = $this->usuario_id;

            $identificacao = PlanoAcaoIdentificacao::where('usuario_id', $uid)->first();
            $diagnostico   = PlanoAcaoDiagnostico::where('usuario_id', $uid)->first();
            $eixos         = PlanoAcaoEixo::where('usuario_id', $uid)
                                ->whereIn('eixo_numero', [1, 2, 3, 4])
                                ->orderBy('eixo_numero')
                                ->get()
                                ->keyBy('eixo_numero')
                                ->map(fn ($e) => $e->acoes ?? []);
            $observacao    = PlanoAcaoObservacao::where('usuario_id', $uid)->first();
            $anexos        = PlanoAcaoAnexo::where('usuario_id', $uid)
                                ->orderBy('created_at')
                                ->get()
                                ->map(fn ($a) => [
                                    'id' => $a->id,
                                    'nome_original' => $a->nome_original,
                                    'tipo_mime' => $a->tipo_mime,
                                    'tamanho_bytes' => $a->tamanho_bytes,
                                    'created_at' => $a->created_at?->toIso8601String(),
                                ]);

            $base['identificacao'] = $identificacao ? [
                'orgao_gestor' => $identificacao->orgao_gestor,
                'secretarias_envolvidas' => $identificacao->secretarias_envolvidas ?? [],
                'vigencia_inicio' => $identificacao->vigencia_inicio?->format('Y-m-d'),
                'vigencia_fim' => $identificacao->vigencia_fim?->format('Y-m-d'),
            ] : null;

            $base['diagnostico'] = $diagnostico ? [
                'caracterizacao_populacao' => $diagnostico->caracterizacao_populacao,
                'barreiras_urbanisticas' => $diagnostico->barreiras_urbanisticas ?? [],
                'barreiras_arquitetonicas' => $diagnostico->barreiras_arquitetonicas ?? [],
                'barreiras_transportes' => $diagnostico->barreiras_transportes ?? [],
                'barreiras_comunicacoes' => $diagnostico->barreiras_comunicacoes ?? [],
                'barreiras_atitudinais' => $diagnostico->barreiras_atitudinais ?? [],
                'barreiras_tecnologicas' => $diagnostico->barreiras_tecnologicas ?? [],
                'outras_barreiras' => $diagnostico->outras_barreiras,
            ] : null;

            $base['eixos'] = [
                1 => $eixos->get(1, []),
                2 => $eixos->get(2, []),
                3 => $eixos->get(3, []),
                4 => $eixos->get(4, []),
            ];

            $base['observacoes'] = $observacao?->observacoes;
            $base['anexos'] = $anexos->values();
        }

        return $base;
    }

    private function estadoOuMunicipio(): string
    {
        return $this->municipio_nome ? 'Município' : 'Estado';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'rascunho' => 'Não enviado',
            'em_analise', 'enviado_para_analise' => 'Análise documental e Análise técnica',
            'em_analise_comissao_tecnica' => 'Em análise pela Comissão Técnica',
            'devolvido_ajustes', 'em_ajustes' => 'Em ajustes',
            'sei_abertura_processo' => 'SEI - Abertura de processo',
            'acordo_adesao' => 'Acordo de Adesão / Termo de Adesão',
            'despacho' => 'Despacho',
            'extrato' => 'Extrato',
            'publicacao_dou' => 'Publicação - DOU',
            'aprovado', 'conclusao' => 'Conclusão',
            'nao_iniciado' => 'Não iniciado',
            default => (string) $status,
        };
    }
}
