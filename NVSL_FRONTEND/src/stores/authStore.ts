import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/ApiService'

export interface PerfilVigente {
    perfil_usuario_id: number
    perfil_id: number
    nome: string
    esfera?: string
    uf?: string | null
    municipio?: string | null
    orgao?: string | null
    data_inicio_vigencia?: string | null
    data_fim_vigencia?: string | null
}

export interface Permissao {
    id: number
    modulo: string
    acao: string
}

export interface AuthUser {
    id: number
    name: string
    email?: string
    picture?: string
    role?: string
    esfera_atuacao?: string
    uf_lotacao?: string
    municipio_lotacao?: string
    perfil_usuario_ativo_id?: number | null
    perfis_vigentes: PerfilVigente[]
    permissoes: Permissao[]
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthUser | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)
    const trocandoContexto = ref(false)
    const contextKey = ref(0)

    const isAuthenticated = computed(() => !!user.value)
    const userName = computed(() => user.value?.name ?? '')
    const userEmail = computed(() => user.value?.email ?? '')

    const permissoes = computed(() => user.value?.permissoes ?? [])

    const perfisAtivos = computed(() => user.value?.perfis_vigentes ?? [])

    const possuiMultiplosPerfis = computed(() => perfisAtivos.value.length > 1)

    const perfilAtivo = computed(() => {
        if (!user.value) return null
        const ativoId = user.value.perfil_usuario_ativo_id
        if (ativoId) {
            return perfisAtivos.value.find(p => p.perfil_usuario_id === ativoId) ?? perfisAtivos.value[0] ?? null
        }
        return perfisAtivos.value[0] ?? null
    })

    function setUser(data: Record<string, unknown> | null): void {
        if (!data) {
            user.value = null
            return
        }
        const rawPerfis = Array.isArray(data.perfis_vigentes) ? data.perfis_vigentes : []
        user.value = {
            id: Number(data.id),
            name: String(data.name ?? ''),
            email: data.email ? String(data.email) : undefined,
            picture: data.picture ? String(data.picture) : undefined,
            role: data.role ? String(data.role) : undefined,
            esfera_atuacao: data.esfera_atuacao ? String(data.esfera_atuacao) : undefined,
            uf_lotacao: data.uf_lotacao ? String(data.uf_lotacao) : undefined,
            municipio_lotacao: data.municipio_lotacao ? String(data.municipio_lotacao) : undefined,
            perfil_usuario_ativo_id: data.perfil_usuario_ativo_id ? Number(data.perfil_usuario_ativo_id) : null,
            perfis_vigentes: rawPerfis.map((p: Record<string, unknown>) => ({
                perfil_usuario_id: Number(p.perfil_usuario_id),
                perfil_id: Number(p.perfil_id),
                nome: String(p.nome ?? ''),
                esfera: p.esfera ? String(p.esfera) : undefined,
                uf: p.uf ? String(p.uf) : null,
                municipio: p.municipio ? String(p.municipio) : null,
                orgao: p.orgao ? String(p.orgao) : null,
                data_inicio_vigencia: p.data_inicio_vigencia ? String(p.data_inicio_vigencia) : null,
                data_fim_vigencia: p.data_fim_vigencia ? String(p.data_fim_vigencia) : null,
            })),
            permissoes: (Array.isArray(data.permissoes) ? data.permissoes : []).map((perm: Record<string, unknown>) => ({
                id: Number(perm.id),
                modulo: String(perm.modulo ?? ''),
                acao: String(perm.acao ?? ''),
            })),
        }
    }

    function temPermissao(modulo: string, acao?: string): boolean {
        const perms = permissoes.value
        if (!perms.length) return true
        if (acao) return perms.some(p => p.modulo === modulo && p.acao === acao)
        return perms.some(p => p.modulo === modulo)
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
            const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
                ?? 'Erro ao trocar contexto.'
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
        permissoes,
        perfisAtivos,
        possuiMultiplosPerfis,
        perfilAtivo,
        setUser,
        temPermissao,
        trocarContexto,
        logout,
        clearError,
    }
})
