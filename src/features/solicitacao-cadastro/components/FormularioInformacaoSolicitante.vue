<template>
    <section class="row">
        <div class="col-md-6 col-sm-12">
            <Select
                label="Esfera de atuação"
                placeholder="Esfera de atuação"
                :options="esfera"
                required
                v-model="esferaAtuacao"
            />
            <Feedback v-if="errorsEsfera" :message="errorsEsfera" type="danger" />
        </div>
        <div class="col-md-6 col-sm-12">
            <Select
                label="UF"
                placeholder="UF"
                :options="estados"
                required
                v-model="uf"
            />
            <Feedback v-if="errorsUf" :message="errorsUf" type="danger" />
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-municipio">Município<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-municipio" type="text" placeholder="Município" v-model="municipio" />
                <Feedback v-if="errorsMunicipio" :message="errorsMunicipio" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-orgao">Órgão de atuação<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-orgao" type="text" placeholder="Órgão/secretaria responsável pela atuação no NVSL." v-model="orgao" />
                <Feedback v-if="errorsOrgao" :message="errorsOrgao" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-cargo">Cargo / Função<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-cargo" type="text" placeholder="Cargo ou função." v-model="cargo" />
                <Feedback v-if="errorsCargo" :message="errorsCargo" type="danger" />
            </div>
        </div>
    </section>
</template>
<script setup lang="ts">
import Select from '@/core/components/Select/Select.vue';
import { ref } from 'vue';
import { useField } from 'vee-validate';
import Feedback from '@/core/components/Feedback/Feedback.vue';

defineOptions({
  name: 'FormularioInformacaoSolicitante'
})

const esfera = ref([
  { value: 'federal', label: 'Federal' },
  { value: 'estadual', label: 'Estadual' },
  { value: 'municipal', label: 'Municipal' },
])

const estados = ref([
  { value: 'AC', label: 'AC' },
  { value: 'AL', label: 'AL' },
  { value: 'AP', label: 'AP' },
  { value: 'AM', label: 'AM' },
  { value: 'BA', label: 'BA' },
  { value: 'CE', label: 'CE' },
  { value: 'DF', label: 'DF' },
  { value: 'ES', label: 'ES' },
  { value: 'GO', label: 'GO' },
  { value: 'MA', label: 'MA' },
  { value: 'MT', label: 'MT' },
  { value: 'MS', label: 'MS' },
  { value: 'MG', label: 'MG' },
  { value: 'PA', label: 'PA' },
  { value: 'PB', label: 'PB' },
  { value: 'PR', label: 'PR' },
  { value: 'PE', label: 'PE' },
  { value: 'PI', label: 'PI' },
  { value: 'RJ', label: 'RJ' },
  { value: 'RN', label: 'RN' },
  { value: 'RS', label: 'RS' },
  { value: 'RO', label: 'RO' },
  { value: 'RR', label: 'RR' },
  { value: 'SC', label: 'SC' },
  { value: 'SP', label: 'SP' },
  { value: 'SE', label: 'SE' },
  { value: 'TO', label: 'TO' },
])



const { value: esferaAtuacao, errorMessage: errorsEsfera } = useField<string>('esferaAtuacao')
const { value: uf, errorMessage: errorsUf } = useField<string>('uf')
const { value: municipio, errorMessage: errorsMunicipio } = useField<string>('municipio')
const { value: orgao, errorMessage: errorsOrgao } = useField<string>('orgao')
const { value: cargo, errorMessage: errorsCargo } = useField<string>('cargo')



</script>

<style scoped>

</style>