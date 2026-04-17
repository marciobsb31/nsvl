import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { listarMunicipios } from '@/services/LocalidadeService'
import type { Municipio } from '@/core/types/localidades/LocalidadeInterface'

export const useMunicipioStore = defineStore('useMunicipioStore', () => {
  //state
  const municipiosLista = ref<Municipio[]>([])
  const carregandoMunicipio = ref(false)

  // getters
  const municipiosOptions = computed(() => {
    return (municipiosLista.value ?? []).map((municipio) => ({
      label: municipio.nome,
      value: String(municipio.id ?? ''),
    }))
  })

  //actions
  async function carregarMunicipios(uf: string) {
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
