import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

/**
 * useAuth — Composable de autenticação
 */
export function useAuth() {
    const authStore = useAuthStore()

    return {
        user: computed(() => authStore.user),
        isAuthenticated: computed(() => authStore.isAuthenticated),
        isLoading: computed(() => authStore.isLoading),
        error: computed(() => authStore.error),
        userName: computed(() => authStore.userName),
        userEmail: computed(() => authStore.userEmail),
        logout: authStore.logout,
        clearError: authStore.clearError,
    }
}
