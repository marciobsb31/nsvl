<template>
  <div class="seletor-permissoes">
    <div v-if="carregando" class="estado-vazio">
      <div class="loading-spinner" aria-hidden="true"></div>
      <p>Carregando permissões...</p>
    </div>

    <div v-else-if="permissoes.length === 0" class="estado-vazio">
      <i class="fas fa-shield-alt fa-2x" aria-hidden="true"></i>
      <p>Nenhuma permissão disponível.</p>
    </div>

    <template v-else>
      <div v-if="!somenteLeitura" class="seletor-toolbar">
        <button class="toolbar-btn" type="button" @click="selecionarTodos" :disabled="todasSelecionadas">
          <i class="fas fa-check-double" aria-hidden="true"></i> Selecionar Todos
        </button>
        <button class="toolbar-btn" type="button" @click="limparSelecao" :disabled="selecionadas.length === 0">
          <i class="fas fa-times" aria-hidden="true"></i> Limpar Seleção
        </button>
      </div>

      <div class="table-responsive">
        <table class="br-table tabela-permissoes" role="table">
          <thead>
            <tr>
              <th scope="col" class="th-check">
                <input
                  v-if="!somenteLeitura"
                  type="checkbox"
                  :checked="todasSelecionadas"
                  :indeterminate="algumasSelecionadas && !todasSelecionadas"
                  @change="toggleTodos"
                  aria-label="Selecionar todas as permissões"
                />
                <span v-else>✓</span>
              </th>
              <th scope="col">Funcionalidade</th>
              <th scope="col">Nome da Ação</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in permissoes"
              :key="p.id"
              :class="{ 'tr-selecionado': selecionadas.includes(p.id) }"
              @click="!somenteLeitura && togglePermissao(p.id)"
              :style="!somenteLeitura ? 'cursor: pointer' : ''"
            >
              <td class="td-check">
                <input
                  type="checkbox"
                  :value="p.id"
                  :checked="selecionadas.includes(p.id)"
                  :disabled="somenteLeitura"
                  @change.stop="togglePermissao(p.id)"
                  @click.stop
                  :aria-label="`Selecionar ${p.funcionalidade} - ${p.nome_acao}`"
                />
              </td>
              <td class="td-funcionalidade">
                <i class="fas fa-cube td-icone" aria-hidden="true"></i>
                {{ p.funcionalidade }}
              </td>
              <td>{{ p.nome_acao }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { PermissaoItem } from '@/services/GerenciarPerfilService'

defineOptions({ name: 'SeletorPermissoes' })

const props = defineProps<{
  permissoes: PermissaoItem[]
  selecionadas: number[]
  carregando?: boolean
  somenteLeitura?: boolean
}>()

const emit = defineEmits<{
  (e: 'update:selecionadas', value: number[]): void
}>()

const todasSelecionadas = computed(() =>
  props.permissoes.length > 0 && props.permissoes.every((p) => props.selecionadas.includes(p.id))
)

const algumasSelecionadas = computed(() => props.selecionadas.length > 0)

function togglePermissao(id: number) {
  if (props.somenteLeitura) return
  if (props.selecionadas.includes(id)) {
    emit('update:selecionadas', props.selecionadas.filter((i) => i !== id))
  } else {
    emit('update:selecionadas', [...props.selecionadas, id])
  }
}

function toggleTodos() {
  if (todasSelecionadas.value) {
    emit('update:selecionadas', [])
  } else {
    emit('update:selecionadas', props.permissoes.map((p) => p.id))
  }
}

function selecionarTodos() {
  emit('update:selecionadas', props.permissoes.map((p) => p.id))
}

function limparSelecao() {
  emit('update:selecionadas', [])
}
</script>

<style scoped>
.seletor-permissoes {
  border: 1px solid var(--color-secondary-04, #ddd);
  border-radius: 8px;
  overflow: hidden;
}

/* ── Toolbar ── */
.seletor-toolbar {
  display: flex;
  gap: 0.5rem;
  padding: 0.625rem 0.875rem;
  background: var(--color-secondary-01, #fafafa);
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
}

.toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.3rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: inherit;
  color: var(--color-primary-default, #1351b4);
  background: #fff;
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.12s;
}

.toolbar-btn:hover:not(:disabled) {
  background: var(--color-primary-default, #1351b4);
  color: #fff;
  border-color: var(--color-primary-default, #1351b4);
}

.toolbar-btn:disabled {
  opacity: 0.4;
  cursor: default;
}

/* ── Estado vazio ── */
.estado-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2rem 1rem;
  color: var(--color-secondary-06, #888);
}

.estado-vazio p { margin: 0; font-size: 0.875rem; }

/* ── Tabela ── */
.tabela-permissoes {
  width: 100%;
  border-collapse: collapse;
}

.tabela-permissoes thead th {
  background: var(--color-secondary-02, #f0f0f0);
  font-weight: 700;
  font-size: 0.8125rem;
  padding: 0.625rem 0.875rem;
  border-bottom: 2px solid var(--color-secondary-04, #ccc);
  text-align: left;
  color: var(--color-secondary-08, #333);
}

.tabela-permissoes tbody tr {
  transition: background 0.12s;
}

.tabela-permissoes tbody tr:hover {
  background: var(--color-secondary-01, #f8f8f8);
}

.tabela-permissoes tbody td {
  padding: 0.5rem 0.875rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
  font-size: 0.8125rem;
  vertical-align: middle;
  color: var(--color-secondary-08, #333);
}

.td-funcionalidade {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-weight: 500;
}

.td-icone {
  color: var(--color-secondary-05, #aaa);
  font-size: 0.625rem;
}

.th-check, .td-check {
  width: 48px;
  text-align: center;
}

.th-check input[type='checkbox'],
.td-check input[type='checkbox'] {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary-default, #1351b4);
  cursor: pointer;
}

.td-check input[type='checkbox']:disabled {
  cursor: default;
  opacity: 0.5;
}

.tr-selecionado {
  background: #edf4fc !important;
}

/* ── Loading ── */
.loading-spinner {
  width: 28px; height: 28px;
  border: 3px solid var(--color-secondary-03, #eee);
  border-top-color: var(--color-primary-default, #1351b4);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
