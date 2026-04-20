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

  const perfisAtivos = computed(() => {
    const hoje = new Date().toISOString().slice(0, 10)
    return (user.value?.perfis ?? []).filter((perfil) => {
      if (!perfil.ativo) return false
      const inicio = perfil.data_inicio_vigencia
        ? String(perfil.data_inicio_vigencia).slice(0, 10)
        : null
      const fim = perfil.data_fim_vigencia ? String(perfil.data_fim_vigencia).slice(0, 10) : null
      const inicioValido = !inicio || inicio <= hoje
      const fimValido = !fim || fim >= hoje
      return inicioValido && fimValido
    })
  })

  const possuiMultiplosPerfis = computed(() => perfisAtivos.value.length > 1)
  const permissoes = computed(() => user.value?.permissoes ?? [])

  const HIERARQUIA_ESFERA: Record<string, number> = {
    federal: 1,
    estadual: 2,
    municipal: 3,
  }

  function prioridadeEsfera(esfera?: string): number {
    return (
      HIERARQUIA_ESFERA[
        String(esfera ?? '')
          .toLowerCase()
          .trim()
      ] ?? 99
    )
  }

  const perfilHierarquicoMaisAlto = computed(() => {
    if (perfisAtivos.value.length === 0) return null
    return [...perfisAtivos.value].sort(
      (a, b) => prioridadeEsfera(a.esfera) - prioridadeEsfera(b.esfera),
    )[0]
  })

  const perfilAtivo = computed(() => {
    if (!user.value) return null
    const ativoFromApi =
      perfisAtivos.value.find((p) => p.id === Number(user.value?.contexto?.perfil_usuario_id)) ??
      perfisAtivos.value.find((p) => p.ativo === true)
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

    const rawEsfera =
      rawContexto?.esfera && typeof rawContexto.esfera === 'object'
        ? (rawContexto.esfera as Record<string, unknown>)
        : null
    user.value = {
      id: Number(payload.id),
      name: String(payload.name ?? ''),
      email: payload.email ? String(payload.email) : undefined,
      sub: payload.sub ? String(payload.sub) : undefined,
      contexto: {
        esfera: {
          id: rawEsfera?.id ? Number(rawEsfera.id) : 0,
          nome: rawEsfera?.nome ? String(rawEsfera.nome) : '',
        },
        localidade: rawContexto?.localidade ? String(rawContexto.localidade) : '',
        perfil: rawContexto?.perfil ? String(rawContexto.perfil) : '',
        perfil_usuario_id: rawContexto?.perfil_usuario_id
          ? Number(rawContexto.perfil_usuario_id)
          : undefined,
        uf_id: rawContexto?.uf_id ? Number(rawContexto.uf_id) : undefined,
        municipio_id: rawContexto?.municipio_id ? Number(rawContexto.municipio_id) : undefined,
      },
      perfis: rawPerfis,
      permissoes: payload.permissoes ? (payload.permissoes as string[]) : [],
    }
  }

  const MODULOS_APENAS_GESTORES = ['Gerenciar Perfis', 'Gerenciar Cadastros']

  function temPermissao(modulo: string, _acao?: string): boolean {
    const perfilNome = String(user.value?.contexto?.perfil ?? '').toLowerCase()
    const ehAdministradorOuVisitante =
      perfilNome.includes('administrador') || perfilNome.includes('visitante')

    if (MODULOS_APENAS_GESTORES.includes(modulo) && ehAdministradorOuVisitante) {
      return false
    }

    const esferaContexto = String(user.value?.contexto?.esfera ?? '')
      .toLowerCase()
      .trim()
    const esferaPerfilAtivo = String(perfilAtivo.value?.esfera ?? '')
      .toLowerCase()
      .trim()
    if (esferaContexto === 'federal' || esferaPerfilAtivo === 'federal') {
      return true
    }

    const lista = permissoes.value
    if (lista.includes(modulo)) return true

    const mapeamentoPorModulo: Record<string, string[]> = {
      'Gerenciar Cadastros': ['solicitacoes_cadastro.'],
      'Plano de Ação': ['plano_acao.'],
      Relatórios: ['relatorio_execucao.'],
      'Gerenciar Perfis': ['perfis.'],
    }

    const prefixes = mapeamentoPorModulo[modulo] ?? []
    if (prefixes.length > 0) {
      return lista.some((permissao) => prefixes.some((prefix) => permissao.startsWith(prefix)))
    }

    return false
  }

  async function trocarContexto(perfilUsuarioId: number): Promise<void> {
    trocandoContexto.value = true
    error.value = null
    try {
      const { data } = await api.post<{ user?: Record<string, unknown> }>('/contextos/selecionar', {
        perfil_usuario_id: perfilUsuarioId,
      })

      if (data?.user) {
        setUser(data.user)
      } else {
        const usuarioAtualizado = await api.get<Record<string, unknown>>('/usuario')
        setUser(usuarioAtualizado.data)
      }

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

  async function refreshUser(): Promise<void> {
    if (!sessionStorage.getItem('nvsl_token')) return
    try {
      const { data } = await api.get<Record<string, unknown>>('/usuario')
      setUser(data)
    } catch {
      // Ignora silenciosamente — não desautentica em caso de falha de rede
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
    perfilHierarquicoMaisAlto,
    setUser,
    temPermissao,
    trocarContexto,
    refreshUser,
    logout,
    clearError,
  }
})
