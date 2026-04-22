import api from '@/services/ApiService'
import type { PlanoAcaoEixo, PlanoAcaoEixoPayload } from '@/features/plano-acao/types/PlanoAcaoEixoInterface'

export async function obterEixo(eixo: number): Promise<PlanoAcaoEixo | null> {
  const { data } = await api.get<{ data: PlanoAcaoEixo | null }>(`/plano-acao/eixo/${eixo}`)
  return data.data
}

export async function salvarEixo(
  eixo: number,
  payload: PlanoAcaoEixoPayload,
): Promise<PlanoAcaoEixo> {
  const { data } = await api.post<{ message: string; data: PlanoAcaoEixo }>(
    `/plano-acao/eixo/${eixo}`,
    payload,
  )
  return data.data
}
