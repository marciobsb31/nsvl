import api from '@/services/ApiService'

export interface PlanoAcaoObservacao {
  id?: number
  usuario_id?: number
  observacoes: string
  created_at?: string
  updated_at?: string
}

export async function obterObservacoes(): Promise<PlanoAcaoObservacao | null> {
  const res = await api.get<{ data: PlanoAcaoObservacao | null }>('/plano-acao/observacoes')
  return res.data.data
}

export async function salvarObservacoes(payload: { observacoes: string }): Promise<PlanoAcaoObservacao> {
  const res = await api.post<{ data: PlanoAcaoObservacao }>('/plano-acao/observacoes', payload)
  return res.data.data
}
