export interface PlanoAcaoIdentificacao {
  id: number
  usuario_id: number
  orgao_gestor: string
  secretarias_envolvidas: string[]
  vigencia_inicio: string
  vigencia_fim: string
  pronto_para_envio: boolean
  created_at: string | null
  updated_at: string | null
}

export interface PlanoAcaoIdentificacaoPayload {
  orgao_gestor: string
  secretarias_envolvidas: string[]
  vigencia_inicio: string
  vigencia_fim: string
}
