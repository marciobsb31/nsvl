<template>
  <div class="filtros-perfis">
    <div class="row">
      <div class="col-12 col-md-4">
        <div class="br-input mb-2">
          <label for="filtro-nome">Nome do perfil</label>
          <input id="filtro-nome" type="text" placeholder="Nome do perfil" v-model="filtrosLocal.nome" />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.status" label="Situação" placeholder="Selecione" :options="OPCOES_STATUS" />
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.tipo" label="Tipo perfil"
          :placeholder="'Selecione o tipo'"
          :options="OPCOES_TIPO"  />
      </div>
  </div>
  <div class="filtros-acoes">
    <br-button emphasis="secondary" type="button" @click="limparFiltros" aria-label="Limpar filtros">
      Limpar Filtro
    </br-button>
    <br-button :color-mode="$appTheme === 'dark' ? 'dark' : undefined" emphasis="primary" type="button" @click="listar"
      :disabled="carregando" aria-label="Pesquisar solicitações">
      Pesquisar
    </br-button>
  </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch } from 'vue'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { OPCOES_STATUS, OPCOES_TIPO } from '../constants/opcoesFiltro'


defineOptions({ name: 'FiltrosGerenciarPerfis' })


const props = defineProps<{
  carregando?: boolean
}>()

const emit = defineEmits<{
  (e: 'pesquisar', filtros: any): void
  (e: 'limpar'): void
}>()

const filtrosLocal = reactive<any>({
  nome: undefined,
  status: undefined,
  tipo: undefined,
})

function listar() {
  const f: any = {}
  if (filtrosLocal.nome?.trim()) f.nome = filtrosLocal.nome.trim()
  if (filtrosLocal.status) f.status = filtrosLocal.status
  if (filtrosLocal.tipo?.trim()) f.tipo = filtrosLocal.tipo.trim()
  emit('pesquisar', f)
}

function limparFiltros() {
  filtrosLocal.nome = undefined
  filtrosLocal.status = undefined
  filtrosLocal.tipo = undefined
  emit('limpar')
}


</script>

<style scoped>
.filtros-perfis {
  margin-bottom: 1.5rem;
}

.filtros-acoes {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

@media (max-width: 575px) {

  .filtros-acoes {
    flex-direction: column;
  }

  .filtros-acoes .br-button {
    width: 100%;
  }
}

</style>
