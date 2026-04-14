<template>
  <div class="formulario-cadastrar-usuario">
    <div class="formulario-header">
      <h2 class="formulario-titulo">Cadastrar usuário</h2>
      <button
        class="br-button secondary small"
        type="button"
        @click="$emit('voltar')"
        aria-label="Voltar"
      >
        Voltar
      </button>
    </div>

    <form novalidate @submit.prevent="onConfirmar">
      <div class="formulario-secao">
        <h3 class="secao-titulo">Dados do(a) solicitante</h3>
        <section class="row g-3 cadastro-user-form-grid">
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-nome">Nome<span class="text-red-50 text-up-01"> *</span></label>
              <input
                id="cad-nome"
                type="text"
                placeholder="Nome completo (somente letras)"
                v-model="nome"
                required
                autocomplete="name"
                :aria-invalid="!!errorsNome"
                aria-describedby="cad-nome-err"
                @blur="() => validateField('nome')"
              />
              <Feedback v-if="errorsNome" id="cad-nome-err" :message="errorsNome" type="danger" />
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-cpf">CPF<span class="text-red-50 text-up-01"> *</span></label>
              <input
                id="cad-cpf"
                type="text"
                inputmode="numeric"
                placeholder="000.000.000-00"
                v-model="cpf"
                v-maska="'###.###.###-##'"
                required
                :aria-invalid="!!errorsCpf"
                aria-describedby="cad-cpf-err cad-cpf-hint"
                @blur="onCpfBlur"
              />
              <span v-if="verificandoCpf" class="cadastro-field-hint cadastro-field-hint--loading"
                >Verificando CPF...</span
              >
              <Feedback v-if="errorsCpf" id="cad-cpf-err" :message="errorsCpf" type="danger" />
              <span
                v-if="!errorsCpf && !verificandoCpf"
                id="cad-cpf-hint"
                class="cadastro-field-hint"
                >Use um CPF ainda não cadastrado no sistema.</span
              >
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-tel-inst">Telefone pessoal</label>
              <input
                id="cad-tel-inst"
                type="tel"
                inputmode="tel"
                placeholder="(00) 00000-0000"
                v-model="telefonePessoal"
                v-maska="telefoneMask"
                required
                :aria-invalid="!!errorsTelPessoal"
                aria-describedby="cad-tel-inst-err cad-tel-inst-hint"
                @blur="() => validateField('telefonePessoal')"
              />
              <Feedback
                v-if="errorsTelPessoal"
                id="cad-tel-inst-err"
                :message="errorsTelPessoal"
                type="danger"
              />
              <span id="cad-tel-inst-hint" class="cadastro-field-hint"
                >Fixo ou celular (10 ou 11 dígitos).</span
              >
            </div>
          </div>
        </section>
      </div>

      <div class="formulario-secao">
        <h3 class="secao-titulo">Informação do(a) solicitante</h3>
        <p class="secao-subtitulo">Informações de atuação institucional do solicitante.</p>
        <section class="row g-3 cadastro-user-form-grid">
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-email"
                >E-mail institucional<span class="text-red-50 text-up-01"> *</span></label
              >
              <input
                id="cad-email"
                type="email"
                autocomplete="email"
                placeholder="seu.nome@email.com"
                v-model="emailInstitucional"
                required
                :aria-invalid="!!errorsEmail"
                aria-describedby="cad-email-err"
                @blur="() => validateField('emailInstitucional')"
              />
              <Feedback
                v-if="errorsEmail"
                id="cad-email-err"
                :message="errorsEmail"
                type="danger"
              />
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-tel-inst"
                >Telefone institucional<span class="text-red-50 text-up-01"> *</span></label
              >
              <input
                id="cad-tel-inst"
                type="tel"
                inputmode="tel"
                placeholder="(00) 00000-0000"
                v-model="telefoneInstitucional"
                v-maska="telefoneMask"
                required
                :aria-invalid="!!errorsTelInst"
                aria-describedby="cad-tel-inst-err cad-tel-inst-hint"
                @blur="() => validateField('telefoneInstitucional')"
              />
              <Feedback
                v-if="errorsTelInst"
                id="cad-tel-inst-err"
                :message="errorsTelInst"
                type="danger"
              />
              <span id="cad-tel-inst-hint" class="cadastro-field-hint"
                >Fixo ou celular (10 ou 11 dígitos).</span
              >
            </div>
          </div>
          <div class="col-12 col-md-4">
            <SelectAutocomplete
              v-model="esferaAtuacao"
              label="Esfera de atuação"
              placeholder="Esfera de atuação"
              :options="opcoesEsferaFiltradas"
              :disabled="isEsferaBloqueada"
              required
            />
            <Feedback v-if="errorsEsfera" :message="errorsEsfera" type="danger" />
          </div>
          <div class="col-12 col-md-3">
            <SelectAutocomplete
              ref="ufRef"
              :model-value="uf"
              @update:model-value="onUfChange"
              label="Estado (UF)"
              placeholder="Selecione"
              :options="opcoesUfFiltradas"
              :disabled="isUfBloqueada"
              input-id="cad-uf"
              required
            />
            <Feedback v-if="errorsUf" :message="errorsUf" type="danger" />
          </div>
          <div class="col-12 col-md-5">
            <SelectAutocomplete
              ref="municipioRef"
              :model-value="municipio"
              @update:model-value="onMunicipioChange"
              label="Município"
              placeholder="Selecione o município."
              :options="opcoesMunicipioFiltradas"
              :disabled="!uf || isMunicipioBloqueado"
              input-id="cad-municipio"
              required
            />
            <Feedback v-if="uf && errorsMunicipio" :message="errorsMunicipio" type="danger" />
          </div>
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-orgao"
                >Órgão de atuação<span class="text-red-50 text-up-01"> *</span></label
              >
              <input
                id="cad-orgao"
                type="text"
                placeholder="Órgão ou secretaria"
                v-model="orgao"
                required
                :aria-invalid="!!errorsOrgao"
                @blur="() => validateField('orgao')"
              />
              <Feedback v-if="errorsOrgao" :message="errorsOrgao" type="danger" />
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="cad-cargo"
                >Cargo / função<span class="text-red-50 text-up-01"> *</span></label
              >
              <input
                id="cad-cargo"
                type="text"
                placeholder="Cargo ou função exercida"
                v-model="cargo"
                required
                :aria-invalid="!!errorsCargo"
                @blur="() => validateField('cargo')"
              />
              <Feedback v-if="errorsCargo" :message="errorsCargo" type="danger" />
            </div>
          </div>
        </section>
      </div>

      <div class="formulario-secao">
        <h3 class="secao-titulo">Dados de Perfil</h3>
        <div class="secao-perfil-linha">
          <div class="field-with-feedback">
            <SelectAutocomplete
              ref="perfilRef"
              :model-value="perfil"
              @update:model-value="onPerfilChange"
              label="Perfil"
              placeholder="Selecione o perfil"
              :options="opcoesPerfilFiltradas"
              input-id="cad-perfil"
              required
            />
            <Feedback v-if="errorsPerfil" :message="errorsPerfil" type="danger" />
          </div>
          <div class="br-input mb-2">
            <label for="cad-vigencia-inicio"
              >Vigência (início)<span class="text-red-50 text-up-01"> *</span></label
            >
            <div class="input-date-wrapper">
              <input
                id="cad-vigencia-inicio"
                type="date"
                v-model="vigenciaInicio"
                :max="vigenciaFim || undefined"
                aria-label="Data de início da vigência"
                :aria-invalid="!!errorsVigenciaInicio"
                @blur="() => validateField('vigenciaInicio')"
              />
              <i class="fas fa-calendar-alt input-date-icon" aria-hidden="true"></i>
            </div>
            <Feedback v-if="errorsVigenciaInicio" :message="errorsVigenciaInicio" type="danger" />
          </div>
          <div class="br-input mb-2">
            <label for="cad-vigencia-fim">Vigência (fim)</label>
            <div class="input-date-wrapper">
              <input
                id="cad-vigencia-fim"
                type="date"
                v-model="vigenciaFim"
                :min="vigenciaInicio || undefined"
                aria-label="Data de fim da vigência"
                :aria-invalid="!!errorsVigenciaFim"
                @blur="() => validateField('vigenciaFim')"
              />
              <i class="fas fa-calendar-alt input-date-icon" aria-hidden="true"></i>
            </div>
            <Feedback v-if="errorsVigenciaFim" :message="errorsVigenciaFim" type="danger" />
          </div>
        </div>
      </div>
      <div class="formulario-acoes">
        <button class="br-button secondary" type="button" @click="$emit('voltar')">Cancelar</button>
        <button class="br-button primary" type="submit" :disabled="enviando">
          {{ enviando ? 'Confirmando...' : 'Confirmar' }}
        </button>
      </div>
    </form>

    <Modal
      v-if="modalErroVisivel"
      :title="tituloModalErro"
      :content="mensagemErroModal"
      :show-actions="true"
      @close="fecharModalErro"
      @confirm="fecharModalErro"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useForm, useField } from 'vee-validate'
