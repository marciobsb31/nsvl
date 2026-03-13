<template>
    <section class="row">
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-default">Nome<span class="text-red-50 text-up-01"> *</span></label>
                <input id="input-default" type="text" placeholder="Nome" disabled/>
                <Feedback v-if="errors.nome" :message="errors.nome" type="danger" />
            </div>            
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-default">CPF<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-default" type="text" placeholder="CPF" disabled  v-maska="'###.###.###-##'"/>
                <Feedback v-if="errors.CPF" :message="errors.CPF" type="danger" />
            </div>            
        </div>
         <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-default">E-mail Institucional<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-default" type="email" placeholder="E-mail comporativo" v-model="emailInstitucional" />
                <Feedback v-if="errors.emailInstitucional" :message="errors.emailInstitucional" type="danger" />
            </div>            
        </div>
         <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-default">Telefone institucional<span class="text-red-50 text-up-01">*</span></label>
                <input id="input-default" type="text" placeholder="Telefone corporativo de contato do solicitante." v-model="telefoneInstitucional" v-maska="'(##) #####-####'"/>
                <Feedback v-if="errors.telefoneInstitucional" :message="errors.telefoneInstitucional" type="danger" />
            </div>            
        </div>
          <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-default">Telefone pessoal</label>
                <input id="input-default" type="text" placeholder="Telefone pessoal do solicitante." v-model="telefonePessoal" v-maska="'(##) #####-####'"/>
                <Feedback v-if="errors.telefonePessoal" :message="errors.telefonePessoal" type="danger" />
            </div>            
        </div>
    </section>

</template>
<script setup lang="ts">
import { useForm, useField } from 'vee-validate';
import { DadosSolicitanteSchema } from '../validators/solicitacaoCadastro.schema';
import Feedback from '@/core/components/Feedback/Feedback.vue';
import { watch } from 'vue';

defineOptions({
  name: 'FormularioDadosSolicitante'
})
const props = defineProps<{
  submitForm: boolean;
}>();

const { handleSubmit, errors, isSubmitting } = useForm<any>({
    validationSchema: DadosSolicitanteSchema,
});

const { value: emailInstitucional } = useField<string>('emailInstitucional')
const { value: telefoneInstitucional } = useField<string>('telefoneInstitucional')
const { value: telefonePessoal } = useField<string>('telefonePessoal')
const { value: nome } = useField<string>('nome')
const { value: CPF } = useField<string>('CPF')

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