import api from './ApiService'

export interface PerfilItem {
  id: number
  nome: string
  descricao?: string
}

export interface PerfilOption {
  value: number
  label: string
}

const PERFIS_PERMITIDOS_ORDEM = [
  'Administrador Nacional',
  'Administrador Estadual',
  'Administrador Municipal',
  'Gestor Nacional',
  'Gestor Estadual',
  'Gestor Municipal',
] as const

/**
 * Lista perfis para combos (formato value/label para SelectAutocomplete).
 */
export async function listarPerfis(): Promise<PerfilOption[]> {
  try {
    const { data } = await api.get<{ data?: Array<{ value?: number; label?: string; id?: number; nome?: string }> }>('/perfis')
    const lista = data?.data
    if (Array.isArray(lista) && lista.length > 0) {
      const perfisMapeados = lista
        .map((p) => ({
          value: p.value ?? p.id ?? 0,
          label: (p.label ?? p.nome ?? '').trim(),
        }))
        .filter((p) => PERFIS_PERMITIDOS_ORDEM.includes(p.label as (typeof PERFIS_PERMITIDOS_ORDEM)[number]))

      return PERFIS_PERMITIDOS_ORDEM.map((nomePerfil) => perfisMapeados.find((p) => p.label === nomePerfil))
        .filter((p): p is PerfilOption => !!p)
    }
    return []
  } catch {
    return []
  }
}