import * as yup from 'yup'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import Card from '@/core/components/Card/Card.vue'
import Modal from '@/core/components/Modal/Modal.vue'
import TermoUsoPrivacidade from '@/core/components/TermoUsoPrivacidade/TermoUsoPrivacidade.vue'
import Feedback from '@/core/components/Feedback/Feedback.vue'
import { useEsferas } from '@/core/composables/useEsferas'
import { useLocalidades } from '@/core/composables/useLocalidades'
import { usePerfis } from '@/core/composables/usePerfis'
import { useNotification } from '@/core/composables/useNotification'
import { validarCpf } from '@/core/utils/validarCpf'
import {
  enviarSolicitacaoCadastro,
  verificarCpfDisponivel,
} from '@/services/SolicitacaoCadastroService'
import type { PerfilOption } from '@/services/PerfilService'
import type { SolicitacaoCadastroPayload } from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'

const regexSomenteLetras = /^[a-zA-ZáàâãéèêíïóôõöúçñÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ\s]+$/

defineOptions({ name: 'FormularioCadastrarUsuario' })

const props = withDefaults(
  defineProps<{
    usuarioLogado?: {
      id?: number
      name?: string
      email?: string
      esfera_atuacao?: string
      uf_lotacao?: string
      municipio_lotacao?: string
    } | null
  }>(),
  {
    usuarioLogado: null,
  },
)

