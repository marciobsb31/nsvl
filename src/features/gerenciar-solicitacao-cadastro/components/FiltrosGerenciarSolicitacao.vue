<template>
  <div class="filtros-gerenciar">
    <div class="row filtros">
      <div class="col-12 col-md-6">
        <div class="br-input mb-2">
          <label for="filtro-nome">Nome completo</label>
          <input
            id="filtro-nome"
            type="text"
            placeholder="Informe o nome"
            v-model="filtrosLocal.nome"
          />
        </div>
      </div>
      <div class="col-12 col-md-6">
        <div class="br-input mb-2">
          <label for="filtro-cpf">CPF</label>
          <input
            id="filtro-cpf"
            type="text"
            placeholder="000.000.000-00"
            v-model="filtrosLocal.cpf"
            v-maska="'###.###.###-##'"
          />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div v-if="isUfBloqueada" class="br-input mb-2 filtro-readonly-field">
          <label for="filtro-uf-readonly">Estado (UF)</label>
          <input id="filtro-uf-readonly" type="text" :value="ufBloqueadaLabel" disabled readonly />
        </div>
        <SelectAutocomplete
          v-else
          v-model="filtrosLocal.uf"
          label="Estado (UF)"
          placeholder="Selecione"
          :options="opcoesUfFiltradas"
        />
      </div>
      <div class="col-12 col-md-4">
        <div v-if="isMunicipioBloqueado" class="br-input mb-2 filtro-readonly-field">
          <label for="filtro-municipio-readonly">Município</label>
          <input
            id="filtro-municipio-readonly"
            type="text"
            :value="municipioBloqueadoLabel"
            disabled
            readonly
          />
        </div>
        <SelectAutocomplete
          v-else
          v-model="filtrosLocal.municipio"
          label="Município"
          :placeholder="filtrosLocal.uf ? 'Selecione o município' : 'Selecione primeiro a UF'"
          :options="opcoesMunicipioFiltradas"
          :disabled="!filtrosLocal.uf"
        />
      </div>
      <div class="col-12 col-md-4">
        <div class="br-input mb-2">
          <label for="filtro-orgao">Órgão de atuação</label>
          <input id="filtro-orgao" type="text" placeholder="Órgão" v-model="filtrosLocal.orgao" />
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div v-if="isEsferaBloqueada" class="br-input mb-2 filtro-readonly-field">
          <label for="filtro-esfera-readonly">Esfera de atuação</label>
          <input
            id="filtro-esfera-readonly"
            type="text"
            :value="esferaBloqueadaLabel"
            disabled
            readonly
          />
        </div>
        <SelectAutocomplete
          v-else
          v-model="filtrosLocal.esfera"
          label="Esfera de atuação"
          placeholder="Selecione"
          :options="opcoesEsferaFiltradas"
        />
      </div>
      <div class="col-12 col-md-4">
        <SelectAutocomplete
          v-model="filtrosLocal.status"
          label="Situação da solicitação"
          placeholder="Selecione"
          :options="OPCOES_STATUS"
        />
      </div>
    </div>
  </div>
  <div class="filtros-acoes">
    <br-button
      emphasis="secondary"
      type="button"
      @click="limparFiltros"
      aria-label="Limpar filtros"
    >
      Limpar Filtro
    </br-button>
    <br-button
      :color-mode="$appTheme === 'dark' ? 'dark' : undefined"
      emphasis="primary"
      type="button"
      @click="listar"
      :disabled="carregando"
      aria-label="Pesquisar solicitações"
    >
      Pesquisar
    </br-button>
  </div>
</template>

<script setup lang="ts">
import { reactive, onMounted, watch, computed, ref } from 'vue'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { OPCOES_STATUS } from '../constants/opcoesFiltro'
import { useEsferasStore } from '@/stores/esferasStore'
import { useUfStore } from '@/stores/ufStore'
import { useMunicipioStore } from '@/stores/municipioStore'
import type { FiltrosGerenciarSolicitacao } from '@/services/GerenciarSolicitacaoCadastroService'
import { useAuth } from '@/core/composables/useAuth'

