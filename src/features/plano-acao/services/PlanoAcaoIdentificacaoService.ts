import api from '@/services/ApiService'
import type {
  PlanoAcaoIdentificacao,
  PlanoAcaoIdentificacaoPayload,
} from '@/features/plano-acao/types/PlanoAcaoIdentificacaoInterface'

export async function obterIdentificacao(): Promise<PlanoAcaoIdentificacao | null> {
  const { data } = await api.get<{ data: PlanoAcaoIdentificacao | null }>('/plano-acao/identificacao')
  return data.data
}

export async function salvarIdentificacao(
  payload: PlanoAcaoIdentificacaoPayload,
): Promise<PlanoAcaoIdentificacao> {
  const { data } = await api.post<{ message: string; data: PlanoAcaoIdentificacao }>(
    '/plano-acao/identificacao',
    payload,
  )
  return data.data
}
