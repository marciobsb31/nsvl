import type { Ref } from 'vue'
import { ref, watch } from 'vue'
import { listarUfs, listarMunicipios } from '@/services/LocalidadeService'
import type {
  LocalidadeOption,
  LocalidadesCompleto,
} from '@/core/types/localidades/LocalidadeInterface'

/** Cache global das localidades completas (UFs + municípios por UF) */
let cacheLocalidades: LocalidadesCompleto | null = null

/**
 * Composable para carregar UFs e municípios da API.
 * Usa o endpoint /localidades/completo para carregar tudo de uma vez.
 * @param ufRef - Ref da UF selecionada (opcional). Quando informado, carrega municípios ao mudar UF.
 */
export function useLocalidades(ufRef?: Ref<string | undefined>) {
  const opcoesUf = ref<LocalidadeOption[]>([])
  const opcoesMunicipio = ref<LocalidadeOption[]>([])
  const carregandoUf = ref(false)
  const carregandoMunicipio = ref(false)

  async function carregarUfs() {}

  async function carregarMunicipios(uf: string) {}

  return {
    opcoesUf,
    opcoesMunicipio,
    carregandoUf,
    carregandoMunicipio,
    carregarUfs,
    carregarMunicipios,
  }
}
