import { ref } from 'vue'
import { listarPerfis, type PerfilOption } from '@/services/PerfilService'

const FALLBACK_PERFIS: PerfilOption[] = [
  { value: 1, label: 'Gestor Federal' },
  { value: 3, label: 'Gestor Estadual' },
  { value: 4, label: 'Gestor Municipal' },
  { value: 2, label: 'Administrador Federal' },
  { value: 5, label: 'Administrador Estadual' },
  { value: 6, label: 'Administrador Municipal' },
  { value: 7, label: 'Visitante Federal' },
  { value: 8, label: 'Visitante Estadual' },
  { value: 9, label: 'Visitante Municipal' },
]

// Singleton: estado compartilhado entre todos os componentes
const opcoesPerfilShared = ref<PerfilOption[]>([...FALLBACK_PERFIS])
const carregandoShared = ref(false)
let carregadoUmaVez = false

/**
 * Composable para carregar perfis da API.
 * Usa singleton: uma única fonte de verdade, carregada uma vez.
 * Inicializa com fallback estático para exibir opções imediatamente.
 */
export function usePerfis() {
  async function carregarPerfis() {
    if (carregandoShared.value) return
    carregandoShared.value = true
    try {
      const dados = await listarPerfis()
      if (Array.isArray(dados) && dados.length > 0) {
        opcoesPerfilShared.value = dados
        carregadoUmaVez = true
      } else if (!carregadoUmaVez) {
        opcoesPerfilShared.value = [...FALLBACK_PERFIS]
      }
    } catch {
      if (!carregadoUmaVez) {
        opcoesPerfilShared.value = [...FALLBACK_PERFIS]
      }
    } finally {
      carregandoShared.value = false
    }
  }

  return {
    opcoesPerfil: opcoesPerfilShared,
    carregando: carregandoShared,
    carregarPerfis,
  }
}
