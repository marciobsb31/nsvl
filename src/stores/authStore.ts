import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import AuthService from '@/services/AuthService'

export interface PerfilVigente {
    id: number
    nome: string
    data_inicio_vigencia?: string | null
    data_fim_vigencia?: string | null
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
    perfis_vigentes: PerfilVigente[]
}

/**
 * authStore — estado de autenticação (token de teste para desenvolvimento)
 */
export const useAuthStore = defineStore('auth', () => {
    const user = ref<AuthUser | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)

    const isAuthenticated = computed(() => !!user.value)
    const userName = computed(() => user.value?.name ?? '')
    const userEmail = computed(() => user.value?.email ?? '')

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
            perfis_vigentes: rawPerfis.map((p: Record<string, unknown>) => ({
                id: Number(p.id),
                nome: String(p.nome ?? ''),
                data_inicio_vigencia: p.data_inicio_vigencia ? String(p.data_inicio_vigencia) : null,
                data_fim_vigencia: p.data_fim_vigencia ? String(p.data_fim_vigencia) : null,
            })),
        }
    }

    async function logout(): Promise<void> {
        try {
            if (sessionStorage.getItem('nvsl_token')) {
                await AuthService.logout()
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
        isAuthenticated,
        userName,
        userEmail,
        setUser,
        logout,
        clearError,
    }
})
