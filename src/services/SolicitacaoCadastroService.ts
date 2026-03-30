import api from './ApiService'

export interface SolicitacaoCadastroItem {
  id: number
  nome: string
  status: string
  created_at: string
}

export interface PerfilVinculado {
  id: number
  perfil: string
  vigencia_inicio: string
  vigencia_fim: string
  vigente: boolean
  esfera: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
}

export interface HistoricoReprovacaoItem {
  data: string | null
  motivo: string
  avaliador: string | null
}

export interface SolicitacaoCadastroDetalhe extends SolicitacaoCadastroItem {
  cpf?: string
  email_institucional: string
  telefone_institucional: string
  telefone_pessoal?: string | null
  esfera_atuacao: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
  perfil_id_solicitado?: number | null
  vigencia_inicio_solicitada?: string | null
  vigencia_fim_solicitada?: string | null
  updated_at?: string
  perfis_vinculados?: PerfilVinculado[]
  pode_avaliar?: boolean
  historico_reprovacoes?: HistoricoReprovacaoItem[]
}

export interface SolicitacaoCadastroPayload {
  nome: string
  CPF?: string
  emailInstitucional: string
  telefoneInstitucional: string
  telefonePessoal?: string | null
  esferaAtuacao: string
  uf: string
  municipio: string
  orgao: string
  cargo: string
  aceiteTermo?: boolean
  perfilId?: number
  vigenciaInicio?: string
  vigenciaFim?: string
}

export interface SolicitacaoCadastroUpdatePayload
  extends Omit<SolicitacaoCadastroPayload, 'CPF' | 'aceiteTermo'> {
  CPF?: string
}

export interface SolicitacaoCadastroResponse {
  message: string
  solicitacao_id: number
}

export async function enviarSolicitacaoCadastro(
  payload: SolicitacaoCadastroPayload
): Promise<SolicitacaoCadastroResponse> {
  console.debug('[Cadastro] Enviando payload:', JSON.stringify(payload, null, 2))
  console.debug('[Cadastro] Token presente:', !!sessionStorage.getItem('nvsl_token'))
  try {
    const { data } = await api.post<SolicitacaoCadastroResponse>(
      '/solicitacoes-cadastro',
      payload
    )
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

export interface VerificarCpfResponse {
  disponivel: boolean
  mensagem: string
}

export async function verificarCpfDisponivel(cpf: string): Promise<VerificarCpfResponse> {
  const digitos = cpf.replace(/\D/g, '')
  const { data } = await api.get<VerificarCpfResponse>(
    '/solicitacoes-cadastro/verificar-cpf',
    { params: { cpf: digitos } }
  )
  return data
}
