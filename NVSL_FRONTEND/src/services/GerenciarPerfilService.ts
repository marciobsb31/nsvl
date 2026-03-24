import api from './ApiService'

export interface PermissaoItem {
  id: number
  funcionalidade: string
  nome_acao: string
  descricao?: string
}

export interface PerfilPermissao {
  id: number
  funcionalidade: string
  nome_acao: string
}

export interface PerfilGerenciar {
  id: number
  nome: string
  descricao?: string
  esfera: string
  status: string
  permissoes: PerfilPermissao[]
  created_at?: string
}

export interface HistoricoItem {
  id: number
  data_hora: string
  usuario: string
  perfil: string
  atualizacao: string
  action: string
}

export interface SalvarPerfilPayload {
  nome: string
  descricao?: string
  esfera: string
  status: string
  permissoes: number[]
}

export async function listarPerfisGerenciar(filtros?: {
  nome?: string
  esfera?: string
  status?: string
}): Promise<PerfilGerenciar[]> {
  const params = new URLSearchParams()
  if (filtros?.nome) params.set('nome', filtros.nome)
  if (filtros?.esfera) params.set('esfera', filtros.esfera)
  if (filtros?.status) params.set('status', filtros.status)
  const qs = params.toString()
  const url = qs ? `/gerenciar-perfis?${qs}` : '/gerenciar-perfis'
  const { data } = await api.get<{ data: PerfilGerenciar[] }>(url)
  return data?.data ?? []
}

export async function obterPerfil(id: number): Promise<PerfilGerenciar> {
  const { data } = await api.get<{ data: PerfilGerenciar }>(`/gerenciar-perfis/${id}`)
  return data.data
}

export async function cadastrarPerfil(payload: SalvarPerfilPayload): Promise<{ message: string; data: PerfilGerenciar }> {
  const { data } = await api.post<{ message: string; data: PerfilGerenciar }>('/gerenciar-perfis', payload)
  return data
}

export async function atualizarPerfil(id: number, payload: SalvarPerfilPayload): Promise<{ message: string; data: PerfilGerenciar }> {
  const { data } = await api.put<{ message: string; data: PerfilGerenciar }>(`/gerenciar-perfis/${id}`, payload)
  return data
}

export async function listarPermissoes(): Promise<PermissaoItem[]> {
  const { data } = await api.get<{ data: PermissaoItem[] }>('/gerenciar-perfis/permissoes')
  return data?.data ?? []
}

export async function obterHistorico(id: number): Promise<HistoricoItem[]> {
  const { data } = await api.get<{ data: HistoricoItem[] }>(`/gerenciar-perfis/${id}/historico`)
  return data?.data ?? []
}

export interface HierarquiaInfo {
  esfera_usuario: string
  esferas_permitidas: string[]
}

export async function obterHierarquia(): Promise<HierarquiaInfo> {
  const { data } = await api.get<HierarquiaInfo>('/gerenciar-perfis/hierarquia')
  return data
}
