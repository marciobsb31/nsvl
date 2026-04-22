<template>
  <DefaultLayout>
    <HeaderPage
      title="Identificação do plano"
      subtitle="Preencha os dados gerais do plano de ação."
      customClass="mb-4"
    >
      <template #actions>
        <button
          class="br-button secondary"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
          Voltar para seções
        </button>
      </template>
    </HeaderPage>

    <form novalidate @submit.prevent>

      <Card
        title="Identificação do plano de ação"
        subtitle="Campos marcados com * são obrigatórios."
        custom-class="mb-4"
      >
        <section class="row g-3">

          <!-- Estado / Município -->
          <div class="col-12 col-md-6">
            <div class="br-input">
              <label for="campo-localidade">Estado / Município</label>
              <input
                id="campo-localidade"
                type="text"
                :value="localidade"
                readonly
                aria-readonly="true"
                class="input-readonly"
              />
            </div>
          </div>

          <!-- Órgão gestor -->
          <div class="col-12">
            <div class="br-input" :class="{ danger: erros.orgaoGestor }">
              <label for="campo-orgao-gestor">
                Órgão gestor da política da pessoa com deficiência (Prefeitura/Governo)
                <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="campo-orgao-gestor"
                type="text"
                v-model="form.orgaoGestor"
                maxlength="255"
                aria-required="true"
                :aria-describedby="erros.orgaoGestor ? 'erro-orgao-gestor' : undefined"
                @blur="validarOrgaoGestor"
              />
              <span
                v-if="erros.orgaoGestor"
                id="erro-orgao-gestor"
                class="feedback danger"
                role="alert"
              >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.orgaoGestor }}
              </span>
            </div>
          </div>

          <!-- Demais secretarias/órgãos -->
          <div class="col-12 col-md-8">
            <label class="br-label" for="campo-secretaria">Demais secretarias/órgãos envolvidos</label>
            <div class="secretarias-input-row mt-1">
              <div class="br-input" style="flex:1; margin-bottom:0">
                <input
                  id="campo-secretaria"
                  type="text"
                  v-model="novaSecretaria"
                  maxlength="255"
                  placeholder="Digite o nome da secretaria ou órgão"
                  @keydown.enter.prevent="adicionarSecretaria"
                />
              </div>
              <button
                class="br-button primary"
                type="button"
                @click="adicionarSecretaria"
              >
                <i class="fas fa-plus" aria-hidden="true"></i>
                Adicionar
              </button>
            </div>
            <ul
              v-if="form.secretariasEnvolvidas.length"
              class="secretarias-lista mt-2"
              aria-label="Secretarias adicionadas"
            >
              <li
                v-for="(item, index) in form.secretariasEnvolvidas"
                :key="index"
                class="secretarias-lista__item"
              >
                <span>{{ item }}</span>
                <button
                  class="secretarias-lista__remover"
                  type="button"
                  :aria-label="`Remover ${item}`"
                  @click="removerSecretaria(index)"
                >
                  <i class="fas fa-times" aria-hidden="true"></i>
                </button>
              </li>
            </ul>
          </div>

          <!-- Vigência início -->
          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.vigenciaInicio }">
              <label for="campo-vigencia-inicio">
                Vigência do Plano — início
                <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="campo-vigencia-inicio"
                type="date"
                v-model="form.vigenciaInicio"
                aria-required="true"
                :aria-describedby="erros.vigenciaInicio ? 'erro-vigencia-inicio' : undefined"
                @blur="validarVigencias"
              />
              <span
                v-if="erros.vigenciaInicio"
                id="erro-vigencia-inicio"
                class="feedback danger"
                role="alert"
              >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.vigenciaInicio }}
              </span>
            </div>
          </div>

          <!-- Vigência fim -->
          <div class="col-12 col-md-6">
            <div class="br-input" :class="{ danger: erros.vigenciaFim }">
              <label for="campo-vigencia-fim">
                Vigência do Plano — fim
                <span class="text-red-50 text-up-01"> *</span>
              </label>
              <input
                id="campo-vigencia-fim"
                type="date"
                v-model="form.vigenciaFim"
                aria-required="true"
                :aria-describedby="erros.vigenciaFim ? 'erro-vigencia-fim' : undefined"
                @blur="validarVigencias"
              />
              <span
                v-if="erros.vigenciaFim"
                id="erro-vigencia-fim"
                class="feedback danger"
                role="alert"
              >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.vigenciaFim }}
              </span>
            </div>
          </div>

        </section>
      </Card>

      <!-- Barra de ações -->
      <div class="painel-acoes">
        <button
          class="br-button secondary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          <span>Voltar</span>
        </button>
        <button
          class="br-button primary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="salvando || !podeSalvar"
          @click="salvarRascunho"
        >
          <i v-if="!salvando" class="fas fa-save" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>{{ salvando ? 'Salvando...' : 'Salvar Rascunho' }}</span>
        </button>
      </div>

    </form>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import { useAuthStore } from '@/stores/authStore'
