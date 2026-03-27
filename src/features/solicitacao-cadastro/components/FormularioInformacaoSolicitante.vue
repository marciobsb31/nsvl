<template>
  <section class="row g-3 solicitacao-form-grid">
    <div class="col-12 col-md-6">
      <SelectAutocomplete
        v-model="esferaAtuacao"
        label="Esfera de atuação"
        placeholder="Esfera de atuação"
        :options="opcoesEsferaFiltradas"
        :disabled="esferaBloqueada"
        required
      />
      <Feedback v-if="errorsEsfera" :message="errorsEsfera" type="danger" />
    </div>
    <div class="col-12 col-md-6">
      <SelectAutocomplete
        v-model="uf"
        label="Estado (UF)"
        placeholder="Selecione"
        :options="opcoesUfFiltradas"
        :disabled="ufBloqueada"
        required
      />
      <Feedback v-if="errorsUf" :message="errorsUf" type="danger" />
    </div>
    <div class="col-12 col-md-6">
      <SelectAutocomplete
        v-model="municipio"
        label="Município"
        placeholder="Município"
        :options="opcoesMunicipioFiltradas"
        :disabled="!uf || municipioBloqueado"
        required
      />
      <Feedback v-if="errorsMunicipio" :message="errorsMunicipio" type="danger" />
    </div>
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-orgao">Órgão de atuação<span class="text-red-50 text-up-01"> *</span></label>
        <input
          id="input-orgao"
          type="text"
          placeholder="Órgão ou secretaria responsável pela atuação no NVSL"
          v-model="orgao"
        />
        <Feedback v-if="errorsOrgao" :message="errorsOrgao" type="danger" />
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-cargo">Cargo / função<span class="text-red-50 text-up-01"> *</span></label>
        <input id="input-cargo" type="text" placeholder="Cargo ou função exercida" v-model="cargo" />
        <Feedback v-if="errorsCargo" :message="errorsCargo" type="danger" />
      </div>
    </div>
  </section>
</template>
<script setup lang="ts">
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { onMounted, watch, computed } from 'vue'
import { useField } from 'vee-validate'
import Feedback from '@/core/components/Feedback/Feedback.vue'
import { useEsferas } from '@/core/composables/useEsferas'
import { useLocalidades } from '@/core/composables/useLocalidades'

defineOptions({
  name: 'FormularioInformacaoSolicitante'
})

const props = withDefaults(
  defineProps<{
    aplicarRegrasHierarquia?: boolean
    usuarioLogado?: {
      esfera_atuacao?: string
      uf_lotacao?: string
      municipio_lotacao?: string
    } | null
  }>(),
  { aplicarRegrasHierarquia: false, usuarioLogado: null }
)

const { opcoesEsfera, carregarEsferas } = useEsferas()

const { value: esferaAtuacao, errorMessage: errorsEsfera } = useField<string>('esferaAtuacao')
const { value: uf, errorMessage: errorsUf } = useField<string>('uf')
const { value: municipio, errorMessage: errorsMunicipio } = useField<string>('municipio')

const { opcoesUf, opcoesMunicipio, carregarUfs } = useLocalidades(uf)

const esferaUsuarioLogado = computed(() =>
  String(props.usuarioLogado?.esfera_atuacao ?? '').toLowerCase()
)

const aplicarHierarquia = computed(() => !!props.aplicarRegrasHierarquia)

const esferaBloqueada = computed(
  () => aplicarHierarquia.value && (esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal')
)
const ufBloqueada = computed(
  () => aplicarHierarquia.value && (esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal')
)
const municipioBloqueado = computed(
  () => aplicarHierarquia.value && esferaUsuarioLogado.value === 'municipal'
)

const opcoesEsferaFiltradas = computed(() => {
  if (!aplicarHierarquia.value) return opcoesEsfera.value
  if (esferaUsuarioLogado.value === 'estadual') {
    return opcoesEsfera.value.filter((o) => String(o.value).toLowerCase() === 'estadual')
  }
  if (esferaUsuarioLogado.value === 'municipal') {
    return opcoesEsfera.value.filter((o) => String(o.value).toLowerCase() === 'municipal')
  }
  return opcoesEsfera.value
})

const opcoesUfFiltradas = computed(() => {
  if (!ufBloqueada.value || !props.usuarioLogado?.uf_lotacao) return opcoesUf.value
  const ufLotacao = String(props.usuarioLogado.uf_lotacao).toUpperCase()
  return opcoesUf.value.filter((o) => String(o.value).toUpperCase() === ufLotacao)
})

const opcoesMunicipioFiltradas = computed(() => {
  if (!municipioBloqueado.value || !props.usuarioLogado?.municipio_lotacao) return opcoesMunicipio.value
  const municipioLotacao = String(props.usuarioLogado.municipio_lotacao).toLowerCase()
  return opcoesMunicipio.value.filter((o) => String(o.label).toLowerCase() === municipioLotacao)
})

onMounted(() => {
  carregarUfs()
  carregarEsferas()
})

watch(uf, () => {
  if (municipioBloqueado.value && props.usuarioLogado?.municipio_lotacao) {
    municipio.value = String(props.usuarioLogado.municipio_lotacao)
    return
  }
  municipio.value = ''
})

watch(
  () => props.usuarioLogado,
  (usuario) => {
    if (!aplicarHierarquia.value || !usuario) return

    const esfera = String(usuario.esfera_atuacao ?? '').toLowerCase()
    if (esfera === 'estadual') {
      esferaAtuacao.value = 'estadual'
      if (usuario.uf_lotacao) uf.value = String(usuario.uf_lotacao).toUpperCase()
      return
    }
    if (esfera === 'municipal') {
      esferaAtuacao.value = 'municipal'
      if (usuario.uf_lotacao) uf.value = String(usuario.uf_lotacao).toUpperCase()
      if (usuario.municipio_lotacao) municipio.value = String(usuario.municipio_lotacao)
    }
  },
  { immediate: true, deep: true }
)

watch(opcoesMunicipioFiltradas, (opcoes) => {
  if (!municipioBloqueado.value || !props.usuarioLogado?.municipio_lotacao) return
  const municipioLotacao = String(props.usuarioLogado.municipio_lotacao).toLowerCase()
  const opcao = opcoes.find((o) => String(o.label).toLowerCase() === municipioLotacao)
  if (opcao) {
    municipio.value = String(opcao.value ?? opcao.label)
  } else {
    municipio.value = String(props.usuarioLogado.municipio_lotacao)
  }
})
const { value: orgao, errorMessage: errorsOrgao } = useField<string>('orgao')
const { value: cargo, errorMessage: errorsCargo } = useField<string>('cargo')



</script>

<style scoped>
.solicitacao-form-grid :deep(.br-input input:not([readonly])) {
  min-height: 2.5rem;
  border-radius: 6px;
}

.solicitacao-form-grid :deep(.br-input label) {
  font-weight: 600;
  font-size: 0.875rem;
  color: var( --dark-text-color);
  margin-bottom: 0.25rem;
}

/* SelectAutocomplete costuma renderizar label dentro do componente */
.solicitacao-form-grid :deep(label) {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--dark-text-color);
}
</style>