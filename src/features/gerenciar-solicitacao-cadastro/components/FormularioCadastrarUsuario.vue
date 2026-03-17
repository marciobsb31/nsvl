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

    <Form :validation-schema="schema" :initial-values="initialValues" @submit="onSubmit">
      <div class="formulario-secao">
        <h3 class="secao-titulo">Dados do solicitante</h3>
        <div class="secao-grid">
          <div class="br-input mb-2">
            <label for="cad-nome">Nome</label>
            <input
              id="cad-nome"
              type="text"
              placeholder="(GOV.BR) Nome do Usuário"
              v-model="nome"
            />
          </div>
          <div class="br-input mb-2">
            <label for="cad-cpf">CPF</label>
            <input
              id="cad-cpf"
              type="text"
              placeholder="000.000.000-00"
              v-model="cpf"
              v-maska="'###.###.###-##'"
            />
          </div>
          <div class="br-input mb-2">
            <label for="cad-email">E-mail Institucional<span class="obrigatorio">*</span></label>
            <input
              id="cad-email"
              type="email"
              placeholder="seu.nome@orgao.gov.br"
              v-model="emailInstitucional"
            />
          </div>
          <div class="br-input mb-2">
            <label for="cad-tel-inst">Telefone Institucional<span class="obrigatorio">*</span></label>
            <input
              id="cad-tel-inst"
              type="tel"
              placeholder="(00) 00000-0000"
              v-model="telefoneInstitucional"
              v-maska="'(##) #####-####'"
            />
          </div>
          <div class="br-input mb-2">
            <label for="cad-tel-pessoal">Telefone Pessoal (opcional)</label>
            <input
              id="cad-tel-pessoal"
              type="tel"
              placeholder="(00) 00000-0000"
              v-model="telefonePessoal"
              v-maska="'(##) #####-####'"
            />
          </div>
        </div>
      </div>

      <div class="formulario-secao">
        <h3 class="secao-titulo">Informação do(a) solicitante</h3>
        <p class="secao-subtitulo">Informações de atuação institucional do solicitante.</p>
        <div class="secao-info-solicitante">
          <div class="secao-linha">
            <SelectAutocomplete
              v-model="esferaAtuacao"
              label="Esfera de atuação"
              placeholder="Selecione"
              :options="OPCOES_ESFERA"
            />
            <SelectAutocomplete
              v-model="uf"
              label="UF"
              placeholder="Selecione"
              :options="OPCOES_UF"
            />
            <div class="br-input mb-2">
              <label for="cad-municipio">Município<span class="obrigatorio">*</span></label>
              <input id="cad-municipio" type="text" placeholder="Município" v-model="municipio" />
            </div>
          </div>
          <div class="secao-linha">
            <div class="br-input mb-2">
              <label for="cad-orgao">Órgão de atuação<span class="obrigatorio">*</span></label>
              <input id="cad-orgao" type="text" placeholder="Órgão" v-model="orgao" />
            </div>
            <div class="br-input mb-2">
              <label for="cad-cargo">Cargo/Função<span class="obrigatorio">*</span></label>
              <input id="cad-cargo" type="text" placeholder="Cargo ou função" v-model="cargo" />
            </div>
          </div>
        </div>
      </div>

      <div class="formulario-secao">
        <h3 class="secao-titulo">Dados de Perfil</h3>
        <div class="secao-perfil-linha">
          <SelectAutocomplete
            v-model="perfil"
            label="Perfil"
            placeholder="Selecione"
            :options="OPCOES_PERFIL"
          />
          <div class="br-input mb-2">
            <label for="cad-vigencia-inicio">Vigência (início)</label>
            <input
              id="cad-vigencia-inicio"
              type="text"
              placeholder="dd/mm/aaaa"
              v-model="vigenciaInicio"
              v-maska="'##/##/####'"
            />
          </div>
          <div class="br-input mb-2">
            <label for="cad-vigencia-fim">Vigência (fim)</label>
            <input
              id="cad-vigencia-fim"
              type="text"
              placeholder="dd/mm/aaaa"
              v-model="vigenciaFim"
              v-maska="'##/##/####'"
            />
          </div>
        </div>
      </div>

      <div class="formulario-acoes">
        <button class="br-button secondary" type="button" @click="$emit('voltar')">
          Cancelar
        </button>
        <button class="br-button primary" type="submit" :disabled="enviando">
          {{ enviando ? 'Confirmando...' : 'Confirmar' }}
        </button>
      </div>
    </Form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Form, useField } from 'vee-validate'
import * as yup from 'yup'
import SelectAutocomplete from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { OPCOES_ESFERA, OPCOES_UF } from '../constants/opcoesFiltro'

const OPCOES_PERFIL = [
  { value: 'gestor', label: 'Gestor' },
  { value: 'analista', label: 'Analista' },
  { value: 'visualizador', label: 'Visualizador' },
]

defineOptions({ name: 'FormularioCadastrarUsuario' })

const emit = defineEmits<{
  (e: 'voltar'): void
  (e: 'confirmar', values: Record<string, unknown>): void
}>()

const enviando = ref(false)

const schema = yup.object({
  nome: yup.string(),
  CPF: yup.string(),
  emailInstitucional: yup.string().email('E-mail inválido').required('Obrigatório'),
  telefoneInstitucional: yup.string().required('Obrigatório'),
  telefonePessoal: yup.string(),
  esferaAtuacao: yup.string().required('Obrigatório'),
  uf: yup.string().required('Obrigatório'),
  municipio: yup.string().required('Obrigatório'),
  orgao: yup.string().required('Obrigatório'),
  cargo: yup.string().required('Obrigatório'),
  perfil: yup.string(),
  vigenciaInicio: yup.string(),
  vigenciaFim: yup.string(),
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
  perfil: '',
  vigenciaInicio: '',
  vigenciaFim: '',
}

const { value: nome } = useField<string>('nome')
const { value: cpf } = useField<string>('CPF')
const { value: emailInstitucional } = useField<string>('emailInstitucional')
const { value: telefoneInstitucional } = useField<string>('telefoneInstitucional')
const { value: telefonePessoal } = useField<string>('telefonePessoal')
const { value: esferaAtuacao } = useField<string>('esferaAtuacao')
const { value: uf } = useField<string>('uf')
const { value: municipio } = useField<string>('municipio')
const { value: orgao } = useField<string>('orgao')
const { value: cargo } = useField<string>('cargo')
const { value: perfil } = useField<string>('perfil')
const { value: vigenciaInicio } = useField<string>('vigenciaInicio')
const { value: vigenciaFim } = useField<string>('vigenciaFim')

async function onSubmit(values: Record<string, unknown>) {
  enviando.value = true
  try {
    emit('confirmar', values)
  } finally {
    enviando.value = false
  }
}
</script>

<style scoped>
.formulario-cadastrar-usuario {
  padding: 1rem;
  color: var(--color-secondary-08, #333);
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
  color: var(--color-secondary-07, #555);
  margin: 0 0 1rem;
}

.secao-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
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

.obrigatorio {
  color: var(--color-primary-default, #1351b4);
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

  .secao-linha {
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

  .secao-linha {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-linha:last-child {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-perfil-linha {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
