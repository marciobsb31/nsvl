<template>
  <DefaultLayout>
    <HeaderPage title="Perfis de acesso do sistema" :subtitle="`Visualize os perfis pré-definidos do NVSL. Na versão MVP, os perfis são fixos e não podem ser criados, editados ou desativados.`" customClass="mb-3" />
    <Contexto />

    <Card custom-class="mb-4">
      <FiltrosPerfis
          @pesquisar="aplicarFiltros"
          @limpar="limparEpesquisar"/>
    </Card>
    <Card custom-class="gerenciar-perfis__card mb-4">
      <div v-if="carregando" class="br-loading p-4" role="status" aria-live="polite">
        <div class="loading-spinner" aria-hidden="true"></div>
        <p class="mt-2">Carregando perfis...</p>
      </div>
      <div v-else>
        <Table :columns="columns" :data="perfisOrdenados" :show-actions="true" :empty-icon="'users-cog'"
          emptyMessage="Nenhum perfil encontrado." @sort="sorteable">
          <template #status="{ row }">
            <span class="br-tag" :class="classeSituacao(row.status)">
              {{ labelSituacao(row.status) }}
            </span>
          </template>
          <template #actions="{ row }">
            <div class="tabela-perfis__acoes">
            <button class="br-button secondary small btn-acao btn-acao--visualizar" type="button"
              @click="abrirVisualizar(row)" title="Visualizar perfil">
              Visualizar
            </button>
            <button class="br-button secondary small btn-acao btn-acao--historico" type="button"
              @click="abrirHistorico(row)" title="Histórico do perfil">
              Histórico
            </button>
            </div> 
          </template>
        </Table>
      </div>
      <PaginationControls v-if="perfisOrdenados.length > 0" v-model:currentPage="paginaAtual"
        v-model:pageSize="itensPorPagina" :total-items="perfisOrdenados.length" />
    </Card>

    <Transition name="painel-fade">
      <div v-if="painelAberto" class="painel-overlay" aria-hidden="true" @click="fecharPainel"></div>
    </Transition>

    <Transition name="painel-slide">
      <aside v-if="painelAberto" class="painel-lateral" :aria-label="ariaPainel">
        <PainelFormularioPerfil
          v-if="modoPainel === 'visualizar'" modo="visualizar"
          :perfil="perfilSelecionado" @voltar="fecharPainel" @sucesso="fecharPainel" @dirty="() => {}" />
        <PainelHistoricoPerfil v-else-if="modoPainel === 'historico' && perfilSelecionado" :perfil="perfilSelecionado"
          @voltar="fecharPainel" />
      </aside>
    </Transition>

  </DefaultLayout>
</template>

<script setup lang="ts">
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Contexto from '@/core/components/Contexto/Contexto.vue'
import Table from '@/core/components/Table/Table.vue'
import Card from '@/core/components/Card/Card.vue'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'
import PainelFormularioPerfil from '../components/PainelFormularioPerfil.vue'
import PainelHistoricoPerfil from '../components/PainelHistoricoPerfil.vue'
import FiltrosPerfis from '../components/FiltrosPerfis.vue'
import { computed, onMounted, ref } from 'vue'
import { listarPerfisGerenciar, type PerfilGerenciar } from '@/services/GerenciarPerfilService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'GerenciarPerfisPage' })
const { error } = useNotification()

const columns = [{
  key: 'nome',
  label: 'Nome do perfil',
  width: '40%',
  sort: true
}, {
  key: 'status',
  label: 'Situação',
  width: '30%',
  sort: true
}]

const sorts = ref<{ column: string | null; asc: boolean }>({ column: null, asc: true })

const sorteable = (payload: { column: string; asc: boolean }) => {
  sorts.value = payload
}

const perfis = ref<PerfilGerenciar[]>([])
const filtrosAtivos = ref<any>({})
const carregando = ref(false)
const paginaAtual = ref(1)
const itensPorPagina = ref(10)

type ModoPainel = 'visualizar' | 'historico'
const modoPainel = ref<ModoPainel>('visualizar')
const perfilSelecionado = ref<PerfilGerenciar | null>(null)
const painelAberto = ref(false)

const ariaPainel = computed(() => {
  const map: Record<ModoPainel, string> = {
    visualizar: 'Visualizar perfil',
    historico: 'Histórico do perfil',
  }
  return map[modoPainel.value]
})