defineOptions({ name: 'FiltrosGerenciarSolicitacao' })

const esferasStore = useEsferasStore()
const opcoesEsfera = computed(() => {
  return esferasStore.esferasOptions
})

const ufStore = useUfStore()
const opcoesUf = computed(() => ufStore.ufsOptions)

const municipioStore = useMunicipioStore()
const opcoesMunicipio = computed(() => municipioStore.municipiosOptions)
const { user, perfilAtivo } = useAuth()
const aplicandoContextoTerritorial = ref(false)

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

function normalizarTexto(valor?: string): string {
  return String(valor ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
}

const esferaUsuarioLogado = computed<'federal' | 'estadual' | 'municipal'>(() => {
  const esferaContexto = normalizarTexto(String(user.value?.contexto?.esfera ?? ''))
  if (esferaContexto === 'federal' || esferaContexto === 'estadual' || esferaContexto === 'municipal') {
    return esferaContexto
  }

  const nomePerfil = normalizarTexto(String(user.value?.contexto?.perfil ?? perfilAtivo.value?.nome ?? ''))
  if (nomePerfil.includes('estadual')) return 'estadual'
  if (nomePerfil.includes('municipal')) return 'municipal'
  return 'federal'
})

const ufSiglaContexto = computed(() => {
  const ufIdContexto = Number(user.value?.contexto?.uf_id ?? 0)
  if (ufIdContexto > 0) {
    const ufDoContexto = ufStore.ufsLista.find((item) => Number(item.id) === ufIdContexto)
    if (ufDoContexto?.sigla) return String(ufDoContexto.sigla).toUpperCase()
  }

  const ufPerfilAtivo = String(perfilAtivo.value?.uf ?? '').trim().toUpperCase()
  return ufPerfilAtivo || undefined
})

const municipioIdContexto = computed(() => {
  const municipioId = user.value?.contexto?.municipio_id
  if (municipioId != null && String(municipioId).trim() !== '') {
    return String(municipioId)
  }

  const municipioPerfil = String(perfilAtivo.value?.municipio ?? '').trim().toLowerCase()
  if (!municipioPerfil) return undefined
  const municipioEncontrado = municipioStore.municipiosLista.find(
    (item) => String(item.nome ?? '').trim().toLowerCase() === municipioPerfil,
  )

  return municipioEncontrado ? String(municipioEncontrado.id) : undefined
})

const isEsferaBloqueada = computed(
  () => esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal',
)
const isUfBloqueada = computed(
  () => esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal',
)
const isMunicipioBloqueado = computed(
  () => esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal',
)

const opcoesEsferaFiltradas = computed(() => {
  if (esferaUsuarioLogado.value === 'estadual') {
    return opcoesEsfera.value.filter((o) => normalizarTexto(String(o.label)) === 'estadual')
  }
  if (esferaUsuarioLogado.value === 'municipal') {
    return opcoesEsfera.value.filter((o) => normalizarTexto(String(o.label)) === 'municipal')
  }
  return opcoesEsfera.value
})

const opcoesUfFiltradas = computed(() => {
  if (isUfBloqueada.value && ufSiglaContexto.value) {
    return opcoesUf.value.filter((o) => String(o.value).toUpperCase() === ufSiglaContexto.value)
  }
  return opcoesUf.value
})

const opcoesMunicipioFiltradas = computed(() => {
  if (isMunicipioBloqueado.value && municipioIdContexto.value) {
    return opcoesMunicipio.value.filter((o) => String(o.value) === municipioIdContexto.value)
  }
  return opcoesMunicipio.value
})

const esferaBloqueadaLabel = computed(() => {
  const esfera = String(filtrosLocal.esfera ?? esferaUsuarioLogado.value ?? '').trim().toLowerCase()
  if (esfera === 'federal') return 'Federal'
  if (esfera === 'estadual') return 'Estadual'
  if (esfera === 'municipal') return 'Municipal'
  return '—'
})

const ufBloqueadaLabel = computed(() => {
  const uf = String(filtrosLocal.uf ?? ufSiglaContexto.value ?? '').trim().toUpperCase()
  if (!uf) return '—'

  const ufEncontrada = ufStore.ufsLista.find(
    (item) => String(item.sigla ?? '').trim().toUpperCase() === uf,
  )

  if (ufEncontrada?.nome) {
    return `${ufEncontrada.nome} - ${uf}`
  }

  return uf
})

const municipioBloqueadoLabel = computed(() => {
  const municipioSelecionado = String(filtrosLocal.municipio ?? municipioIdContexto.value ?? '').trim()
  if (!municipioSelecionado) return '—'

  const opcao = opcoesMunicipio.value.find((o) => String(o.value) === municipioSelecionado)
  if (opcao) return String(opcao.label)

  const municipioLista = municipioStore.municipiosLista.find(
    (item) => String(item.id) === municipioSelecionado,
  )
  if (municipioLista?.nome) return String(municipioLista.nome)

  return String(perfilAtivo.value?.municipio ?? '—')
})

async function aplicarContextoTerritorialNosFiltros() {
  if (esferaUsuarioLogado.value === 'federal') return

  aplicandoContextoTerritorial.value = true

  filtrosLocal.esfera = esferaUsuarioLogado.value

  if (ufSiglaContexto.value) {
    filtrosLocal.uf = ufSiglaContexto.value
    await municipioStore.carregarMunicipios(ufSiglaContexto.value)
  }

  if (municipioIdContexto.value) {
    filtrosLocal.municipio = municipioIdContexto.value
  }

  aplicandoContextoTerritorial.value = false
}

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
  filtrosLocal.orgao = undefined
  filtrosLocal.status = undefined
  if (isUfBloqueada.value) {
    filtrosLocal.uf = ufSiglaContexto.value
  } else {
    filtrosLocal.uf = undefined
  }
  if (isMunicipioBloqueado.value) {
    filtrosLocal.municipio = municipioIdContexto.value
  } else {
    filtrosLocal.municipio = undefined
  }
  if (isEsferaBloqueada.value) {
    filtrosLocal.esfera = esferaUsuarioLogado.value
  } else {
    filtrosLocal.esfera = undefined
  }
  emit('limpar')
}

