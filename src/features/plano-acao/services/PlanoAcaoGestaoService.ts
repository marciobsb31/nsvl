import api from '@/services/ApiService'
import type {
  FiltrosPlanoAcaoGestao,
  PlanoAcaoGestaoDetalhe,
  PlanoAcaoGestaoHistoricoItem,
  PlanoAcaoGestaoItem,
} from '@/features/plano-acao/types/PlanoAcaoGestaoInterface'

export async function listarPlanosGestao(
  filtros: FiltrosPlanoAcaoGestao,
): Promise<PlanoAcaoGestaoItem[]> {
  const { data } = await api.get<{ data: PlanoAcaoGestaoItem[] }>('/plano-acao/gestao', {
    params: filtros,
  })
  return Array.isArray(data.data) ? data.data : []
}

export async function detalharPlanoGestao(id: number): Promise<PlanoAcaoGestaoDetalhe> {
  const { data } = await api.get<{ data: PlanoAcaoGestaoDetalhe }>(`/plano-acao/gestao/${id}`)
  return data.data
}

export async function aprovarPlanoGestao(id: number): Promise<{ status: string; message: string }> {
  const { data } = await api.post<{ status: string; message: string }>(
    `/plano-acao/gestao/${id}/aprovar`,
  )
  return data
}

export async function solicitarAjustesPlanoGestao(
  id: number,
  observacao?: string,
): Promise<{ status: string; message: string }> {
  const { data } = await api.post<{ status: string; message: string }>(
    `/plano-acao/gestao/${id}/solicitar-ajustes`,
    { observacao: observacao ?? '' },
  )
  return data
}

export async function historicoPlanoGestao(id: number): Promise<PlanoAcaoGestaoHistoricoItem[]> {
  const { data } = await api.get<{ data: PlanoAcaoGestaoHistoricoItem[] }>(`/plano-acao/gestao/${id}/historico`)
  return Array.isArray(data.data) ? data.data : []
}
