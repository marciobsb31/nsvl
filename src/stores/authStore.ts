import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/ApiService'

export interface AuthUser {
    id: number
    name: string
    email?: string
    esfera_atuacao?: string
    uf_lotacao?: string
    municipio_lotacao?: string
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
        user.value = {
            id: Number(data.id),
            name: String(data.name ?? ''),
            email: data.email ? String(data.email) : undefined,
            esfera_atuacao: data.esfera_atuacao ? String(data.esfera_atuacao) : undefined,
            uf_lotacao: data.uf_lotacao ? String(data.uf_lotacao) : undefined,
            municipio_lotacao: data.municipio_lotacao ? String(data.municipio_lotacao) : undefined,
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
        isAuthenticated,
        userName,
        userEmail,
        setUser,
        logout,
        clearError,
    }
})
