import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { listarUfs } from '@/services/LocalidadeService'
import type { uf } from '@/core/types/localidades/LocalidadeInterface'

export const useUfStore = defineStore('useUfStore', () => {
  //state
  const ufsLista = ref<uf[]>([])
  const carregandoUf = ref(false)

  //getters
  const ufsOptions = computed(() => {
    return (ufsLista.value ?? []).map((uf) => ({
      label: uf.sigla + ' - ' + uf.nome,
      value: String(uf.id ?? ''),
    }))
  })

  //actions
  async function carregarUfs() {
    carregandoUf.value = true
    try {
      ufsLista.value = await listarUfs()
    } catch {
      ufsLista.value = []
    } finally {
      carregandoUf.value = false
    }
  }

  return {
    ufsLista,
    carregandoUf,
    ufsOptions,
    carregarUfs,
  }
})