const emit = defineEmits<{
  (e: 'voltar'): void
  (e: 'sucesso'): void
}>()

const router = useRouter()
const { success, error } = useNotification()

const MENSAGENS_CPF: Record<string, string> = {
  'Já existe uma solicitação em análise para este CPF.':
    'Já existe uma solicitação em análise para este CPF. Aguarde a avaliação da equipe gestora.',
  'O CPF informado é inválido.': 'CPF inválido. Confira os números digitados.',
}

function mapearMensagemCpf(original: string): string {
  return MENSAGENS_CPF[original] ?? original
}

function mapearMensagemCadastro(original: string): string {
  if (original === 'Acesso não permitido.') {
    return 'Acesso não permitido para os dados informados. No cadastro interno, use a mesma esfera/UF/município da sua lotação.'
  }
  return original
}

const enviando = ref(false)
const modalErroVisivel = ref(false)
const mensagemErroModal = ref('')
const tituloModalErro = ref('Dados incompletos')

const esferaRef = ref<InstanceType<typeof SelectAutocomplete> | null>(null)
const ufRef = ref<InstanceType<typeof SelectAutocomplete> | null>(null)
const municipioRef = ref<InstanceType<typeof SelectAutocomplete> | null>(null)
const perfilRef = ref<InstanceType<typeof SelectAutocomplete> | null>(null)

/** Fixo (10 dígitos) ou celular (11 dígitos), igual à solicitação de cadastro público */
const telefoneMask = { mask: ['(##) ####-####', '(##) #####-####'] }

