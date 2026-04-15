import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

export function usePermissoes() {
  const authStore = useAuthStore()
  const permissoes = computed(() => authStore.permissoes)

  function hasPermissao(permissao: string): boolean {
    return permissoes.value.includes(permissao)
  }

  return {
    hasPermissao,
  }
}
