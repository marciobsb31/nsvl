<template>
  <section class="row g-3 solicitacao-form-grid">
    <div class="col-12 col-md-6">
      <div class="br-input">
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
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-cpf">
          CPF
          <span v-if="!modoEdicao && !modoGovBr" class="text-red-50 text-up-01">*</span>
          <template v-else-if="modoGovBr"> (GOV.BR)</template>
          <template v-else> (opcional)</template>
        </label>
        <input
          id="input-cpf"
          type="text"
          :placeholder="
            modoGovBr
              ? 'Preenchido automaticamente pelo GOV.BR'
              : modoEdicao
                ? 'Informe apenas se desejar alterar'
                : '000.000.000-00'
          "
          v-model="CPF"
          v-maska="modoGovBr ? undefined : '###.###.###-##'"
          :readonly="modoGovBr"
          :disabled="verificandoCpf"
          @blur="onCpfBlur"
        />
        <span v-if="verificandoCpf" class="input-hint input-hint--loading">
          <i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Verificando CPF...
        </span>
        <span v-else-if="cpfDisponivel && cpfPreenchido" class="input-hint input-hint--success">
          <i class="fas fa-check-circle" aria-hidden="true"></i> CPF disponível
        </span>
        <Feedback v-if="errorsCPF" :message="errorsCPF" type="danger" />
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-email">E-mail institucional<span class="text-red-50 text-up-01"> *</span></label>
        <input id="input-email" type="email" placeholder="seu.nome@email.com" v-model="emailInstitucional" maxlength="60"/>
        <Feedback v-if="errorsEmail" :message="errorsEmail" type="danger" />
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-tel-inst">Telefone institucional<span class="text-red-50 text-up-01"> *</span></label>
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
    <div class="col-12 col-md-6">
      <div class="br-input">
        <label for="input-tel-pessoal">Telefone pessoal</label>
        <input
          id="input-tel-pessoal"
          type="tel"
          placeholder="(00) 00000-0000"
          v-model="telefonePessoal"
          v-maska="telefoneMask"
        />
        <Feedback v-if="errorsTelPessoal" :message="errorsTelPessoal" type="danger" />
        <span class="solicitacao-field-hint">Opcional — para contato alternativo.</span>
      </div>
    </div>
  </section>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useField, useForm } from 'vee-validate'
import Feedback from '@/core/components/Feedback/Feedback.vue'
import { verificarCpfDisponivel } from '@/services/SolicitacaoCadastroService'
import { validarCpf } from '@/core/utils/validarCpf'

defineOptions({
  name: 'FormularioDadosSolicitante'
})

const props = withDefaults(defineProps<{
  modoEdicao?: boolean
  modoGovBr?: boolean
  verificarCpfEmUso?: boolean
}>(), { modoEdicao: false, modoGovBr: false, verificarCpfEmUso: true })

const { value: nome, errorMessage: errorsNome } = useField<string>('nome')
const { value: CPF, errorMessage: errorsCPF } = useField<string>('CPF')
const { value: emailInstitucional, errorMessage: errorsEmail } = useField<string>('emailInstitucional')
const { value: telefoneInstitucional, errorMessage: errorsTelInst } = useField<string>('telefoneInstitucional')
const { value: telefonePessoal, errorMessage: errorsTelPessoal } = useField<string>('telefonePessoal')

const { setFieldError } = useForm()
const verificandoCpf = ref(false)
const cpfDisponivel = ref<boolean | null>(null)

const cpfPreenchido = computed(() => {
  const d = String(CPF.value ?? '').replace(/\D/g, '')
  return d.length === 11
})

// Máscara dinâmica: fixo (##) ####-#### ou celular (##) #####-####
const telefoneMask = { mask: ['(##) ####-####', '(##) #####-####'] }

const MENSAGENS_CPF_EM_USO: Record<string, string> = {
  'Este CPF já possui cadastro ativo no sistema.': 'Este CPF já está em uso. Faça login ou solicite recuperação de acesso.',
  'Já existe uma solicitação em análise para este CPF.': 'Este CPF já possui uma solicitação em análise. Aguarde o retorno.',
  'CPF inválido. Verifique os dígitos informados.': 'CPF inválido. Confira os números digitados.',
  'Informe um CPF com 11 dígitos.': 'Informe os 11 dígitos do CPF.',
}

function mensagemCriativa(original: string): string {
  return MENSAGENS_CPF_EM_USO[original] ?? original
}

async function onCpfBlur() {
  if (props.modoGovBr || !props.verificarCpfEmUso) return

  const digitos = String(CPF.value ?? '').replace(/\D/g, '')
  if (digitos.length !== 11) {
    cpfDisponivel.value = null
    return
  }

  if (!validarCpf(digitos)) {
    setFieldError('CPF', 'CPF inválido. Confira os números digitados.')
    cpfDisponivel.value = false
    return
  }

  verificandoCpf.value = true
  cpfDisponivel.value = null
  setFieldError('CPF', undefined)

  try {
    const res = await verificarCpfDisponivel(digitos)
    if (res.disponivel) {
      cpfDisponivel.value = true
      setFieldError('CPF', undefined)
    } else {
      cpfDisponivel.value = false
      setFieldError('CPF', mensagemCriativa(res.mensagem))
    }
  } catch {
    cpfDisponivel.value = null
    setFieldError('CPF', 'Não foi possível verificar o CPF. Tente novamente.')
  } finally {
    verificandoCpf.value = false
  }
}

function filtrarSomenteLetras(event: Event) {
  const input = event.target as HTMLInputElement
  const valor = input.value.replace(/[^a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]/g, '')
  nome.value = valor
}
</script>

<style scoped>
.solicitacao-form-grid :deep(.br-input input:not([readonly])) {
  min-height: 2.5rem;
  border-radius: 6px;
}

.solicitacao-form-grid :deep(.br-input label) {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--dark-text-color);
  margin-bottom: 0.25rem;
}

.solicitacao-field-hint {
  display: block;
  font-size: 0.75rem;
  margin-top: 0.35rem;
  color: var(--secondary-text-color-02);
}

.input-hint {
  display: block;
  font-size: 0.75rem;
  margin-top: 0.25rem;
}
.input-hint--loading {
  color: var(--secondary-text-color);
}
.input-hint--success {
  color: var(--color-success, #168821);
}
</style>