const schema = yup.object({
  nome: yup
    .string()
    .required('Informe o nome do usuário.')
    .trim()
    .matches(regexSomenteLetras, 'O nome deve conter apenas letras.'),
  CPF: yup
    .string()
    .required('Informe o CPF.')
    .trim()
    .test('cpf-valido', 'CPF inválido. Verifique os dígitos informados.', (value) =>
      validarCpf(value),
    ),
  emailInstitucional: yup
    .string()
    .required('Informe o e-mail institucional.')
    .trim()
    .email('Informe um e-mail válido.'),
  telefoneInstitucional: yup
    .string()
    .required('Informe o telefone institucional.')
    .trim()
    .test(
      'telefone',
      'Informe um telefone válido com 10 ou 11 dígitos (ex: (11) 3333-4444 ou (11) 99999-8888).',
      (value) => {
        if (!value) return false
        const digitos = value.replace(/\D/g, '')
        return digitos.length >= 10 && digitos.length <= 11
      },
    ),
  esferaAtuacao: yup.string().required('Selecione a esfera de atuação.').trim(),
  uf: yup.string().required('Selecione o estado (UF).').trim(),
  municipio: yup
    .string()
    .trim()
    .when('uf', {
      is: (uf: string) => !!uf?.trim(),
      then: (s) => s.required('Selecione o município.'),
      otherwise: (s) => s,
    }),
  orgao: yup.string().required('Informe o órgão de atuação.').trim(),
  cargo: yup.string().required('Informe o cargo ou função.').trim(),
  perfil: yup
    .mixed()
    .required('Selecione o perfil.')
    .test('perfil-valido', 'Selecione o perfil.', (v) => v != null && v !== ''),
  vigenciaInicio: yup.string().trim().required('Informe a vigência inicial.'),
  vigenciaFim: yup
    .string()
    .trim()
    .test(
      'vigencia-fim',
      'A data de fim deve ser igual ou posterior à data de início.',
      (value, ctx) => {
        if (!value) return true
        const inicio = ctx.parent.vigenciaInicio as string
        if (!inicio) return true
        return value >= inicio
      },
    ),
  telefonePessoal: yup
    .string()
    .trim()
    .test('telefone-pessoal', 'Telefone deve ter 10 ou 11 dígitos', (value) => {
      if (!value || !String(value).trim()) return true
      const digitos = value.replace(/\D/g, '')
      return digitos.length === 10 || digitos.length === 11
    }),
})

const initialValues = {
  nome: '',
  CPF: '',
  emailInstitucional: '',
  telefoneInstitucional: '',
  telefonePessoal: '',
  esferaAtuacao: '',
  uf: '',
  municipio: '',
  orgao: '',
  cargo: '',
  perfil: null,
  vigenciaInicio: '',
  vigenciaFim: '',
}

const {
  validateField,
  setFieldValue,
  setFieldError,
  validate,
  meta: formMeta,
} = useForm({
  validationSchema: schema,
  initialValues,
})

const { value: nome, errorMessage: errorsNome } = useField<string>('nome')
const { value: cpf, errorMessage: errorsCpf } = useField<string>('CPF')
const { value: emailInstitucional, errorMessage: errorsEmail } =
  useField<string>('emailInstitucional')
const { value: telefoneInstitucional, errorMessage: errorsTelInst } =
  useField<string>('telefoneInstitucional')
const { value: telefonePessoal, errorMessage: errorsTelPessoal } =
  useField<string>('telefonePessoal')
const { value: esferaAtuacao, errorMessage: errorsEsfera } = useField<string>('esferaAtuacao')
const { value: uf, errorMessage: errorsUf } = useField<string>('uf')
const { value: municipio, errorMessage: errorsMunicipio } = useField<string>('municipio')

const verificandoCpf = ref(false)

async function onCpfBlur() {
  await validateField('CPF')
  if (errorsCpf.value) return

  const digitos = String(cpf.value ?? '').replace(/\D/g, '')
  if (digitos.length !== 11) return
  if (!validarCpf(digitos)) return

  verificandoCpf.value = true
  try {
    const res = await verificarCpfDisponivel(digitos)
    if (!res.disponivel) {
      setFieldError('CPF', mapearMensagemCpf(res.mensagem))
    }
  } catch {
    setFieldError('CPF', 'Não foi possível verificar o CPF. Tente novamente.')
  } finally {
    verificandoCpf.value = false
  }
}

