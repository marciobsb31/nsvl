import api from './ApiService'

/**
 * Item da listagem de solicitações para a tela Gerenciar Cadastros.
 * Campos adicionais (esfera_atuacao, uf, municipio, email_institucional, orgao)
 * serão exibidos quando o backend passar a retorná-los no index.
 */
export interface SolicitacaoGerenciarItem {
  id: number
  nome: string
  status: string
  created_at: string
  cpf?: string
  esfera_atuacao?: string
  uf?: string
  municipio?: string
  orgao?: string
}

export interface FiltrosGerenciarSolicitacao {
  cpf?: string
  nome?: string
  uf?: string
  municipio?: string
  orgao?: string
  esfera?: string
  status?: string
}

export async function listarSolicitacoesGerenciar(
  filtros?: FiltrosGerenciarSolicitacao
): Promise<SolicitacaoGerenciarItem[]> {
  const params = new URLSearchParams()
  if (filtros?.cpf) params.set('cpf', filtros.cpf.replace(/\D/g, ''))
  if (filtros?.nome) params.set('nome', filtros.nome)
  if (filtros?.uf) params.set('uf', filtros.uf)
  if (filtros?.municipio) params.set('municipio', filtros.municipio)
  if (filtros?.orgao) params.set('orgao', filtros.orgao)
  if (filtros?.esfera) params.set('esfera', filtros.esfera)
  if (filtros?.status) params.set('status', filtros.status)

  const query = params.toString()
  const url = query ? `/solicitacoes-cadastro?${query}` : '/solicitacoes-cadastro'
  const { data } = await api.get<{ data: SolicitacaoGerenciarItem[] }>(url)
  return data.data ?? []
}

export interface AprovarPayload {
  perfilId?: string | number | null
  vigenciaInicio?: string
  vigenciaFim?: string
}

export async function aprovarSolicitacao(
  id: number,
  payload?: AprovarPayload
): Promise<void> {
  const body: Record<string, unknown> = { status: 'aprovado' }
  if (payload?.perfilId != null) body.perfil_id = payload.perfilId
  if (payload?.vigenciaInicio) body.vigencia_inicio = payload.vigenciaInicio
  if (payload?.vigenciaFim) body.vigencia_fim = payload.vigenciaFim
  await api.patch(`/solicitacoes-cadastro/${id}`, body)
}

export async function reprovarSolicitacao(id: number): Promise<void> {
  await api.patch(`/solicitacoes-cadastro/${id}`, { status: 'reprovado' })
}

export async function ativarPerfilVinculado(solicitacaoId: number, perfilUsuarioId: number): Promise<void> {
  await api.patch(`/solicitacoes-cadastro/${solicitacaoId}/perfis/${perfilUsuarioId}/ativar`)
}

export async function desativarPerfilVinculado(solicitacaoId: number, perfilUsuarioId: number): Promise<void> {
  await api.patch(`/solicitacoes-cadastro/${solicitacaoId}/perfis/${perfilUsuarioId}/desativar`)
}
