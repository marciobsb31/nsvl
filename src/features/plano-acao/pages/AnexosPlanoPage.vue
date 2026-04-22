<template>
  <DefaultLayout>
    <HeaderPage
      title="Anexos"
      subtitle="Documentos de apoio ao plano."
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

    <Card
      title="Anexos"
      subtitle="Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, CSV, PNG, JPG — tamanho máximo: 10 MB por arquivo."
      custom-class="mb-4"
    >
      <!-- Upload -->
      <div class="upload-area">
        <label class="br-label" for="campo-anexo">Inserir anexos</label>
        <div class="upload-input-row mt-1">
          <input
            id="campo-anexo"
            ref="inputRef"
            type="file"
            multiple
            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.png,.jpg,.jpeg"
            class="upload-input-native"
            aria-label="Selecionar arquivos para anexar"
            @change="onArquivosSelecionados"
          />
        </div>
        <p class="upload-hint mt-1">
          <i class="fas fa-info-circle" aria-hidden="true"></i>
          É possível anexar documentos de apoio ao plano, como PDF, planilhas e relatórios.
        </p>
      </div>

      <!-- Erros de validação -->
      <div
        v-if="erroUpload"
        class="feedback danger mt-2"
        role="alert"
      >
        <i class="fas fa-times-circle" aria-hidden="true"></i>
        {{ erroUpload }}
      </div>

      <!-- Upload em progresso -->
      <div v-if="enviando" class="upload-progresso mt-2">
        <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
        Enviando arquivo...
      </div>

      <!-- Lista de anexos -->
      <div v-if="anexos.length" class="anexos-lista mt-3">
        <span class="br-divider mb-3" aria-hidden="true"></span>
        <p class="anexos-lista__titulo">
          <i class="fas fa-paperclip" aria-hidden="true"></i>
          {{ anexos.length }} {{ anexos.length === 1 ? 'arquivo anexado' : 'arquivos anexados' }}
        </p>
        <ul class="anexos-itens" aria-label="Arquivos anexados">
          <li
            v-for="anexo in anexos"
            :key="anexo.id"
            class="anexo-item"
          >
            <div class="anexo-item__info">
              <i :class="['fas', iconeArquivo(anexo.tipo_mime)]" aria-hidden="true"></i>
              <div>
                <span class="anexo-item__nome">{{ anexo.nome_original }}</span>
                <span class="anexo-item__tamanho">{{ formatarTamanho(anexo.tamanho_bytes) }}</span>
              </div>
            </div>
            <button
              class="br-button secondary small"
              type="button"
              :aria-label="`Remover ${anexo.nome_original}`"
              @click="remover(anexo.id)"
            >
              <i class="fas fa-trash-alt" aria-hidden="true"></i>
              Remover
            </button>
          </li>
        </ul>
      </div>

      <!-- Vazio -->
      <div v-else-if="carregado" class="anexos-vazio mt-3">
        <i class="fas fa-folder-open fa-2x mb-2" aria-hidden="true"></i>
        <p>Nenhum arquivo anexado.</p>
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
        @click="router.push('/enviar-plano-acao')"
      >
        <i class="fas fa-check" aria-hidden="true"></i>
        <span>Concluir</span>
      </button>
    </div>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import {
  listarAnexos,
  uplodarAnexo,
  removerAnexo,
  type PlanoAcaoAnexo,
} from '@/features/plano-acao/services/PlanoAcaoAnexoService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'AnexosPlanoPage' })

const router = useRouter()
const { success: notifySuccess, error: notifyError } = useNotification()

const anexos = ref<PlanoAcaoAnexo[]>([])
const enviando = ref(false)
const erroUpload = ref('')
const carregado = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)

onMounted(async () => {
  try {
    anexos.value = await listarAnexos()
  } catch {
    // erro silencioso
  } finally {
    carregado.value = true
  }
})

async function onArquivosSelecionados(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const arquivos = Array.from(input.files ?? [])
  if (!arquivos.length) return

  erroUpload.value = ''

  for (const arquivo of arquivos) {
    enviando.value = true
    try {
      const novo = await uplodarAnexo(arquivo)
      anexos.value.unshift(novo)
    } catch (err: unknown) {
      const apiErr = err as { response?: { data?: { message?: string } } }
      erroUpload.value = apiErr?.response?.data?.message ?? 'Erro ao enviar o arquivo. Verifique o tipo e tamanho.'
      notifyError(erroUpload.value)
    } finally {
      enviando.value = false
    }
  }

  // limpar seleção para permitir re-upload do mesmo arquivo
  if (inputRef.value) inputRef.value.value = ''
}

async function remover(id: number): Promise<void> {
  try {
    await removerAnexo(id)
    anexos.value = anexos.value.filter(a => a.id !== id)
    notifySuccess('Arquivo removido com sucesso.')
  } catch {
    notifyError('Erro ao remover o arquivo. Tente novamente.')
  }
}

function formatarTamanho(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function iconeArquivo(mime: string): string {
  if (mime === 'application/pdf') return 'fa-file-pdf'
  if (mime.includes('word')) return 'fa-file-word'
  if (mime.includes('excel') || mime.includes('spreadsheet') || mime === 'text/csv') return 'fa-file-excel'
  if (mime.startsWith('image/')) return 'fa-file-image'
  return 'fa-file-alt'
}
</script>

<style scoped>
/* ── Upload ─────────────────────────────────────────────────────────────── */
.upload-input-native {
  display: block;
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  font-family: inherit;
  border: 1px solid var(--color-secondary-05, #bbb);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  box-sizing: border-box;
}

.upload-hint {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--color-primary-default, #1351b4);
}

.upload-progresso {
  font-size: 0.875rem;
  color: var(--secondary-text-color, #666);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* ── Lista de anexos ─────────────────────────────────────────────────────── */
.anexos-lista__titulo {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--primary-text-color, #1c1c1e);
  margin: 0 0 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.anexos-itens {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.anexo-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: #f4f7fc;
  border: 1px solid #dde4ee;
  border-radius: 6px;
  flex-wrap: wrap;
}

.anexo-item__info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0;
}

.anexo-item__info .fas {
  font-size: 1.35rem;
  color: #1351b4;
  flex-shrink: 0;
}

.anexo-item__nome {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--primary-text-color, #1c1c1e);
  word-break: break-all;
}

.anexo-item__tamanho {
  display: block;
  font-size: 0.8rem;
  color: var(--secondary-text-color, #666);
}

/* ── Estado vazio ────────────────────────────────────────────────────────── */
.anexos-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  color: #888;
  text-align: center;
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