const { opcoesUf, opcoesMunicipio, carregarUfs } = useLocalidades(uf)
const { opcoesEsfera, carregarEsferas } = useEsferas()
const { opcoesPerfil, carregarPerfis } = usePerfis()
const esferaUsuarioLogado = computed(() =>
  String(props.usuarioLogado?.esfera_atuacao ?? '').toLowerCase(),
)
const isEsferaBloqueada = computed(
  () => esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal',
)
const isUfBloqueada = computed(
  () => esferaUsuarioLogado.value === 'estadual' || esferaUsuarioLogado.value === 'municipal',
)
const isMunicipioBloqueado = computed(() => esferaUsuarioLogado.value === 'municipal')
const opcoesEsferaFiltradas = computed(() => {
  if (esferaUsuarioLogado.value === 'estadual') {
    return opcoesEsfera.value.filter((o) => String(o.value).toLowerCase() === 'estadual')
  }
  if (esferaUsuarioLogado.value === 'municipal') {
    return opcoesEsfera.value.filter((o) => String(o.value).toLowerCase() === 'municipal')
  }
  return opcoesEsfera.value
})
const opcoesUfFiltradas = computed(() => {
  if (isUfBloqueada.value && props.usuarioLogado?.uf_lotacao) {
    return opcoesUf.value.filter(
      (o) =>
        String(o.value).toUpperCase() === String(props.usuarioLogado?.uf_lotacao).toUpperCase(),
    )
  }
  return opcoesUf.value
})
const opcoesMunicipioFiltradas = computed(() => {
  if (isMunicipioBloqueado.value && props.usuarioLogado?.municipio_lotacao) {
    return opcoesMunicipio.value.filter(
      (o) =>
        String(o.label).toLowerCase() ===
        String(props.usuarioLogado?.municipio_lotacao).toLowerCase(),
    )
  }
  return opcoesMunicipio.value
})
const opcoesPerfilFiltradas = computed(() => {
  if (esferaUsuarioLogado.value === 'estadual') {
    return opcoesPerfil.value.filter((p) => String(p.label).toLowerCase().includes('estadual'))
  }
  if (esferaUsuarioLogado.value === 'municipal') {
    return opcoesPerfil.value.filter((p) => String(p.label).toLowerCase().includes('municipal'))
  }
  return opcoesPerfil.value
})

onMounted(() => {
  carregarUfs()
  carregarEsferas()
  carregarPerfis()
})

watch(
  uf,
  () => {
    municipio.value = ''
  },
  { immediate: true },
)

watch(
  () => props.usuarioLogado,
  (usuario) => {
    if (!usuario) return

    if (esferaUsuarioLogado.value === 'estadual') {
      esferaAtuacao.value = 'estadual'
      if (usuario.uf_lotacao) uf.value = usuario.uf_lotacao
      return
    }

    if (esferaUsuarioLogado.value === 'municipal') {
      esferaAtuacao.value = 'municipal'
      if (usuario.uf_lotacao) uf.value = usuario.uf_lotacao
      if (usuario.municipio_lotacao) municipio.value = usuario.municipio_lotacao
    }
  },
  { immediate: true, deep: true },
)

watch(opcoesPerfilFiltradas, (opcoes) => {
  if (!opcoes.length) {
    perfil.value = null
    return
  }
  if (perfil.value == null) return
  const existe = opcoes.some((op) => String(op.value) === String(perfil.value))
  if (!existe) perfil.value = null
})

watch(opcoesMunicipioFiltradas, (opcoes) => {
  if (!isMunicipioBloqueado.value) return
  const municipioLotacao = String(props.usuarioLogado?.municipio_lotacao ?? '')
  if (!municipioLotacao) return
  const opcao = opcoes.find((o) => String(o.label).toLowerCase() === municipioLotacao.toLowerCase())
  if (opcao) municipio.value = String(opcao.value ?? opcao.label)
})

const { value: orgao, errorMessage: errorsOrgao } = useField<string>('orgao')
const { value: cargo, errorMessage: errorsCargo } = useField<string>('cargo')
const { value: perfil, errorMessage: errorsPerfil } = useField<string | number | null>('perfil')
const { value: vigenciaInicio, errorMessage: errorsVigenciaInicio } =
  useField<string>('vigenciaInicio')
const { value: vigenciaFim, errorMessage: errorsVigenciaFim } = useField<string>('vigenciaFim')

const formularioPreenchido = computed(() => {
  const camposTexto = [
    nome,
    cpf,
    emailInstitucional,
    telefoneInstitucional,
    esferaAtuacao,
    uf,
    municipio,
    orgao,
    cargo,
  ]
  const todosPreenchidos = camposTexto.every((f) => String(f.value ?? '').trim() !== '')
  const perfilPreenchido = perfil.value !== null && perfil.value !== ''
  const vigenciaPreenchida = String(vigenciaInicio.value ?? '').trim() !== ''
  return todosPreenchidos && perfilPreenchido && vigenciaPreenchida && formMeta.value.valid
})

