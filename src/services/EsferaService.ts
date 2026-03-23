import api from './ApiService'
import { OPCOES_ESFERA } from '@/features/gerenciar-solicitacao-cadastro/constants/opcoesFiltro'

export interface EsferaOption {
  value: string
  label: string
}

/**
 * Lista todas as esferas de atuação (API com fallback estático).
 * Sempre retorna array válido (nunca undefined).
 */
export async function listarEsferas(): Promise<EsferaOption[]> {
  try {
    const { data } = await api.get<{ data?: EsferaOption[] }>('/esferas')
    const lista = data?.data
    if (Array.isArray(lista) && lista.length > 0) {
      return lista
    }
    return OPCOES_ESFERA
  } catch (err) {
    console.warn('[EsferaService] API esferas indisponível, usando dados estáticos.', err)
    return OPCOES_ESFERA
  }
}
