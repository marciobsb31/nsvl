import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * useAuth — Composable de autenticação
 *
 * Encapsula o authStore e expõe uma API simplificada
 * para uso nos componentes, separando a lógica da UI.
 */
export function useAuth() {
    const authStore = useAuthStore()

    return {
        // Estado
        user: computed(() => authStore.user),
        isAuthenticated: computed(() => authStore.isAuthenticated),
        isLoading: computed(() => authStore.isLoading),
        error: computed(() => authStore.error),
        userName: computed(() => authStore.userName),
        userEmail: computed(() => authStore.userEmail),

        // Ações (login(redirectTo?) — redirectTo ex: 'solicitacao-cadastro')
        login: authStore.login as (redirectTo?: string) => Promise<void>,
        logout: authStore.logout,
        handleCallback: authStore.handleCallback,
        clearError: authStore.clearError,
    }
}
