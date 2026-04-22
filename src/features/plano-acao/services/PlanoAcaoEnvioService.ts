import api from '@/services/ApiService'

export interface PlanoAcaoEnvio {
  id?: number
  responsavel_nome: string
  responsavel_cargo: string
  responsavel_orgao: string
  responsavel_contato: string
  justificativa_eixo_1?: string
  justificativa_eixo_2?: string
  justificativa_eixo_3?: string
  justificativa_eixo_4?: string
  status: 'rascunho' | 'em_analise'
  enviado_em?: string
  created_at?: string
  updated_at?: string
}

export async function obterEnvio(): Promise<PlanoAcaoEnvio | null> {
  const res = await api.get<{ data: PlanoAcaoEnvio | null }>('/plano-acao/envio')
  return res.data.data
}

export async function salvarEnvio(payload: Partial<PlanoAcaoEnvio>): Promise<PlanoAcaoEnvio> {
  const res = await api.post<{ data: PlanoAcaoEnvio }>('/plano-acao/envio', payload)
  return res.data.data
}

export async function enviarPlano(): Promise<PlanoAcaoEnvio> {
  const res = await api.post<{ data: PlanoAcaoEnvio }>('/plano-acao/envio/enviar')
  return res.data.data
}
