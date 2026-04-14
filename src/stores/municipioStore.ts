import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { listarMunicipios } from '@/services/LocalidadeService'
import type { LocalidadeOption, Municipio } from '@/core/types/localidades/LocalidadeInterface'

export const useMunicipioStore = defineStore('useMunicipioStore', () => {
  //state
  const municipiosLista = ref<Municipio[]>([])
  const carregandoMunicipio = ref(false)

  // getters
  const municipiosOptions = computed(() => {
    console.log('municipiosLista', municipiosLista.value)
    return (municipiosLista.value ?? []).map((municipio) => ({
      label: municipio.nome,
      value: String(municipio.id ?? ''),
    }))
  })

  //actions
  async function carregarMunicipios(uf: string) {
    if (!uf || uf.length !== 2) return
    try {
      municipiosLista.value = await listarMunicipios(uf)
    } catch {
      municipiosLista.value = []
    } finally {
      carregandoMunicipio.value = false
    }
  }

  return {
    municipiosLista,
    carregandoMunicipio,
    municipiosOptions,
    carregarMunicipios,
  }
})
