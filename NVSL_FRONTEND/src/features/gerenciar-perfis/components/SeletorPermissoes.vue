<template>
  <div class="seletor-permissoes" :class="{ 'seletor-permissoes--visualizar': somenteLeitura }">
    <div v-if="carregando" class="estado-vazio">
      <div class="loading-spinner" aria-hidden="true"></div>
      <p>Carregando permissões...</p>
    </div>

    <div v-else-if="permissoes.length === 0" class="estado-vazio">
      <i class="fas fa-shield-alt fa-2x" aria-hidden="true"></i>
      <p>Nenhuma permissão disponível.</p>
    </div>

    <template v-else>
      <div class="seletor-permissoes__barra" role="region" aria-label="Resumo e ações das permissões">
        <p class="seletor-resumo" role="status" aria-live="polite">
          <template v-if="somenteLeitura">
            <strong>{{ selecionadas.length }}</strong>
            {{ selecionadas.length === 1 ? 'permissão atribuída' : 'permissões atribuídas' }}
            <span class="seletor-resumo__total">de {{ permissoes.length }} no sistema</span>
          </template>
          <template v-else>
            <strong>{{ selecionadas.length }}</strong>
            de {{ permissoes.length }}
            {{ permissoes.length === 1 ? 'permissão selecionada' : 'permissões selecionadas' }}
          </template>
        </p>
        <div v-if="!somenteLeitura" class="seletor-toolbar">
          <button class="toolbar-btn" type="button" @click="selecionarTodos" :disabled="todasSelecionadas">
            <i class="fas fa-check-double" aria-hidden="true"></i>
            Marcar todas
          </button>
          <button class="toolbar-btn" type="button" @click="limparSelecao" :disabled="selecionadas.length === 0">
            <i class="fas fa-times" aria-hidden="true"></i>
            Desmarcar todas
          </button>
        </div>
      </div>

      <div class="table-responsive tabela-permissoes__wrap">
        <table class="br-table tabela-permissoes" role="table">
          <caption class="tabela-permissoes__caption">
            Lista de permissões por funcionalidade e ação. {{ somenteLeitura ? 'Somente leitura.' : 'Clique na linha ou na caixa para alternar.' }}
          </caption>
          <thead>
            <tr>
              <th scope="col" class="th-check">
                <input
                  v-if="!somenteLeitura"
                  type="checkbox"
                  :checked="todasSelecionadas"
                  :indeterminate="algumasSelecionadas && !todasSelecionadas"
                  @change="toggleTodos"
                  aria-label="Marcar ou desmarcar todas as permissões desta lista"
                />
                <span v-else class="th-check-rotulo">Ativa</span>
              </th>
              <th scope="col" class="th-funcionalidade">Funcionalidade</th>
              <th scope="col" class="th-acao">Ação</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in permissoesPaginadas"
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
                  :aria-label="labelCheckboxPermissao(p)"
                />
              </td>
              <td class="td-funcionalidade">{{ p.funcionalidade }}</td>
              <td class="td-acao">
                <span
                  class="acao-celula"
                  :class="'acao-celula--' + varianteAcao(p.nome_acao)"
                  :title="tituloTooltipAcao(p)"
                >
                  <i :class="[iconeNomeAcao(p.nome_acao), 'acao-celula__icone']" aria-hidden="true"></i>
                  <span class="acao-celula__texto">{{ p.nome_acao }}</span>
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="permissoes.length > 0" class="seletor-permissoes__rodape">
        <PaginationControls
          v-model:currentPage="paginaAtual"
          v-model:pageSize="itensPorPagina"
          :total-items="permissoes.length"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { PermissaoItem } from '@/services/GerenciarPerfilService'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'

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
const paginaAtual = ref(1)
const itensPorPagina = ref(10)

const permissoesPaginadas = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return props.permissoes.slice(inicio, fim)
})

watch(() => props.permissoes.length, () => {
  paginaAtual.value = 1
})

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

type VarianteAcaoChip = 'leitura' | 'escrita' | 'aprovar' | 'reprovar' | 'enviar' | 'excluir' | 'default'

function normalizarChaveAcao(nomeAcao: string): string {
  return nomeAcao
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim()
    .toLowerCase()
}

/** Cor / família visual do chip conforme o tipo de permissão. */
function varianteAcao(nomeAcao: string): VarianteAcaoChip {
  const chave = normalizarChaveAcao(nomeAcao)
  if (['visualizar', 'consultar', 'listar', 'exportar', 'imprimir', 'baixar'].includes(chave)) {
    return 'leitura'
  }
  if (['criar', 'cadastrar', 'incluir', 'editar', 'atualizar', 'importar', 'gerenciar', 'configurar'].includes(chave)) {
    return 'escrita'
  }
  if (chave === 'aprovar') return 'aprovar'
  if (chave === 'reprovar') return 'reprovar'
  if (chave === 'enviar') return 'enviar'
  if (['excluir', 'deletar', 'remover'].includes(chave)) return 'excluir'
  return 'default'
}

function tituloTooltipAcao(p: PermissaoItem): string | undefined {
  const d = p.descricao?.trim()
  if (d) return d
  return undefined
}

/** Rótulo acessível do checkbox por linha. */
function labelCheckboxPermissao(p: PermissaoItem): string {
  const base = `${p.funcionalidade} — ${p.nome_acao}`
  const marcada = props.selecionadas.includes(p.id)
  if (props.somenteLeitura) {
    return marcada ? `Permissão ativa no perfil: ${base}` : `Permissão não atribuída: ${base}`
  }
  return marcada ? `Desmarcar: ${base}` : `Marcar: ${base}`
}