import { obterIdentificacao, salvarIdentificacao } from '@/features/plano-acao/services/PlanoAcaoIdentificacaoService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'IdentificacaoPlanoPage' })

const router = useRouter()
const authStore = useAuthStore()
const { success: notifySuccess, error: notifyError } = useNotification()

const form = ref({
  orgaoGestor: '',
  secretariasEnvolvidas: [] as string[],
  vigenciaInicio: '',
  vigenciaFim: '',
})

const novaSecretaria = ref('')
const salvando = ref(false)

const erros = ref({
  orgaoGestor: '',
  vigenciaInicio: '',
  vigenciaFim: '',
})

const podeSalvar = computed(() => {
  const f = form.value
  return (
    f.orgaoGestor.trim().length > 0 &&
    f.vigenciaInicio.length > 0 &&
    f.vigenciaFim.length > 0 &&
    f.vigenciaFim >= f.vigenciaInicio
  )
})

const localidade = computed(() => {
  const ctx = authStore.user?.contexto
  if (!ctx) return ''
  return ctx.localidade ?? ''
})

function adicionarSecretaria(): void {
  const valor = novaSecretaria.value.trim()
  if (!valor) return
  form.value.secretariasEnvolvidas.push(valor)
  novaSecretaria.value = ''
}

function removerSecretaria(index: number): void {
  form.value.secretariasEnvolvidas.splice(index, 1)
}

function validarOrgaoGestor(): boolean {
  if (!form.value.orgaoGestor.trim()) {
    erros.value.orgaoGestor = 'Campo obrigatório não preenchido.'
    return false
  }
  erros.value.orgaoGestor = ''
  return true
}

function validarVigencias(): boolean {
  let valido = true

  if (!form.value.vigenciaInicio) {
    erros.value.vigenciaInicio = 'Campo obrigatório não preenchido.'
    valido = false
  } else {
    erros.value.vigenciaInicio = ''
  }

  if (!form.value.vigenciaFim) {
    erros.value.vigenciaFim = 'Campo obrigatório não preenchido.'
    valido = false
  } else if (
    form.value.vigenciaInicio &&
    form.value.vigenciaFim < form.value.vigenciaInicio
  ) {
    erros.value.vigenciaFim = 'A data final deve ser maior ou igual à data inicial.'
    valido = false
  } else {
    erros.value.vigenciaFim = ''
  }

  return valido
}

function formularioValido(): boolean {
  const a = validarOrgaoGestor()
  const b = validarVigencias()
  return a && b
}

async function salvarRascunho(): Promise<void> {
  if (!formularioValido()) return

  salvando.value = true
  try {
    await salvarIdentificacao({
      orgao_gestor: form.value.orgaoGestor.trim(),
      secretarias_envolvidas: form.value.secretariasEnvolvidas,
      vigencia_inicio: form.value.vigenciaInicio,
      vigencia_fim: form.value.vigenciaFim,
    })
    notifySuccess('Identificação do plano salva com sucesso!')
    router.push('/enviar-plano-acao')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(apiErr?.response?.data?.message ?? 'Ocorreu um erro ao salvar. Tente novamente.')
  } finally {
    salvando.value = false
  }
}

onMounted(async () => {
  try {
    const dados = await obterIdentificacao()
    if (dados) {
      form.value.orgaoGestor = dados.orgao_gestor
      form.value.secretariasEnvolvidas = dados.secretarias_envolvidas ?? []
      form.value.vigenciaInicio = dados.vigencia_inicio ?? ''
      form.value.vigenciaFim = dados.vigencia_fim ?? ''
    }
  } catch {
    // sem registro prévio, formulário começa vazio
  }
})
</script>

<style scoped>
/* ── Secretarias chips ── */
.secretarias-input-row {
  display: flex;
  gap: 0.75rem;
  align-items: flex-end;
}

.secretarias-input-row .br-button {
  white-space: nowrap;
  flex-shrink: 0;
}

.secretarias-lista {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.secretarias-lista__item {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: #e8f0fb;
  border: 1px solid #1351b4;
  border-radius: 20px;
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  color: #1351b4;
}

.secretarias-lista__remover {
  background: none;
  border: none;
  cursor: pointer;
  color: #1351b4;
  padding: 0;
  line-height: 1;
  font-size: 0.875rem;
}

.secretarias-lista__remover:hover {
  color: #c0345b;
}

/* ── Campo readonly ── */
.input-readonly {
  background-color: #f4f4f4 !important;
  cursor: not-allowed;
}

/* ── Feedback API ── */
.feedback-sucesso {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #168821;
  font-size: 0.9375rem;
}

.feedback-erro {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #c0345b;
  font-size: 0.9375rem;
}

/* ── Barra de ações ── */
.painel-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
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
    justify-content: flex-end;
    align-items: center;
  }

  .painel-acoes__btn {
    width: auto;
    min-width: 10rem;
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
