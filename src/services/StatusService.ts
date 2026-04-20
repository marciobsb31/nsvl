import api from './ApiService'

export async function listarStatusSolicitacao(): Promise<any[]> {
  try {
    const { data } = await api.get<{ data: any[] }>('status-solicitacao')
    if (Array.isArray(data.data) && data.data.length > 0) {
      return data.data
    }
    return []
  } catch (err) {
    console.warn(`[StatusService] Erro ao buscar status de solicitação`, err)
    return []
  }
}