const MAPA_CAMPO_PARA_FOCO: Record<string, string> = {
  nome: 'cad-nome',
  CPF: 'cad-cpf',
  emailInstitucional: 'cad-email',
  telefoneInstitucional: 'cad-tel-inst',
  esferaAtuacao: 'focusEsfera',
  uf: 'focusUf',
  municipio: 'focusMunicipio',
  orgao: 'cad-orgao',
  cargo: 'cad-cargo',
  perfil: 'focusPerfil',
  vigenciaInicio: 'cad-vigencia-inicio',
  vigenciaFim: 'cad-vigencia-fim',
}

function focusarCampo(campo: string) {
  nextTick(() => {
    const alvo = MAPA_CAMPO_PARA_FOCO[campo]
    if (typeof alvo === 'string' && alvo.startsWith('cad-')) {
      const el = document.getElementById(alvo)
      el?.focus()
      el?.scrollIntoView({ behavior: 'smooth', block: 'center' })
    } else if (alvo === 'focusEsfera') {
      esferaRef.value?.focus()
    } else if (alvo === 'focusUf') {
      ufRef.value?.focus()
    } else if (alvo === 'focusMunicipio') {
      municipioRef.value?.focus()
    } else if (alvo === 'focusPerfil') {
      perfilRef.value?.focus()
    }
  })
}

function onEsferaChange(val: string | number | null) {
  esferaAtuacao.value = val != null ? String(val) : ''
  setFieldValue('esferaAtuacao', esferaAtuacao.value)
}

function onUfChange(val: string | number | null) {
  uf.value = val != null ? String(val) : ''
  setFieldValue('uf', uf.value)
}

function onMunicipioChange(val: string | number | null) {
  municipio.value = val != null ? String(val) : ''
  setFieldValue('municipio', municipio.value)
}

function onPerfilChange(val: string | number | null) {
  perfil.value = val
  setFieldValue('perfil', val as never)
}

function lerValoresDosRefs(): Record<string, unknown> {
  return {
    nome: nome.value,
    CPF: cpf.value,
    emailInstitucional: emailInstitucional.value,
    telefoneInstitucional: telefoneInstitucional.value,
    esferaAtuacao: esferaAtuacao.value,
    uf: uf.value,
    municipio: municipio.value,
    orgao: orgao.value,
    cargo: cargo.value,
    perfil: perfil.value,
    vigenciaInicio: vigenciaInicio.value,
    vigenciaFim: vigenciaFim.value,
  }
}

async function onConfirmar() {
  setFieldValue('perfil', perfil.value as never)
  setFieldValue('esferaAtuacao', esferaAtuacao.value)
  setFieldValue('uf', uf.value)
  setFieldValue('municipio', municipio.value)

  const resultado = await validate()

  if (!resultado.valid) {
    const erros = resultado.errors as Record<string, string | undefined>
    const primeiroCampo = Object.keys(erros)[0]
    const mensagem = primeiroCampo
      ? (erros[primeiroCampo] ?? 'Preencha os campos obrigatórios.')
      : 'Preencha os campos obrigatórios.'
    tituloModalErro.value = 'Dados incompletos'
    mensagemErroModal.value = mensagem
    modalErroVisivel.value = true
    if (primeiroCampo) focusarCampo(primeiroCampo)
    return
  }

  const values = lerValoresDosRefs()
  await onSubmit(values)
}

function onInvalid(ctx: { errors: Partial<Record<string, string>> }) {
  const erros = ctx.errors
  const primeiroCampo = Object.keys(erros)[0]
  const mensagem = primeiroCampo
    ? (erros[primeiroCampo] ?? 'Preencha os campos obrigatórios.')
    : 'Preencha os campos obrigatórios.'
  tituloModalErro.value = 'Dados incompletos'
  mensagemErroModal.value = mensagem
  modalErroVisivel.value = true
  if (primeiroCampo) focusarCampo(primeiroCampo)
}