const perfisOrdenados = computed(() => {
  const lista = [...perfis.value]
  if (!sorts.value.column) {
    lista.sort((a, b) => (a.nome ?? '').localeCompare(b.nome ?? '', 'pt-BR'))
    return lista
  }
  const col = sorts.value.column
  const asc = sorts.value.asc
  lista.sort((a, b) => {
    let cmp: number
    if (col === 'nome') {
      cmp = (a.nome ?? '').toLowerCase().localeCompare((b.nome ?? '').toLowerCase(), 'pt-BR')
    } else if (col === 'status') {
      cmp = labelSituacao(a.status).localeCompare(labelSituacao(b.status), 'pt-BR')
    } else {
      return 0
    }
    return asc ? cmp : -cmp
  })
  return lista
})

onMounted(() => carregarPerfis())

async function carregarPerfis() {
  carregando.value = true
  try {
    const listaPerfis = await listarPerfisGerenciar(filtrosAtivos.value)
    perfis.value = listaPerfis
    paginaAtual.value = 1
  } catch {
    perfis.value = []
    error('Não foi possível carregar os perfis.')
  } finally {
    carregando.value = false
  }
}

function abrirVisualizar(p: PerfilGerenciar) {
  perfilSelecionado.value = p
  modoPainel.value = 'visualizar'
  painelAberto.value = true
}

function abrirHistorico(p: PerfilGerenciar) {
  perfilSelecionado.value = p
  modoPainel.value = 'historico'
  painelAberto.value = true
}

function fecharPainel() {
  painelAberto.value = false
  perfilSelecionado.value = null
}

function labelSituacao(status: string): string {
  return status === 'ativo' ? 'Ativo' : 'Inativo'
}

function classeSituacao(status: string): string {
  return status === 'ativo' ? 'success' : 'danger'
}

function aplicarFiltros(filtros: any) {
  filtrosAtivos.value = filtros
  sorts.value = { column: null, asc: true }
  carregarPerfis()
}

function limparEpesquisar() {
  filtrosAtivos.value = {}
  sorts.value = { column: null, asc: true }
  carregarPerfis()
}
</script>

<style scoped>

.tabela-perfis__acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  justify-content: flex-start;
  align-items: center;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto;
  border: 3px solid var(--color-secondary-03, #eee);
  border-top-color: var(--color-primary-default, #1351b4);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ── Overlay + Painel ── */
.painel-overlay {
  position: fixed;
  inset: 0;
  background: rgba(12, 50, 111, 0.25);
  z-index: 999;
}

.painel-lateral {
  position: fixed;
  top: 0;
  right: 0;
  width: 100%;
  max-width: 100%;
  height: 100vh;
  background: var(--background, #fff);
  border-left: 1px solid var(--color-secondary-03, #e8e8e8);
  box-shadow: -8px 0 32px rgba(12, 50, 111, 0.08);
  z-index: 1000;
  overflow-y: auto;
}

@media (min-width: 576px) { .painel-lateral { width: 75%; } }
@media (min-width: 1200px) { .painel-lateral { width: 65%; } }

/* ── Modal de confirmação ── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(12, 50, 111, 0.35);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal-confirmacao {
  background: var(--background, #fff);
  border: 1px solid var(--color-secondary-03, #e8e8e8);
  border-radius: 8px;
  padding: 1.5rem 1.5rem 1.25rem;
  max-width: 26rem;
  width: 100%;
  box-shadow: 0 8px 24px rgba(12, 50, 111, 0.12);
}

.modal-confirmacao__header {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  margin-bottom: 0.875rem;
}

.modal-confirmacao__icone {
  font-size: 1.25rem;
  color: var(--color-warning-darken-01, #c26100);
  margin-top: 0.1rem;
}

.modal-confirmacao__titulo {
  font-size: 1.0625rem;
  font-weight: 700;
  line-height: 1.35;
  margin: 0;
  color: var(--color-primary-darken-02, #0c326f);
}

.modal-confirmacao__texto {
  font-size: 0.875rem;
  color: var(--color-secondary-07, #555);
  margin: 0 0 1.25rem;
  line-height: 1.55;
}

.modal-confirmacao__acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
}

/* ── Transitions ── */
.painel-fade-enter-active, .painel-fade-leave-active { transition: opacity 0.25s ease; }
.painel-fade-enter-from, .painel-fade-leave-to { opacity: 0; }
.painel-slide-enter-active, .painel-slide-leave-active { transition: transform 0.25s ease; }
.painel-slide-enter-from, .painel-slide-leave-to { transform: translateX(100%); }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
</style>