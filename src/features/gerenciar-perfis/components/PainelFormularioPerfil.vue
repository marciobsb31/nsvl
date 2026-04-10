<template>
  <div class="painel-perfil">
    <!-- Hero header (padrão solicitacao-hero) -->
    <header class="painel-hero">
      <div class="painel-hero__topo">
        <h1 class="painel-hero__title">{{ titulosPainel[modo] }}</h1>
        <button
          class="br-button secondary small painel-hero__btn-voltar"
          type="button"
          @click="handleVoltar"
          aria-label="Voltar"
        >
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          <span>Voltar</span>
        </button>
      </div>
      <p class="painel-hero__lead">
        {{ descricoesPainel[modo] }}
        <template v-if="!somenteLeitura">
          Campos marcados com <span class="painel-hero__req">*</span> são obrigatórios.
        </template>
      </p>
    </header>

    <form novalidate class="painel-form" @submit.prevent="handleSalvar">
      <!-- Card 1: Dados do Perfil -->
      <Card
        title="Dados do Perfil"
        subtitle="Identificação básica — nome e tipo do perfil de acesso."
      >
        <section class="row g-3">
          <div class="col-12 col-md-5">
            <div class="br-input" :class="{ danger: erros.nome }">
              <label for="pf-nome">Nome do Perfil<span class="text-red-50 text-up-01"> *</span></label>
              <input
                id="pf-nome"
                v-model="form.nome"
                type="text"
                maxlength="100"
                :disabled="somenteLeitura || salvando"
                placeholder="Ex: Gestor Federal..."
                :aria-invalid="!!erros.nome"
                aria-describedby="pf-nome-err"
              />
              <span v-if="erros.nome" id="pf-nome-err" class="feedback danger" role="alert">
                <i class="fas fa-times-circle" aria-hidden="true"></i> {{ erros.nome }}
              </span>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="br-input">
              <label for="pf-ativo">Situação<span class="text-red-50 text-up-01"> *</span></label>
              <select
                id="pf-ativo"
                v-model="form.ativo"
                class="br-select-native"
                :disabled="somenteLeitura || salvando"
                aria-describedby="pf-ativo-hint"
              >
                <option :value="true">Ativo</option>
                <option :value="false">Inativo</option>
              </select>
              <span id="pf-ativo-hint" class="field-hint">Define se o perfil está em uso no sistema.</span>
            </div>
          </div>
        </section>
      </Card>

      <!-- Card 2: Descrição -->
      <Card
        title="Descrição"
        subtitle="Finalidade e escopo de atuação do perfil."
      >
        <section class="row g-3">
          <div class="col-12">
            <div class="br-input">
              <label for="pf-descricao">Descrição do perfil</label>
              <textarea
                id="pf-descricao"
                v-model="form.descricao"
                class="br-textarea-native"
                rows="4"
                maxlength="255"
                placeholder="Descreva a finalidade e escopo de atuação do perfil..."
                :disabled="somenteLeitura || salvando"
              ></textarea>
              <span class="field-hint">{{ form.descricao.length }}/255 caracteres</span>
            </div>
          </div>
        </section>
      </Card>

      <!-- Ações (padrão solicitacao-acoes) -->
      <div class="painel-acoes">
        <button
          class="br-button secondary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="salvando"
          @click="handleVoltar"
        >
          <i class="fas fa-times" aria-hidden="true"></i>
          <span>Cancelar</span>
        </button>
        <button
          v-if="!somenteLeitura"
          class="br-button primary painel-acoes__btn painel-acoes__btn--principal painel-acoes__btn--com-icone"
          type="submit"
          :disabled="salvando"
          :aria-busy="salvando"
        >
          <i v-if="!salvando" class="fas fa-save" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>{{ salvando ? 'Salvando...' : (modo === 'editar' ? 'Atualizar perfil' : 'Confirmar e salvar perfil') }}</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import Card from '@/core/components/Card/Card.vue'
import {
  cadastrarPerfil,
  atualizarPerfil,
  obterHierarquia,
  type PerfilGerenciar,
} from '@/services/GerenciarPerfilService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'PainelFormularioPerfil' })

type Modo = 'cadastrar' | 'editar' | 'visualizar'

const titulosPainel: Record<Modo, string> = {
  cadastrar: 'Cadastrar perfil',
  editar: 'Editar perfil',
  visualizar: 'Visualizar perfil',
}

const descricoesPainel: Record<Modo, string> = {
  cadastrar: 'Preencha os dados abaixo para criar um novo perfil de acesso.',
  editar: 'Atualize as informações do perfil selecionado.',
  visualizar: 'Detalhes do perfil de acesso selecionado.',
}

const props = defineProps<{
  modo: Modo
  perfil?: PerfilGerenciar | null
}>()

const emit = defineEmits<{
  (e: 'voltar'): void
  (e: 'sucesso'): void
  (e: 'dirty', value: boolean): void
}>()

const { success, error } = useNotification()

const somenteLeitura = ref(props.modo === 'visualizar')
const salvando = ref(false)
const erros = reactive<Record<string, string>>({})

const form = reactive({
  nome: '',
  descricao: '',
  ativo: true,
})

const initialSnapshot = ref('')

function capturarSnapshot(): string {
  return JSON.stringify({
    nome: form.nome,
    descricao: form.descricao,
    ativo: form.ativo,
  })
}

const isDirty = computed(() => {
  if (somenteLeitura.value) return false
  return capturarSnapshot() !== initialSnapshot.value
})

watch(isDirty, (val) => emit('dirty', val))