function fecharModalErro() {
  modalErroVisivel.value = false
  mensagemErroModal.value = ''
}

function montarPayload(): SolicitacaoCadastroPayload {
  const cpfVal = String(cpf.value ?? '').replace(/\D/g, '')
  const perfilNum = perfil.value != null && perfil.value !== '' ? Number(perfil.value) : NaN
  const telPessoal = telefonePessoal.value
    ? String(telefonePessoal.value).replace(/\D/g, '')
    : undefined
  return {
    nome: String(nome.value ?? '').trim(),
    cpf: cpfVal || undefined,
    email_institucional: String(emailInstitucional.value ?? '').trim(),
    telefone_institucional: String(telefoneInstitucional.value ?? '').replace(/\D/g, ''),
    telefone_pessoal: telPessoal,
    esfera_id: Number(esferaAtuacao.value ?? ''),
    uf_id: Number(uf.value ?? ''),
    municipio_id: Number(municipio.value ?? ''),
    orgao: String(orgao.value ?? '').trim(),
    cargo: String(cargo.value ?? '').trim(),
    perfilId: !Number.isNaN(perfilNum) && perfilNum > 0 ? perfilNum : undefined,
    vigenciaInicio: String(vigenciaInicio.value ?? '').trim() || undefined,
    vigenciaFim: String(vigenciaFim.value ?? '').trim() || undefined,
  }
}

async function onSubmit(values: Record<string, unknown>) {
  const erroRegra = validarHierarquiaNoFrontend(values)
  if (erroRegra) {
    tituloModalErro.value = 'Erro ao cadastrar'
    mensagemErroModal.value = mapearMensagemCadastro(erroRegra)
    modalErroVisivel.value = true
    return
  }

  enviando.value = true
  try {
    const payload = montarPayload()
    console.debug('[Cadastro] Payload final:', JSON.stringify(payload, null, 2))
    console.debug('[Cadastro] perfil.value =', perfil.value, '| perfilId =', payload.perfilId)
    await enviarSolicitacaoCadastro(payload)
    const msgSucesso =
      'Cadastro realizado com sucesso! A solicitação foi registrada com status "Em análise" e está disponível na lista.'
    success(msgSucesso)
    emit('sucesso')
    await router.push({ name: 'gerenciar-cadastros' })
  } catch (e: unknown) {
    const res = (
      e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    )?.response
    const data = res?.data
    let msg =
      data?.message ?? 'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.'
    if (data?.errors && typeof data.errors === 'object') {
      const firstField = Object.keys(data.errors)[0]
      const firstMsg = firstField ? data.errors[firstField]?.[0] : null
      if (firstMsg) msg = firstMsg
    }
    if (!res && (e as Error)?.message) {
      msg = (e as Error).message
    }
    const isCpfError =
      msg.toLowerCase().includes('cpf') || (data?.errors && 'CPF' in (data?.errors as object))
    if (isCpfError) {
      const cpfMsg =
        data?.errors && typeof data.errors === 'object' && 'CPF' in data.errors
          ? (data.errors as Record<string, string[]>).CPF?.[0]
          : msg
      msg = mapearMensagemCpf(cpfMsg ?? msg)
    }
    msg = mapearMensagemCadastro(msg)
    tituloModalErro.value = 'Erro ao cadastrar'
    mensagemErroModal.value = msg
    modalErroVisivel.value = true
    error(msg)
  } finally {
    enviando.value = false
  }
}

function validarHierarquiaNoFrontend(_values: Record<string, unknown>): string | null {
  const esferaVal = String(esferaAtuacao.value ?? '').toLowerCase()
  const ufValor = String(uf.value ?? '').toUpperCase()
  const municipioValor = String(municipio.value ?? '')
    .toLowerCase()
    .trim()
  const perfilSelecionado = opcoesPerfil.value.find(
    (op: PerfilOption) => String(op.value) === String(perfil.value ?? ''),
  )
  const tipoPerfil = inferirTipoPerfilPorNome(perfilSelecionado?.label ?? '')

  if (esferaUsuarioLogado.value === 'estadual') {
    if (tipoPerfil !== 'estadual' || esferaVal !== 'estadual') return 'Acesso não permitido.'
    if (ufValor !== String(props.usuarioLogado?.uf_lotacao ?? '').toUpperCase())
      return 'Acesso não permitido.'
  }

  if (esferaUsuarioLogado.value === 'municipal') {
    if (tipoPerfil !== 'municipal' || esferaVal !== 'municipal') return 'Acesso não permitido.'
    if (ufValor !== String(props.usuarioLogado?.uf_lotacao ?? '').toUpperCase())
      return 'Acesso não permitido.'
    if (
      municipioValor !==
      String(props.usuarioLogado?.municipio_lotacao ?? '')
        .toLowerCase()
        .trim()
    )
      return 'Acesso não permitido.'
  }

  return null
}