/** Ícone Font Awesome por rótulo da ação (campo `acao` / nome_acao da API). */
function iconeNomeAcao(nomeAcao: string): string {
  const chave = normalizarChaveAcao(nomeAcao)

  const map: Record<string, string> = {
    visualizar: 'fas fa-eye',
    consultar: 'fas fa-search',
    listar: 'fas fa-list',
    criar: 'fas fa-plus-circle',
    cadastrar: 'fas fa-plus-circle',
    incluir: 'fas fa-plus-circle',
    editar: 'fas fa-pen',
    atualizar: 'fas fa-sync-alt',
    excluir: 'fas fa-trash-alt',
    deletar: 'fas fa-trash-alt',
    remover: 'fas fa-minus-circle',
    aprovar: 'fas fa-check-circle',
    reprovar: 'fas fa-times-circle',
    exportar: 'fas fa-file-download',
    importar: 'fas fa-file-upload',
    enviar: 'fas fa-paper-plane',
    gerenciar: 'fas fa-sliders-h',
    configurar: 'fas fa-cog',
    historico: 'fas fa-history',
    imprimir: 'fas fa-print',
    baixar: 'fas fa-download',
  }

  return map[chave] ?? 'fas fa-key'
}
</script>

<style scoped>
.seletor-permissoes {
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  border-radius: 8px;
  overflow: hidden;
  background: var(--background, #fff);
}

/* ── Barra: resumo + ações (uma linha no desktop) ── */
.seletor-permissoes__barra {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  padding: 0.875rem 1rem;
  background: var(--color-secondary-01, #f8f8f8);
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
}

.seletor-resumo {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.45;
  color: var(--color-secondary-08, #333);
}

.seletor-resumo strong {
  font-weight: 700;
  color: var(--color-primary-darken-02, #0c326f);
}

.seletor-resumo__total {
  display: inline-block;
  margin-left: 0.25rem;
  color: var(--color-secondary-06, #888);
  font-weight: 400;
}

/* ── Toolbar (ações secundárias — contorno) ── */
.seletor-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  flex-shrink: 0;
}

.toolbar-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  font-family: inherit;
  line-height: 1.2;
  color: var(--color-secondary-09, #333);
  background: var(--background, #fff);
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 6px;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.toolbar-btn:hover:not(:disabled) {
  border-color: var(--color-primary-default, #1351b4);
  color: var(--color-primary-default, #1351b4);
  background: var(--color-primary-pastel-01, #e8f0ff);
}

.toolbar-btn:focus-visible {
  outline: 2px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
}

.toolbar-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
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
.tabela-permissoes__wrap {
  position: relative;
  background: var(--background, #fff);
}

.tabela-permissoes {
  width: 100%;
  border-collapse: collapse;
}

.tabela-permissoes__caption {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.tabela-permissoes thead th {
  background: var(--background, #fff);
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  padding: 0.625rem 1rem;
  border-bottom: 1px solid var(--color-secondary-04, #ccc);
  text-align: left;
  color: var(--color-secondary-07, #555);
}

.th-funcionalidade {
  width: 42%;
}

.th-acao {
  min-width: 12rem;
}

.th-check-rotulo {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-secondary-07, #555);
}

.tabela-permissoes tbody tr {
  transition: background 0.12s;
}

.tabela-permissoes tbody tr:hover {
  background: var(--color-secondary-01, #f8f8f8);
}

.tabela-permissoes tbody td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
  font-size: 0.9375rem;
  vertical-align: middle;
  color: var(--color-secondary-09, #333);
}

.td-funcionalidade {
  font-weight: 500;
  line-height: 1.4;
  color: var(--color-secondary-09, #333);
}

.td-acao {
  vertical-align: middle;
}

/* Ação: texto legível + ícone discreto (sem caixa / chip) */
.acao-celula {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  max-width: 100%;
  line-height: 1.35;
  cursor: inherit;
}

.acao-celula__icone {
  flex-shrink: 0;
  width: 1.125rem;
  text-align: center;
  font-size: 0.8125rem;
  color: var(--acao-icone, var(--color-secondary-06, #888));
  opacity: 0.95;
}

.acao-celula__texto {
  min-width: 0;
  font-weight: 600;
  font-size: 0.875rem;
  color: var(--color-secondary-09, #333);
}

.acao-celula--leitura {
  --acao-icone: #1351b4;
}

.acao-celula--escrita {
  --acao-icone: #c26100;
}

.acao-celula--aprovar {
  --acao-icone: #168821;
}

.acao-celula--reprovar {
  --acao-icone: #e52207;
}

.acao-celula--enviar {
  --acao-icone: #07689f;
}

.acao-celula--excluir {
  --acao-icone: #c62828;
}

.acao-celula--default {
  --acao-icone: #555;
}

/* Modo visualizar: ícones só em preto/cinza (sem cores semânticas) */
.seletor-permissoes--visualizar .acao-celula__icone {
  color: var(--color-secondary-09, #333) !important;
  filter: grayscale(1);
  opacity: 1;
}

.seletor-permissoes--visualizar .estado-vazio .fa-shield-alt {
  color: var(--color-secondary-08, #333);
  filter: grayscale(1);
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
  background: var(--color-primary-pastel-01, #e8f0ff) !important;
  box-shadow: inset 3px 0 0 var(--color-primary-default, #1351b4);
}

.seletor-permissoes__rodape {
  padding: 0.75rem 1rem 1rem;
  border-top: 1px solid var(--color-secondary-03, #e8e8e8);
  background: var(--color-secondary-01, #f8f8f8);
}

.seletor-permissoes__rodape :deep(.pagination-controls) {
  padding-top: 0;
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
