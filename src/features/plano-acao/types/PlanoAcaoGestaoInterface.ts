export interface FiltrosPlanoAcaoGestao {
  esfera?: string
  uf_id?: number
  municipio_id?: number
  status?: string
}

export interface PlanoAcaoGestaoItem {
  id: number
  usuario_id: number
  estado_ou_municipio: string
  esfera: string | null
  uf_id: number | null
  uf: string | null
  municipio_id: number | null
  municipio: string | null
  orgao_gestor: string | null
  data_envio: string | null
  status: string
  status_label: string
}

export interface PlanoAcaoGestaoHistoricoItem {
  id: number
  data_hora: string | null
  usuario: string
  perfil: string
  evento: string
}

// ── Interfaces de suporte para o detalhamento ──────────────────────────────

export interface MetaPorAnoDetalhe {
  ano: string
  descricao: string
}

export interface OrcamentoItemDetalhe {
  orcamento_estimado: string
  fonte_recurso: string
}

export interface AcaoEixoDetalhe {
  id: string
  nome: string
  acao_nvsl: string
  acao_nvsl_outra: string
  meta: string
  metas_por_ano: MetaPorAnoDetalhe[]
  orgaos_locais: string[]
  orgaos_federais: string[]
  indicador_produto: string
  indicador_resultado: string
  vigencia: string
  orcamentos: OrcamentoItemDetalhe[]
}

export interface IdentificacaoDetalhe {
  orgao_gestor: string
  secretarias_envolvidas: string[]
  vigencia_inicio: string | null
  vigencia_fim: string | null
}

export interface DiagnosticoDetalhe {
  caracterizacao_populacao: string | null
  barreiras_urbanisticas: string[]
  barreiras_arquitetonicas: string[]
  barreiras_transportes: string[]
  barreiras_comunicacoes: string[]
  barreiras_atitudinais: string[]
  barreiras_tecnologicas: string[]
  outras_barreiras: string | null
}

export interface AnexoDetalhe {
  id: number
  nome_original: string
  tipo_mime: string
  tamanho_bytes: number
  created_at: string | null
}

export interface PlanoAcaoGestaoDetalhe extends PlanoAcaoGestaoItem {
  vigencia_inicio: string | null
  vigencia_fim: string | null
  responsavel_nome: string | null
  responsavel_cargo: string | null
  responsavel_orgao: string | null
  responsavel_contato: string | null
  justificativa_eixo_1: string | null
  justificativa_eixo_2: string | null
  justificativa_eixo_3: string | null
  justificativa_eixo_4: string | null
  enviado_em: string | null
  identificacao: IdentificacaoDetalhe | null
  diagnostico: DiagnosticoDetalhe | null
  eixos: Record<1 | 2 | 3 | 4, AcaoEixoDetalhe[]>
  observacoes: string | null
  anexos: AnexoDetalhe[]
}