watch(() => props.perfil, (p) => {
  if (p) {
    form.nome = p.nome
    form.descricao = p.descricao ?? ''
    form.ativo = p.ativo
  } else {
    form.nome = ''
    form.descricao = ''
    form.ativo = true
  }
  initialSnapshot.value = capturarSnapshot()
}, { immediate: true })

watch(() => props.modo, (m) => {
  somenteLeitura.value = m === 'visualizar'
})

onMounted(async () => {
  try {
    await obterHierarquia()
  } catch {
    // Hierarquia indisponível — continua com permissões padrão
  } finally {
    initialSnapshot.value = capturarSnapshot()
  }
})

function handleVoltar() {
  emit('voltar')
}

async function handleSalvar() {
  Object.keys(erros).forEach((k) => delete erros[k])

  if (!form.nome.trim()) {
    erros.nome = 'Preencha os campos obrigatórios.'
    return
  }

  salvando.value = true
  try {
    const payload = {
      nome: form.nome.trim(),
      descricao: form.descricao.trim() || undefined,
      ativo: form.ativo,
    }

    let resultado: { message: string }

    if (props.modo === 'editar' && props.perfil) {
      resultado = await atualizarPerfil(props.perfil.id, payload)
    } else {
      resultado = await cadastrarPerfil(payload)
    }

    success(resultado.message)
    emit('sucesso')
  } catch (e: unknown) {
    const err = e as { response?: { status?: number; data?: { message?: string; errors?: Record<string, string[]> } } }
    const data = err?.response?.data
    const validationErrors = data?.errors
    if (err?.response?.status === 422 && validationErrors) {
      const keys = Object.keys(validationErrors)
      const firstKey = keys[0] ?? ''
      const firstMsg = validationErrors[firstKey]?.[0] ?? 'Erro de validação.'
      if (firstKey === 'nome') erros.nome = firstMsg
      error(firstMsg)
    } else if (err?.response?.status === 403) {
      error(data?.message || 'Acesso não permitido.')
    } else {
      error(data?.message || 'Erro ao salvar perfil.')
    }
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.painel-perfil {
  padding: 1.25rem;
}

@media (min-width: 576px) {
  .painel-perfil { padding: 1.5rem; }
}

@media (min-width: 1200px) {
  .painel-perfil { padding: 2rem; }
}

/* ── Hero (hierarquia sóbria, alinhado à lista de perfis) ── */
.painel-hero {
  margin-bottom: 1.5rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
}

.painel-hero__topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.painel-hero__btn-voltar {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.painel-hero__title {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  font-weight: 600;
  line-height: 1.25;
  color: var(--color-primary-darken-02, #0c326f);
  letter-spacing: -0.015em;
}

@media (min-width: 768px) {
  .painel-hero__title { font-size: 1.75rem; }
}

.painel-hero__lead {
  margin: 0;
  max-width: 62rem;
  font-size: 0.9375rem;
  line-height: 1.55;
  color: var(--color-secondary-07, #555);
}

.painel-hero__req {
  color: var(--color-danger, #e52207);
  font-weight: 700;
}

/* ── Form (padrão solicitacao-form) ── */
.painel-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

@media (min-width: 768px) {
  .painel-form { gap: 1.5rem; }
}

/* ── Campos br-input (padrão DS gov.br) ── */
.painel-perfil :deep(.br-input input),
.painel-perfil :deep(.br-input select),
.painel-perfil :deep(.br-input textarea) {
  min-height: 2.5rem;
  border-radius: 6px;
}

.painel-perfil :deep(.br-input label) {
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--color-secondary-09, #333);
  margin-bottom: 0.25rem;
}

.br-select-native {
  width: 100%;
  min-height: 2.5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  border: 1px solid var(--color-secondary-05, #bbb);
  border-radius: 6px;
  outline: none;
  background: #fff;
  cursor: pointer;
  appearance: auto;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.br-select-native:focus {
  border-color: var(--color-primary-default, #1351b4);
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.12);
}

.br-select-native:disabled {
  background: var(--color-secondary-02, #f5f5f5);
  color: var(--color-secondary-06, #888);
  cursor: not-allowed;
}

.br-textarea-native {
  width: 100%;
  min-height: 5rem;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  border: 1px solid var(--color-secondary-05, #bbb);
  border-radius: 6px;
  outline: none;
  background: #fff;
  resize: vertical;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.br-textarea-native:focus {
  border-color: var(--color-primary-default, #1351b4);
  box-shadow: 0 0 0 3px rgba(19, 81, 180, 0.12);
}

.br-textarea-native:disabled {
  background: var(--color-secondary-02, #f5f5f5);
  color: var(--color-secondary-06, #888);
  cursor: not-allowed;
}

.field-hint {
  display: block;
  font-size: 0.75rem;
  margin-top: 0.35rem;
  color: var(--color-secondary-06, #666);
}

/* ── Feedback de erro (padrão DS) ── */
.feedback.danger {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  color: #e52207;
  margin-top: 0.25rem;
}

.br-input.danger input,
.br-input.danger select {
  border-color: #e52207;
}

/* ── Ações (padrão solicitacao-acoes) ── */
.painel-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
  margin-top: 0.25rem;
  padding: 1.25rem 0 0;
  border-top: 1px solid var(--color-secondary-03, #e8e8e8);
}

.painel-acoes__btn {
  width: 100%;
  min-height: 2.75rem;
  justify-content: center;
}

@media (min-width: 576px) {
  .painel-acoes {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
  }

  .painel-acoes__btn {
    width: auto;
    min-width: 10rem;
  }

  .painel-acoes__btn--principal {
    min-width: 14rem;
  }
}

.painel-acoes__btn--com-icone {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.painel-acoes__btn--com-icone i {
  font-size: 0.875rem;
}
</style>
