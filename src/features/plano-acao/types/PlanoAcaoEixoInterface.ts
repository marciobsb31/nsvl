export interface MetaPorAno {
  ano: string
  descricao: string
}

export interface OrcamentoItem {
  orcamento_estimado: string
  fonte_recurso: string
}

export interface AcaoEixo {
  id: string
  nome: string
  acao_nvsl: string
  acao_nvsl_outra: string
  meta: string
  metas_por_ano: MetaPorAno[]
  orgaos_locais: string[]
  orgaos_federais: string[]
  indicador_produto: string
  indicador_resultado: string
  vigencia: string
  orcamentos: OrcamentoItem[]
}

export interface PlanoAcaoEixo {
  id: number
  usuario_id: number
  eixo_numero: number
  acoes: AcaoEixo[]
  total_acoes: number
  pronto_para_envio: boolean
  created_at: string | null
  updated_at: string | null
}

export interface PlanoAcaoEixoPayload {
  acoes: AcaoEixo[]
}
