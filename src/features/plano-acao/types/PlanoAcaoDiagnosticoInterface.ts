export interface PlanoAcaoDiagnostico {
  id: number
  usuario_id: number
  caracterizacao_populacao: string | null
  barreiras_urbanisticas: string[]
  barreiras_transportes: string[]
  barreiras_atitudinais: string[]
  barreiras_arquitetonicas: string[]
  barreiras_comunicacoes: string[]
  barreiras_tecnologicas: string[]
  outras_barreiras: string | null
  pronto_para_envio: boolean
  created_at: string | null
  updated_at: string | null
}

export interface PlanoAcaoDiagnosticoPayload {
  caracterizacao_populacao: string
  barreiras_urbanisticas: string[]
  barreiras_transportes: string[]
  barreiras_atitudinais: string[]
  barreiras_arquitetonicas: string[]
  barreiras_comunicacoes: string[]
  barreiras_tecnologicas: string[]
  outras_barreiras: string
}
