import api from './ApiService'
import { OPCOES_ESFERA } from '@/features/gerenciar-solicitacao-cadastro/constants/opcoesFiltro'

export interface EsferaOption {
  value: string
  label: string
}

function normalizarEsferas(lista: unknown[]): EsferaOption[] {
  return lista
    .map((item) => {
      if (!item || typeof item !== 'object') return null
      const esfera = item as {
        value?: string | number
        label?: string
        id?: string | number
        nome?: string
      }

      const value = esfera.value ?? esfera.id
      const label = String(esfera.label ?? esfera.nome ?? '').trim()

      if ((typeof value !== 'string' && typeof value !== 'number') || !label) {
        return null
      }

      return { value: String(value), label }
    })
    .filter((item): item is EsferaOption => !!item)
}

/**
 * Lista todas as esferas de atuação (API com fallback estático).
 * Sempre retorna array válido (nunca undefined).
 */
export async function listarEsferas(): Promise<EsferaOption[]> {
  try {
    const { data } = await api.get<{ data?: unknown[] }>('/esferas')
    const lista = data?.data
    if (Array.isArray(lista) && lista.length > 0) {
      const normalizada = normalizarEsferas(lista)
      if (normalizada.length > 0) {
        return normalizada
      }
    }
    return OPCOES_ESFERA
  } catch (err) {
    console.warn('[EsferaService] API esferas indisponível, usando dados estáticos.', err)
    return OPCOES_ESFERA
  }
}
