import api from '@/services/ApiService'

export interface PlanoAcaoAnexo {
  id: number
  usuario_id?: number
  nome_original: string
  tipo_mime: string
  tamanho_bytes: number
  created_at?: string
}

export async function listarAnexos(): Promise<PlanoAcaoAnexo[]> {
  const res = await api.get<{ data: PlanoAcaoAnexo[] }>('/plano-acao/anexos')
  return res.data.data ?? []
}

export async function uplodarAnexo(arquivo: File): Promise<PlanoAcaoAnexo> {
  const formData = new FormData()
  formData.append('arquivo', arquivo)
  const res = await api.post<{ data: PlanoAcaoAnexo }>('/plano-acao/anexos', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return res.data.data
}

export async function removerAnexo(id: number): Promise<void> {
  await api.delete(`/plano-acao/anexos/${id}`)
}
