import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { listarEsferas, type EsferaOption } from '@/services/EsferaService'

export const useEsferasStore = defineStore('useEsferasStore', () => {
  //state
  const esferasLista = ref<any[]>([])
  const carregandoEsferas = ref(false)

  //getters
  const esferasOptions = computed(() => {
    return (esferasLista.value ?? []).map((esfera) => ({
      label: esfera.nome,
      value: esfera.id,
    }))
  })

  //actions
  async function carregarEsferas() {
    carregandoEsferas.value = true
    try {
      esferasLista.value = await listarEsferas()
    } catch {
      esferasLista.value = []
    } finally {
      carregandoEsferas.value = false
    }
  }

  return {
    esferasLista,
    carregandoEsferas,
    esferasOptions,
    carregarEsferas,
  }
})
