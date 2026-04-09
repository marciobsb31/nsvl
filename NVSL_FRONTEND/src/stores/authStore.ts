import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/ApiService'

export interface PerfilVigente {
    perfil_usuario_id: number
    perfil_id: number
    nome: string
    esfera?: string | null
    uf?: string | null
    municipio?: string | null
    orgao?: string | null
    data_inicio_vigencia?: string | null
    data_fim_vigencia?: string | null
    ativo?: boolean
}

export interface AuthUser {
    id: number
    name: string
    email?: string
    sub?: string
    esfera_atuacao?: string
    uf_lotacao?: string
    municipio_lotacao?: string
    perfil_ativo_id?: number | null
    perfis_vigentes: PerfilVigente[]
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

    const perfisAtivos = computed(() => user.value?.perfis_vigentes ?? [])

    const possuiMultiplosPerfis = computed(() => perfisAtivos.value.length > 1)

    const perfilAtivo = computed(() => {
        if (!user.value) return null
        const ativoFromApi = perfisAtivos.value.find(p => p.ativo === true)
        if (ativoFromApi) return ativoFromApi
        const ativoId = user.value.perfil_ativo_id
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
            sub: data.sub ? String(data.sub) : undefined,
            esfera_atuacao: data.esfera_atuacao ? String(data.esfera_atuacao) : undefined,
            uf_lotacao: data.uf_lotacao ? String(data.uf_lotacao) : undefined,
            municipio_lotacao: data.municipio_lotacao ? String(data.municipio_lotacao) : undefined,
            perfil_ativo_id: data.perfil_ativo_id ? Number(data.perfil_ativo_id) : null,
            perfis_vigentes: rawPerfis.map((p: Record<string, unknown>) => ({
                perfil_usuario_id: Number(p.perfil_usuario_id),
                perfil_id: Number(p.perfil_id),
                nome: String(p.nome ?? ''),
                data_inicio_vigencia: p.data_inicio_vigencia ? String(p.data_inicio_vigencia) : null,
                data_fim_vigencia: p.data_fim_vigencia ? String(p.data_fim_vigencia) : null,
                ativo: p.ativo === true || p.ativo === 'true',
            })),
        }
    }

    /**
     * MVP: Permissões por perfil ativo conforme regras de negócio (RN02).
     *
     * Módulos reconhecidos:
     *  - 'Gerenciar Cadastros'   → Gestão de Solicitações de Cadastro
     *  - 'Gerenciar Perfis'      → Tela de gerência de perfis (somente leitura no MVP)
     *  - 'Plano de Ação'         → Gestão + Envio de plano de ação
     *  - 'Enviar Plano de Ação'  → Envio de plano de ação
     *  - 'Relatórios'            → Relatórios e exportações
     */
    function temPermissao(modulo: string, _acao?: string): boolean {
        const nomePerfil = perfilAtivo.value?.nome ?? ''
        if (!nomePerfil) return false

        const n = nomePerfil.toLowerCase()

        // Gestor Federal: acesso integral
        if (n === 'gestor federal') return true

        // Gestores Estadual/Municipal: acesso a tudo (dentro do seu escopo territorial)
        if (n === 'gestor estadual' || n === 'gestor municipal') return true

        // Administrador Estadual/Municipal:
        //  - SEM acesso a Gerenciar Cadastros
        //  - SEM acesso a Enviar plano de ação
        //  - SEM acesso a Gerenciar Perfis
        if (n === 'administrador estadual' || n === 'administrador municipal') {
            const moduloLower = modulo.toLowerCase()
            if (moduloLower === 'gerenciar cadastros') return false
            if (moduloLower === 'enviar plano de ação' || moduloLower === 'enviar plano de acao') return false
            if (moduloLower === 'gerenciar perfis') return false
            return true
        }

        // Visitante Federal: acesso somente leitura a tudo
        if (n === 'visitante federal') {
            // Tem acesso de leitura a todas as funcionalidades
            return true
        }

        // Visitante Estadual/Municipal:
        //  - SEM acesso a Gerenciar Cadastros
        //  - SEM acesso a Gerenciar Perfis
        //  - Acesso somente consulta ao restante
        if (n === 'visitante estadual' || n === 'visitante municipal') {
            const moduloLower = modulo.toLowerCase()
            if (moduloLower === 'gerenciar cadastros') return false
            if (moduloLower === 'gerenciar perfis') return false
            return true
        }

        return false
    }

    /**
     * Verifica se o perfil ativo é do tipo Visitante (somente leitura).
     */
    const isVisitante = computed(() => {
        const n = (perfilAtivo.value?.nome ?? '').toLowerCase()
        return n.startsWith('visitante')
    })

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
        perfisAtivos,
        possuiMultiplosPerfis,
        perfilAtivo,
        isVisitante,
        setUser,
        temPermissao,
        trocarContexto,
        logout,
        clearError,
    }
})
