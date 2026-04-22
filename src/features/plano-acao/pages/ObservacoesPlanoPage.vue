<template>
  <DefaultLayout>
    <HeaderPage
      title="Observações complementares"
      subtitle="Informações adicionais do plano."
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
        title="Observações complementares"
        subtitle="Campo opcional — máximo de 8.000 caracteres."
        custom-class="mb-4"
      >
        <div class="br-input">
          <label for="campo-observacoes">Observações</label>
          <textarea
            id="campo-observacoes"
            v-model="observacoes"
            rows="8"
            maxlength="8000"
            placeholder="(opcional)"
          ></textarea>
          <span class="field-hint mt-1">
            {{ observacoes.length }}/8000 caracteres
          </span>
        </div>
      </Card>

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
          :disabled="salvando"
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import { obterObservacoes, salvarObservacoes } from '@/features/plano-acao/services/PlanoAcaoObservacaoService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'ObservacoesPlanoPage' })

const router = useRouter()
const { success: notifySuccess, error: notifyError } = useNotification()

const observacoes = ref('')
const salvando = ref(false)

onMounted(async () => {
  try {
    const dados = await obterObservacoes()
    if (dados) {
      observacoes.value = dados.observacoes ?? ''
    }
  } catch {
    // sem dados — campo vazio
  }
})

async function salvarRascunho(): Promise<void> {
  salvando.value = true
  try {
    await salvarObservacoes({ observacoes: observacoes.value })
    notifySuccess('Rascunho salvo com sucesso!')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(apiErr?.response?.data?.message ?? 'Erro ao salvar. Tente novamente.')
  } finally {
    salvando.value = false
  }
}
</script>

<style scoped>
.br-input textarea {
  width: 100%;
  box-sizing: border-box;
  resize: vertical;
  min-height: 10rem;
}

.field-hint {
  display: block;
  font-size: 0.8rem;
  color: var(--secondary-text-color, #666);
}

/* ── Painel de ações ─────────────────────────────────────────────────────── */
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
