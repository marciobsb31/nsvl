<template>
      <section class="row">
        <div class="col-md-6 col-sm-12">
        <Select 
          label="Esfera de atuação"
          placeholder="Esfera de atuação"
          :options="esfera"
          required
          v-model="esferaAtuacao" />
          <Feedback v-if="errors.esferaAtuacao" :message="errors.esferaAtuacao" type="danger" />
        </div>
        <div class="col-md-6 col-sm-12">
        <Select
          label="UF"
          placeholder="UF"
          :options="estados"
          required
          v-model="uf" />
          <Feedback v-if="errors.uf" :message="errors.uf" type="danger" />
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="br-input mb-2">
            <label for="input-default">Município<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Município" v-model="municipio" />
            <Feedback v-if="errors.municipio" :message="errors.municipio" type="danger" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="br-input mb-2">
            <label for="input-default">Orgão de atuação<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Órgão/secretaria responsável pela atuação no NVSL." v-model="orgao" />
            <Feedback v-if="errors.orgao" :message="errors.orgao" type="danger" />
          </div>
        </div>
        <div class="col-md-6 col-sm-12">
          <div class="br-input mb-2">
            <label for="input-default">Cargo /Função<span class="text-red-50 text-up-01">*</span></label>
            <input id="input-default" type="text" placeholder="Cargo ou função." v-model="cargo" />
            <Feedback v-if="errors.cargo" :message="errors.cargo" type="danger" />
          </div>
        </div>
      </section>

</template>
<script setup lang="ts">
import Select from '@/core/components/Select/Select.vue';
import { ref } from 'vue';
import { InformacaoSolicitanteSchema } from '../validators/solicitacaoCadastro.schema';
import Feedback from '@/core/components/Feedback/Feedback.vue';
import { watch } from 'vue';
import { useForm, useField } from 'vee-validate';

defineOptions({
  name: 'FormularioInformacaoSolicitante'
})

const props = defineProps<{
  submitForm: boolean;
}>();

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



const { handleSubmit, errors, isSubmitting } = useForm<any>({
    validationSchema: InformacaoSolicitanteSchema,
});

const { value: esferaAtuacao } = useField<string>('esferaAtuacao')
const { value: uf } = useField<string>('uf')
const { value: municipio } = useField<string>('municipio')
const { value: orgao } = useField<string>('orgao')
const { value: cargo } = useField<string>('cargo')

const onSubmit = handleSubmit(values => {
   console.log(values);
});

watch(() => props.submitForm, (newValue) => {
  if (newValue) {
    console.log('Submitting form');
    onSubmit();
  }
});



</script>

<style scoped>

</style>