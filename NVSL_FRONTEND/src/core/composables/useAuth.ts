import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

export function useAuth() {
    const authStore = useAuthStore()

    return {
        user: computed(() => authStore.user),
        isAuthenticated: computed(() => authStore.isAuthenticated),
        isLoading: computed(() => authStore.isLoading),
        error: computed(() => authStore.error),
        userName: computed(() => authStore.userName),
        userEmail: computed(() => authStore.userEmail),
        permissoes: computed(() => authStore.permissoes),
        perfisAtivos: computed(() => authStore.perfisAtivos),
        possuiMultiplosPerfis: computed(() => authStore.possuiMultiplosPerfis),
        perfilAtivo: computed(() => authStore.perfilAtivo),
        trocandoContexto: computed(() => authStore.trocandoContexto),
        contextKey: computed(() => authStore.contextKey),
        temPermissao: authStore.temPermissao,
        trocarContexto: authStore.trocarContexto,
        logout: authStore.logout,
        clearError: authStore.clearError,
    }
}
