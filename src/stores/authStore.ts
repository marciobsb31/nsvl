import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/ApiService'
import type { AuthUser } from '@/core/types/usuario/UsuarioInterface'

export const useAuthStore = defineStore('useAuthStore', () => {
  const user = ref<AuthUser | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)
  const trocandoContexto = ref(false)
  const contextKey = ref(0)

  const isAuthenticated = computed(() => !!user.value)
  const userName = computed(() => user.value?.name ?? '')
  const userEmail = computed(() => user.value?.email ?? '')

  const perfisAtivos = computed(() => user.value?.perfis ?? [])

  const possuiMultiplosPerfis = computed(() => perfisAtivos.value.length > 1)
  const permissoes = computed(() => user.value?.permissions ?? [])

  const perfilAtivo = computed(() => {
    if (!user.value) return null
    const ativoFromApi = perfisAtivos.value.find((p) => p.ativo === true)
    if (ativoFromApi) return ativoFromApi
    return perfisAtivos.value[0] ?? null
  })

  function setUser(data: Record<string, unknown> | null): void {
    if (!data) {
      user.value = null
      return
    }

    const payload =
      (data.user && typeof data.user === 'object'
        ? (data.user as Record<string, unknown>)
        : null) ??
      (data.data && typeof data.data === 'object'
        ? (data.data as Record<string, unknown>)
        : null) ??
      data

    const rawPerfis = Array.isArray(payload.perfis) ? payload.perfis : []
    const rawContexto =
      payload.contexto && typeof payload.contexto === 'object'
        ? (payload.contexto as Record<string, unknown>)
        : null
    user.value = {
      id: Number(payload.id),
      name: String(payload.name ?? ''),
      email: payload.email ? String(payload.email) : undefined,
      sub: payload.sub ? String(payload.sub) : undefined,
      contexto: {
        esfera: rawContexto?.esfera ? String(rawContexto.esfera) : '',
        localidade: rawContexto?.localidade ? String(rawContexto.localidade) : '',
        perfil: rawContexto?.perfil ? String(rawContexto.perfil) : '',
      },
      perfis: rawPerfis,
      permissions: payload.permissions ? (payload.permissions as string[]) : [],
    }
  }

  function temPermissao(_modulo: string, _acao?: string): boolean {
    const esfera = user.value?.contexto.esfera ?? 'federal'
    if (esfera.toLowerCase() === 'federal') return true
    return perfisAtivos.value.length > 0
  }

  async function trocarContexto(perfilUsuarioId: number): Promise<void> {
    trocandoContexto.value = true
    error.value = null
    try {
      const { data } = await api.post<{ user: Record<string, unknown> }>('/user/trocar-contexto', {
        perfil_usuario_id: perfilUsuarioId,
      })
      setUser(data.user)
      contextKey.value++
    } catch (e: unknown) {
      const msg =
        (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
        'Erro ao trocar contexto.'
      error.value = msg
      throw e
    } finally {
      trocandoContexto.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      if (sessionStorage.getItem('nvsl_token')) {
        await api.post('/auth/logout')
      }
    } catch {
      // Ignora falhas no logout remoto e limpa o estado local mesmo assim.
    } finally {
      sessionStorage.removeItem('nvsl_token')
      user.value = null
    }
  }

  function clearError(): void {
    error.value = null
  }

  return {
    user,
    isLoading,
    error,
    trocandoContexto,
    contextKey,
    isAuthenticated,
    userName,
    userEmail,
    perfisAtivos,
    possuiMultiplosPerfis,
    permissoes,
    perfilAtivo,
    setUser,
    temPermissao,
    trocarContexto,
    logout,
    clearError,
  }
})
