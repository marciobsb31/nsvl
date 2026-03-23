import api from './ApiService'
import { OPCOES_UF, OPCOES_MUNICIPIOS } from '@/features/gerenciar-solicitacao-cadastro/constants/opcoesFiltro'

export interface LocalidadeOption {
  value: string
  label: string
}

export interface LocalidadesCompleto {
  ufs: LocalidadeOption[]
  municipios_por_uf: Record<string, LocalidadeOption[]>
}

/** Municípios por UF para fallback (amostra quando API falha) */
const FALLBACK_MUNICIPIOS: Record<string, LocalidadeOption[]> = {
  GO: OPCOES_MUNICIPIOS.filter((m) => ['Alexânia', 'Anápolis', 'Aparecida de Goiânia', 'Goiânia', 'Luziânia', 'Rio Verde'].includes(m.value)),
  DF: [{ value: 'Brasília', label: 'Brasília' }],
  SP: OPCOES_MUNICIPIOS.filter((m) => ['São Paulo', 'Campinas', 'Guarulhos'].includes(m.value)),
  MG: [{ value: 'Belo Horizonte', label: 'Belo Horizonte' }],
  PR: [{ value: 'Curitiba', label: 'Curitiba' }],
  RS: [{ value: 'Porto Alegre', label: 'Porto Alegre' }],
  BA: [{ value: 'Salvador', label: 'Salvador' }],
  CE: [{ value: 'Fortaleza', label: 'Fortaleza' }],
  PE: [{ value: 'Recife', label: 'Recife' }],
  AM: [{ value: 'Manaus', label: 'Manaus' }],
  PA: [{ value: 'Belém', label: 'Belém' }],
  SC: [{ value: 'Florianópolis', label: 'Florianópolis' }],
}

/**
 * Lista UFs e municípios de todas as UFs em uma única requisição.
 * Recomendado para carregar dados completos e cachear no frontend.
 */
export async function listarLocalidadesCompleto(): Promise<LocalidadesCompleto> {
  try {
    const { data } = await api.get<{ data: LocalidadesCompleto }>('/localidades/completo')
    const result = data.data
    if (result?.ufs && result?.municipios_por_uf) {
      return result
    }
    throw new Error('Resposta inválida')
  } catch (err) {
    console.warn('[LocalidadeService] API completo indisponível, usando endpoints separados.', err)
    const ufs = await listarUfs()
    const municipiosPorUf: Record<string, LocalidadeOption[]> = {}
    for (const uf of ufs) {
      municipiosPorUf[uf.value] = await listarMunicipios(uf.value)
    }
    return { ufs, municipios_por_uf: municipiosPorUf }
  }
}

/**
 * Lista todas as UFs (API com fallback estático).
 */
export async function listarUfs(): Promise<LocalidadeOption[]> {
  try {
    const { data } = await api.get<{ data: LocalidadeOption[] }>('/localidades/ufs')
    return data.data ?? OPCOES_UF
  } catch (err) {
    console.warn('[LocalidadeService] API UFs indisponível, usando dados estáticos.', err)
    return OPCOES_UF
  }
}

/**
 * Lista municípios da UF informada (API com fallback estático).
 */
export async function listarMunicipios(uf: string): Promise<LocalidadeOption[]> {
  if (!uf || uf.length !== 2) return []
  try {
    const { data } = await api.get<{ data: LocalidadeOption[] }>(
      `/localidades/municipios?uf=${encodeURIComponent(uf)}`
    )
    return data.data ?? (FALLBACK_MUNICIPIOS[uf] ?? [])
  } catch (err) {
    console.warn('[LocalidadeService] API municípios indisponível, usando dados estáticos.', err)
    return FALLBACK_MUNICIPIOS[uf] ?? []
  }
}
