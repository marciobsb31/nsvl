import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/AuthService'
import type { GovBrUser } from '@/core/types/auth'

/**
 * authStore — estado global de autenticação
 *
 * Utiliza Composition API style com defineStore para melhor
 * inferência de tipos e código mais limpo.
 */
export const useAuthStore = defineStore('auth', () => {
    const user = ref<GovBrUser | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)

    // Getters computados
    const isAuthenticated = computed(() => !!user.value)
    const userName = computed(() => user.value?.name ?? '')
    const userEmail = computed(() => user.value?.email ?? '')

    /**
     * Carrega o usuário da sessão OIDC (chamado ao iniciar a aplicação)
     */
    async function loadUser(): Promise<void> {
        isLoading.value = true
        error.value = null
        try {
            user.value = await authService.getUser()
        } catch (err) {
            console.error('[AuthStore] Erro ao carregar usuário:', err)
            error.value = 'Falha ao carregar dados do usuário.'
            user.value = null
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Inicia o fluxo de login — redireciona para GOV.BR SSO
     * @param redirectTo - Rota para redirecionar após login (ex: 'solicitacao-cadastro')
     */
    async function login(redirectTo?: string): Promise<void> {
        isLoading.value = true
        error.value = null
        try {
            await authService.login(redirectTo)
        } catch (err) {
            console.error('[AuthStore] Erro ao iniciar login:', err)
            error.value = 'Não foi possível iniciar o login. Tente novamente.'
            isLoading.value = false
        }
    }

    /**
     * Processa o callback do SSO GOV.BR e extrai o token da URL
     */
    async function handleCallback(): Promise<void> {
        isLoading.value = true
        error.value = null
        try {
            const params = new URLSearchParams(window.location.search)
            const errorParam = params.get('error')
            if (errorParam) {
                error.value = errorParam
                user.value = null
                return
            }

            const token = params.get('token')
            if (token) {
                authService.setToken(token)
                user.value = await authService.getUser()
            } else {
                throw new Error('Token não encontrado na URL de callback')
            }
        } catch (err) {
            console.error('[AuthStore] Erro no callback de autenticação:', err)
            error.value = (err as Error).message || 'Falha na autenticação. Por favor, tente novamente.'
            user.value = null
        } finally {
            isLoading.value = false
        }
    }

    /**
     * Realiza logout — limpa estado e redireciona para SSO
     */
    async function logout(redirectTo?: string): Promise<void> {
        isLoading.value = true
        try {
            user.value = null
            await authService.logout(redirectTo)
        } catch (err) {
            console.error('[AuthStore] Erro ao realizar logout:', err)
            isLoading.value = false
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
        loadUser,
        login,
        handleCallback,
        logout,
        clearError,
    }
})
