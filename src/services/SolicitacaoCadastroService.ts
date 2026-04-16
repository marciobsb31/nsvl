import api from './ApiService'
import type {
  SolicitacaoCadastroPayload,
  SolicitacaoCadastroResponse,
  SolicitacaoCadastroUpdatePayload,
  SolicitacaoCadastroItem,
  SolicitacaoCadastroDetalhe,
} from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'

export async function enviarSolicitacaoCadastro(
  payload: SolicitacaoCadastroPayload,
): Promise<SolicitacaoCadastroResponse> {
  console.debug('[Cadastro] Enviando payload:', JSON.stringify(payload, null, 2))
  console.debug('[Cadastro] Token presente:', !!sessionStorage.getItem('nvsl_token'))
  try {
    const { data } = await api.post<SolicitacaoCadastroResponse>('/solicitacoes-cadastro', payload)
    console.debug('[Cadastro] Sucesso:', data)
    return data
  } catch (err) {
    console.error('[Cadastro] Erro na requisição:', err)
    const axErr = err as { response?: { status?: number; data?: unknown } }
    if (axErr?.response) {
      console.error('[Cadastro] Status:', axErr.response.status, 'Data:', axErr.response.data)
    }
    throw err
  }
}

export async function listarSolicitacoesCadastro(): Promise<SolicitacaoCadastroItem[]> {
  const { data } = await api.get<{ data: SolicitacaoCadastroItem[] }>('/solicitacoes-cadastro')
  return data.data
}

export async function obterSolicitacaoCadastro(id: number): Promise<SolicitacaoCadastroDetalhe> {
  const { data } = await api.get<SolicitacaoCadastroDetalhe | { data: SolicitacaoCadastroDetalhe }>(
    `/solicitacoes-cadastro/${id}`,
  )
  return (data as { data?: SolicitacaoCadastroDetalhe }).data ?? (data as SolicitacaoCadastroDetalhe)
}

export async function atualizarSolicitacaoCadastro(
  id: number,
  payload: SolicitacaoCadastroUpdatePayload,
): Promise<{ message: string; data: SolicitacaoCadastroItem }> {
  const { data } = await api.put<{ message: string; data: SolicitacaoCadastroItem }>(
    `/solicitacoes-cadastro/${id}`,
    payload,
  )
  return data
}

export async function excluirSolicitacaoCadastro(id: number): Promise<{ message: string }> {
  const { data } = await api.delete<{ message: string }>(`/solicitacoes-cadastro/${id}`)
  return data
}

export interface VerificarCpfResponse {
  disponivel: boolean
  mensagem: string
  perfis_ativos?: Array<{
    id: number
    nome: string
    codigo?: string
  }>
}

export async function verificarCpfDisponivel(
  cpf: string,
  area?: {
    esfera_id?: number
    uf_id?: number
    municipio_id?: number
  },
): Promise<VerificarCpfResponse> {
  const digitos = cpf.replace(/\D/g, '')
  const { data } = await api.get<VerificarCpfResponse>('/solicitacoes-cadastro/verificar-cpf', {
    params: {
      cpf: digitos,
      ...(area?.esfera_id ? { esfera_id: area.esfera_id } : {}),
      ...(area?.uf_id ? { uf_id: area.uf_id } : {}),
      ...(area?.municipio_id ? { municipio_id: area.municipio_id } : {}),
    },
  })
  return data
}
