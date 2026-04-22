import api from '@/services/ApiService'
import type {
  PlanoAcaoDiagnostico,
  PlanoAcaoDiagnosticoPayload,
} from '@/features/plano-acao/types/PlanoAcaoDiagnosticoInterface'

export async function obterDiagnostico(): Promise<PlanoAcaoDiagnostico | null> {
  const { data } = await api.get<{ data: PlanoAcaoDiagnostico | null }>('/plano-acao/diagnostico')
  return data.data
}

export async function salvarDiagnostico(
  payload: PlanoAcaoDiagnosticoPayload,
): Promise<PlanoAcaoDiagnostico> {
  const { data } = await api.post<{ message: string; data: PlanoAcaoDiagnostico }>(
    '/plano-acao/diagnostico',
    payload,
  )
  return data.data
}
