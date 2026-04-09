import api from './ApiService'

export interface LocalidadeOption {
  value: string
  label: string
}

/** UF com id interno do banco (PK), retornado por GET /localidades/completo */
export interface UfCompletoOption extends LocalidadeOption {
  id?: number
}

export interface LocalidadesCompleto {
  ufs: UfCompletoOption[]
  municipios_por_uf: Record<string, LocalidadeOption[]>
}

/**
 * Lista UFs e municípios de todas as UFs em uma única requisição.
 * Recomendado para carregar dados completos e cachear no frontend.
 * Fonte única: API backend.
 */
export async function listarLocalidadesCompleto(): Promise<LocalidadesCompleto> {
  try {
    const { data } = await api.get<{ data: LocalidadesCompleto }>('/localidades/completo')
    const result = data.data
    if (
      result &&
      Array.isArray(result.ufs) &&
      result.ufs.length > 0 &&
      result.municipios_por_uf &&
      Object.keys(result.municipios_por_uf).length > 0
    ) {
      return result
    }
    throw new Error('Resposta inválida')
  } catch (err) {
    console.warn('[LocalidadeService] API completo indisponível, usando endpoints separados do backend.', err)
    const ufs = await listarUfs()
    const municipiosPorUf: Record<string, LocalidadeOption[]> = {}
    for (const uf of ufs) {
      municipiosPorUf[uf.value] = await listarMunicipios(uf.value)
    }
    return { ufs, municipios_por_uf: municipiosPorUf }
  }
}

/**
 * Lista todas as UFs (somente API backend).
 */
export async function listarUfs(): Promise<LocalidadeOption[]> {
  try {
    const { data } = await api.get<{ data: LocalidadeOption[] }>('/localidades/ufs')
    if (Array.isArray(data.data) && data.data.length > 0) {
      return data.data
    }
    return []
  } catch (err) {
    console.warn('[LocalidadeService] Erro ao buscar UFs no backend.', err)
    return []
  }
}

/**
 * Lista municípios da UF informada (somente API backend).
 */
export async function listarMunicipios(uf: string): Promise<LocalidadeOption[]> {
  if (!uf || uf.length !== 2) return []
  const ufNormalizada = uf.toUpperCase()
  try {
    const { data } = await api.get<{ data: LocalidadeOption[] }>(
      `/localidades/municipios?uf=${encodeURIComponent(ufNormalizada)}`
    )
    if (Array.isArray(data.data) && data.data.length > 0) {
      return data.data
    }
    return []
  } catch (err) {
    console.warn(`[LocalidadeService] Erro ao buscar municípios da UF ${ufNormalizada} no backend.`, err)
    return []
  }
}
