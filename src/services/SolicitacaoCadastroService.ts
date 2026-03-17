import api from './ApiService'

export interface SolicitacaoCadastroItem {
  id: number
  nome: string
  status: string
  created_at: string
}

export interface SolicitacaoCadastroDetalhe extends SolicitacaoCadastroItem {
  email_institucional: string
  telefone_institucional: string
  telefone_pessoal?: string
  esfera_atuacao: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
  updated_at?: string
}

export interface SolicitacaoCadastroPayload {
  nome: string
  CPF?: string
  emailInstitucional: string
  telefoneInstitucional: string
  telefonePessoal?: string
  esferaAtuacao: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
}

/** Payload para edição: CPF opcional (não retornado pela API por segurança) */
export interface SolicitacaoCadastroUpdatePayload extends Omit<SolicitacaoCadastroPayload, 'CPF'> {
  CPF?: string
}

export interface SolicitacaoCadastroResponse {
  message: string
  solicitacao_id: number
}

export async function enviarSolicitacaoCadastro(
  payload: SolicitacaoCadastroPayload
): Promise<SolicitacaoCadastroResponse> {
  const { data } = await api.post<SolicitacaoCadastroResponse>(
    '/solicitacoes-cadastro',
    payload
  )
  return data
}

export async function listarSolicitacoesCadastro(): Promise<SolicitacaoCadastroItem[]> {
  const { data } = await api.get<{ data: SolicitacaoCadastroItem[] }>('/solicitacoes-cadastro')
  return data.data
}

export async function obterSolicitacaoCadastro(id: number): Promise<SolicitacaoCadastroDetalhe> {
  const { data } = await api.get<SolicitacaoCadastroDetalhe>(`/solicitacoes-cadastro/${id}`)
  return data
}

export async function atualizarSolicitacaoCadastro(
  id: number,
  payload: SolicitacaoCadastroUpdatePayload
): Promise<{ message: string; data: SolicitacaoCadastroItem }> {
  const { data } = await api.put<{ message: string; data: SolicitacaoCadastroItem }>(
    `/solicitacoes-cadastro/${id}`,
    payload
  )
  return data
}

export async function excluirSolicitacaoCadastro(id: number): Promise<{ message: string }> {
  const { data } = await api.delete<{ message: string }>(`/solicitacoes-cadastro/${id}`)
  return data
}
