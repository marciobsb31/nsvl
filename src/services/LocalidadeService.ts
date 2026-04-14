import api from './ApiService'
import type { uf, Municipio } from '../core/types/localidades/LocalidadeInterface'

/**
 * Lista todas as UFs (somente API backend).
 */
export async function listarUfs(): Promise<uf[]> {
  try {
    const { data } = await api.get<{ data: uf[] }>('/localidades/ufs')
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
export async function listarMunicipios(uf: string): Promise<Municipio[]> {
  if (!uf || uf.length !== 2) return []
  const ufNormalizada = uf.toUpperCase()
  try {
    const { data } = await api.get<{ data: Municipio[] }>(
      `/localidades/municipios/${encodeURIComponent(ufNormalizada)}`,
    )
    if (Array.isArray(data.data) && data.data.length > 0) {
      return data.data
    }
    return []
  } catch (err) {
    console.warn(
      `[LocalidadeService] Erro ao buscar municípios da UF ${ufNormalizada} no backend.`,
      err,
    )
    return []
  }
}
