<template>
  <div class="filtros-gerenciar">
    <div class="row filtros">
      <div class="col-12 col-md-6">
        <div class="br-input mb-2">
          <label for="filtro-cpf">CPF</label>
          <input id="filtro-cpf" type="text" placeholder="000.000.000-00" v-model="filtrosLocal.cpf"
            v-maska="'###.###.###-##'" />
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="br-input mb-2">
          <label for="filtro-nome">Nome completo</label>
          <input id="filtro-nome" type="text" placeholder="Informe o nome" v-model="filtrosLocal.nome" />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.uf" label="Estado (UF)" placeholder="Selecione" :options="opcoesUf" />
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.municipio" label="Município"
          :placeholder="filtrosLocal.uf ? 'Selecione o município' : 'Selecione primeiro a UF'"
          :options="opcoesMunicipio" :disabled="!filtrosLocal.uf" />
      </div>
      <div class="col-12 col-md-4">
        <div class="br-input mb-2">
          <label for="filtro-orgao">Órgão de atuação</label>
          <input id="filtro-orgao" type="text" placeholder="Órgão" v-model="filtrosLocal.orgao" />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.esfera" label="Esfera de atuação" placeholder="Selecione"
          :options="opcoesEsfera" />
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete v-model="filtrosLocal.status" label="Situação da solicitação" placeholder="Selecione"
          :options="OPCOES_STATUS" />
      </div>
    </div>
  </div>
  <div class="filtros-acoes">
    <br-button emphasis="secondary" type="button" @click="limparFiltros" aria-label="Limpar filtros">
      Limpar Filtro
    </br-button>
    <br-button :color-mode="$appTheme === 'dark' ? 'dark' : undefined" emphasis="primary" type="button" @click="listar"
      :disabled="carregando" aria-label="Pesquisar solicitações">
      Listar
    </br-button>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch } from 'vue'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { OPCOES_STATUS } from '../constants/opcoesFiltro'
import { useEsferas } from '@/core/composables/useEsferas'
import { useLocalidades } from '@/core/composables/useLocalidades'
import type { FiltrosGerenciarSolicitacao } from '@/services/GerenciarSolicitacaoCadastroService'
import { BrButton } from '@govbr-ds/webcomponents-vue'

defineOptions({ name: 'FiltrosGerenciarSolicitacao' })

const { opcoesUf, opcoesMunicipio, carregarUfs, carregarMunicipios } = useLocalidades()
const { opcoesEsfera, carregarEsferas } = useEsferas()

const props = defineProps<{
  carregando?: boolean
}>()

const emit = defineEmits<{
  (e: 'pesquisar', filtros: FiltrosGerenciarSolicitacao): void
  (e: 'limpar'): void
}>()

const filtrosLocal = reactive<FiltrosGerenciarSolicitacao>({
  cpf: undefined,
  nome: undefined,
  uf: undefined,
  municipio: undefined,
  orgao: undefined,
  esfera: undefined,
  status: undefined,
})

function listar() {
  const f: FiltrosGerenciarSolicitacao = {}
  const cpfDigits = filtrosLocal.cpf?.replace(/\D/g, '')
  if (cpfDigits && cpfDigits.length >= 11) f.cpf = filtrosLocal.cpf
  if (filtrosLocal.nome?.trim()) f.nome = filtrosLocal.nome.trim()
  if (filtrosLocal.uf) f.uf = filtrosLocal.uf
  if (filtrosLocal.municipio?.trim()) f.municipio = filtrosLocal.municipio.trim()
  if (filtrosLocal.orgao?.trim()) f.orgao = filtrosLocal.orgao.trim()
  if (filtrosLocal.esfera) f.esfera = filtrosLocal.esfera
  if (filtrosLocal.status) f.status = filtrosLocal.status
  emit('pesquisar', f)
}

function limparFiltros() {
  filtrosLocal.cpf = undefined
  filtrosLocal.nome = undefined
  filtrosLocal.uf = undefined
  filtrosLocal.municipio = undefined
  filtrosLocal.orgao = undefined
  filtrosLocal.esfera = undefined
  filtrosLocal.status = undefined
  emit('limpar')
}

onMounted(() => {
  carregarUfs()
  carregarEsferas()
})

watch(
  () => filtrosLocal.uf,
  (uf) => {
    filtrosLocal.municipio = undefined
    carregarMunicipios(uf ?? '')
  },
  { immediate: true }
)
</script>

<style scoped>
.filtros-gerenciar {
  margin-bottom: 1.5rem;
}

.filtros-acoes {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

@media (max-width: 575px) {
  .filtros-row {
    grid-template-columns: 1fr;
  }

  .filtros-acoes {
    flex-direction: column;
  }

  .filtros-acoes .br-button {
    width: 100%;
  }
}

@media (min-width: 576px) and (max-width: 991px) {

  .filtros-row:first-child,
  .filtros-row:nth-child(2) {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