onMounted(async () => {
  await ufStore.carregarUfs()
  await esferasStore.carregarEsferas()
  await aplicarContextoTerritorialNosFiltros()
})

watch(
  () => filtrosLocal.uf,
  async (uf) => {
    const ufSelecionada = uf ?? ''

    if (!aplicandoContextoTerritorial.value) {
      filtrosLocal.municipio = undefined
    }

    await municipioStore.carregarMunicipios(ufSelecionada)

    if (aplicandoContextoTerritorial.value && municipioIdContexto.value) {
      filtrosLocal.municipio = municipioIdContexto.value
    }
  },
  { immediate: true },
)

watch(
  () => [
    user.value?.contexto?.esfera,
    user.value?.contexto?.perfil,
    user.value?.contexto?.uf_id,
    user.value?.contexto?.municipio_id,
    perfilAtivo.value?.nome,
    perfilAtivo.value?.uf,
    perfilAtivo.value?.municipio,
  ],
  () => {
    aplicarContextoTerritorialNosFiltros()
  },
  { deep: true },
)
</script>

<style scoped>
.filtros-gerenciar {
  margin-bottom: 1.5rem;
}

.filtro-readonly-field :deep(input[readonly]),
.filtro-readonly-field :deep(input[disabled]) {
  background-color: var(--gray-5, #f0f0f0) !important;
  color: var(--secondary-text-color, #555) !important;
  border-color: var(--gray-30, #d9d9d9) !important;
  cursor: not-allowed;
  opacity: 1;
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
