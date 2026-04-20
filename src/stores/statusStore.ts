import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { listarStatusSolicitacao } from '@/services/StatusService'

export const useStatusStore = defineStore('useStatus', () => {
  //state
  const statusSolicitacao = ref<any[]>([])
  const carregandoStatus = ref(false)

  //getters
  const statusOptions = computed(() => {
    return (statusSolicitacao.value ?? []).map((status) => ({
      label: status.nome,
      value: status.id,
    }))
  })

  //actions
  async function carregarStatus() {
    carregandoStatus.value = true
    try {
      statusSolicitacao.value = await listarStatusSolicitacao()
    } catch {
      statusSolicitacao.value = []
    } finally {
      carregandoStatus.value = false
    }
  }

  return {
    statusSolicitacao,
    carregandoStatus,
    statusOptions,
    carregarStatus,
  }
})
