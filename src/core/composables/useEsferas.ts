import { ref } from 'vue'
import { listarEsferas, type EsferaOption } from '@/services/EsferaService'

const FALLBACK_ESFERAS: EsferaOption[] = [
  { value: 'federal', label: 'Federal' },
  { value: 'estadual', label: 'Estadual' },
  { value: 'municipal', label: 'Municipal' },
]

// Singleton: estado compartilhado entre todos os componentes
const opcoesEsferaShared = ref<EsferaOption[]>([...FALLBACK_ESFERAS])
const carregandoShared = ref(false)
let carregadoUmaVez = false

/**
 * Composable para carregar esferas de atuação da API.
 * Usa singleton: uma única fonte de verdade, carregada uma vez.
 * Inicializa com fallback estático para exibir opções imediatamente.
 */
export function useEsferas() {
  async function carregarEsferas() {
    if (carregandoShared.value) return
    carregandoShared.value = true
    try {
      const dados = await listarEsferas()
      if (Array.isArray(dados) && dados.length > 0) {
        opcoesEsferaShared.value = dados
        carregadoUmaVez = true
      } else if (!carregadoUmaVez) {
        opcoesEsferaShared.value = [...FALLBACK_ESFERAS]
      }
    } catch {
      if (!carregadoUmaVez) {
        opcoesEsferaShared.value = [...FALLBACK_ESFERAS]
      }
    } finally {
      carregandoShared.value = false
    }
  }

  return {
    opcoesEsfera: opcoesEsferaShared,
    carregando: carregandoShared,
    carregarEsferas,
  }
}