function inferirTipoPerfilPorNome(
  nome: string,
): 'federal' | 'estadual' | 'municipal' | 'desconhecido' {
  const label = nome.toLowerCase()
  if (label.includes('federal') || label.includes('nacional')) return 'federal'
  if (label.includes('estadual')) return 'estadual'
  if (label.includes('municipal')) return 'municipal'
  return 'desconhecido'
}
</script>

<style scoped>
.formulario-cadastrar-usuario {
  padding: 1rem;
  color: var(--secondary-text-color);
}

.formulario-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.formulario-titulo {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  text-transform: uppercase;
}

.formulario-secao {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-secondary-04, #ddd);
}

.formulario-secao:last-of-type {
  border-bottom: none;
}

.secao-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
}

.secao-subtitulo {
  font-size: 0.875rem;
  color: var(--secondary-text-color, #555);
  margin: 0 0 1rem;
}

.secao-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.secao-linha--3cols {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.secao-linha--2cols {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.secao-linha--1col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

/* Informação do solicitante: linha 1 = Esfera, UF, Município | linha 2 = Órgão, Cargo */
.secao-info-solicitante {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.secao-linha {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.secao-linha:last-child {
  grid-template-columns: repeat(2, 1fr);
}

/* Dados de Perfil: todos na mesma linha */
.secao-perfil-linha {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

/* Dados do solicitante — alinhado ao padrão eGOV / solicitação pública */

.cadastro-user-form-grid :deep(.br-input input:not([readonly])) {
  min-height: 2.5rem;
  border-radius: 6px;
}

.cadastro-user-form-grid :deep(.br-input label) {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--dark-text-color);
  margin-bottom: 0.25rem;
}

.cadastro-field-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--secondary-text-color-02, #666);
  margin-top: 0.35rem;
  line-height: 1.35;
}

.cadastro-field-hint--loading {
  color: var(--primary-text-color);
  font-style: italic;
}

.cadastro-label-opcional {
  font-weight: 500;
  color: var(--secondary-text-color-02, #666);
  font-size: 0.8125rem;
}

.input-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--secondary-text-color, #555);
  margin-top: 0.25rem;
}

.field-with-feedback {
  display: flex;
  flex-direction: column;
}

/* Campos de data com ícone de calendário */
.input-date-wrapper {
  position: relative;
}

.input-date-wrapper input[type='date'] {
  padding-right: 2.5rem;
}

.input-date-icon {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--secondary-text-color-02, #888);
  pointer-events: none;
}

.formulario-termo-card {
  margin-bottom: 1.5rem;
}

.formulario-termo-card :deep(.solicitacao-card) {
  margin-top: 0 !important;
}

.formulario-acoes {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

@media (max-width: 575px) {
  .formulario-cadastrar-usuario {
    padding: 0.75rem;
  }

  .formulario-titulo {
    font-size: 1.25rem;
  }

  .secao-grid {
    grid-template-columns: 1fr;
  }

  .secao-linha,
  .secao-linha--3cols,
  .secao-linha--2cols,
  .secao-linha--1col {
    grid-template-columns: 1fr;
  }

  .secao-linha:last-child {
    grid-template-columns: 1fr;
  }

  .secao-perfil-linha {
    grid-template-columns: 1fr;
  }

  .formulario-acoes {
    flex-direction: column;
  }

  .formulario-acoes .br-button {
    width: 100%;
  }
}

@media (min-width: 576px) and (max-width: 991px) {
  .secao-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-linha,
  .secao-linha--3cols,
  .secao-linha--2cols {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-linha--1col {
    grid-template-columns: 1fr;
  }

  .secao-linha:last-child {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-perfil-linha {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
