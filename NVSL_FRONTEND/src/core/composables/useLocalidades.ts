import type { Ref } from 'vue'
import { ref, watch } from 'vue'
import {
  listarLocalidadesCompleto,
  listarUfs,
  listarMunicipios,
  type LocalidadeOption,
  type LocalidadesCompleto,
} from '@/services/LocalidadeService'

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

  async function carregarUfs() {
    carregandoUf.value = true
    try {
      // Primeiro carrega UFs rapidamente para destravar o select.
      opcoesUf.value = await listarUfs()

      if (cacheLocalidades) {
        opcoesUf.value = cacheLocalidades.ufs.length ? cacheLocalidades.ufs : opcoesUf.value
        if (ufRef?.value) {
          opcoesMunicipio.value = cacheLocalidades.municipios_por_uf[ufRef.value] ?? []
        }
        return
      }

      try {
        const dados = await listarLocalidadesCompleto()
        cacheLocalidades = dados
        if (dados.ufs.length) {
          opcoesUf.value = dados.ufs
        }
        if (ufRef?.value) {
          opcoesMunicipio.value = dados.municipios_por_uf[ufRef.value] ?? []
        }
      } catch {
        // Mantém as UFs já carregadas via /localidades/ufs.
      }
    } catch {
      opcoesUf.value = []
    } finally {
      carregandoUf.value = false
    }
  }

  async function carregarMunicipios(uf: string) {
    if (!uf || uf.length !== 2) {
      opcoesMunicipio.value = []
      return
    }
    if (cacheLocalidades) {
      opcoesMunicipio.value = cacheLocalidades.municipios_por_uf[uf] ?? []
      return
    }
    carregandoMunicipio.value = true
    try {
      opcoesMunicipio.value = await listarMunicipios(uf)
    } catch {
      opcoesMunicipio.value = []
    } finally {
      carregandoMunicipio.value = false
    }
  }

  if (ufRef) {
    watch(
      () => ufRef.value,
      (novaUf) => {
        carregarMunicipios(novaUf ?? '')
      },
      { immediate: true }
    )
  }

  return {
    opcoesUf,
    opcoesMunicipio,
    carregandoUf,
    carregandoMunicipio,
    carregarUfs,
    carregarMunicipios,
  }
}
