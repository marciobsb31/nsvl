<template>
    <section class="row">
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-nome">Nome<span class="text-red-50 text-up-01"> *</span></label>
                <input
                    id="input-nome"
                    type="text"
                    placeholder="Nome completo (somente letras)"
                    v-model="nome"
                    :readonly="modoGovBr"
                    @input="filtrarSomenteLetras"
                />
                <Feedback v-if="errorsNome" :message="errorsNome" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-cpf">CPF<span v-if="!modoEdicao && !modoGovBr" class="text-red-50 text-up-01">*</span><template v-else-if="modoGovBr"> (GOV.BR)</template><template v-else> (opcional)</template></label>
                <input
                    id="input-cpf"
                    type="text"
                    :placeholder="modoGovBr ? 'Preenchido automaticamente pelo GOV.BR' : (modoEdicao ? 'Informe apenas se desejar alterar' : '000.000.000-00')"
                    v-model="CPF"
                    v-maska="modoGovBr ? undefined : '###.###.###-##'"
                    :readonly="modoGovBr"
                />
                <Feedback v-if="errorsCPF" :message="errorsCPF" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-email">E-mail Institucional<span class="text-red-50 text-up-01">*</span></label>
                <input
                    id="input-email"
                    type="email"
                    placeholder="seu.nome@orgao.gov.br"
                    v-model="emailInstitucional"
                />
                <Feedback v-if="errorsEmail" :message="errorsEmail" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-tel-inst">Telefone institucional<span class="text-red-50 text-up-01">*</span></label>
                <input
                    id="input-tel-inst"
                    type="tel"
                    placeholder="(00) 00000-0000"
                    v-model="telefoneInstitucional"
                    v-maska="telefoneMask"
                />
                <Feedback v-if="errorsTelInst" :message="errorsTelInst" type="danger" />
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="br-input mb-2">
                <label for="input-tel-pessoal">Telefone pessoal</label>
                <input
                    id="input-tel-pessoal"
                    type="tel"
                    placeholder="(00) 00000-0000"
                    v-model="telefonePessoal"
                    v-maska="telefoneMask"
                />
                <Feedback v-if="errorsTelPessoal" :message="errorsTelPessoal" type="danger" />
            </div>
        </div>
    </section>
</template>
<script setup lang="ts">
import { useField } from 'vee-validate';
import Feedback from '@/core/components/Feedback/Feedback.vue';

defineOptions({
  name: 'FormularioDadosSolicitante'
})

const props = withDefaults(defineProps<{ modoEdicao?: boolean; modoGovBr?: boolean }>(), { modoEdicao: false, modoGovBr: false })

const { value: nome, errorMessage: errorsNome } = useField<string>('nome')
const { value: CPF, errorMessage: errorsCPF } = useField<string>('CPF')
const { value: emailInstitucional, errorMessage: errorsEmail } = useField<string>('emailInstitucional')
const { value: telefoneInstitucional, errorMessage: errorsTelInst } = useField<string>('telefoneInstitucional')
const { value: telefonePessoal, errorMessage: errorsTelPessoal } = useField<string>('telefonePessoal')

// Máscara dinâmica: fixo (##) ####-#### ou celular (##) #####-####
const telefoneMask = { mask: ['(##) ####-####', '(##) #####-####'] }

function filtrarSomenteLetras(event: Event) {
  const input = event.target as HTMLInputElement
  const valor = input.value.replace(/[^a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]/g, '')
  nome.value = valor
}
</script>

<style scoped>

</style>