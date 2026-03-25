<template>
  <DefaultLayout>
    <section class="gerenciar-perfis" :class="{ 'painel-aberto': painelAberto }">
      <header class="pagina-hero">
        <div class="pagina-hero__topo">
          <h1 class="pagina-hero__title">Gerenciar Perfis</h1>
          <button class="br-button primary pagina-hero__btn" type="button" @click="abrirCadastrar" aria-label="Novo perfil">
            <i class="fas fa-plus-circle pagina-hero__btn-icone" aria-hidden="true"></i>
            <span>Novo Perfil</span>
          </button>
        </div>
        <p class="pagina-hero__lead">
          Consulte, cadastre e edite os perfis de acesso do sistema NVSL.
        </p>
      </header>

      <Card custom-class="gerenciar-perfis__card mb-4">
        <div v-if="carregando" class="estado-vazio" role="status" aria-live="polite">
          <div class="loading-spinner" aria-hidden="true"></div>
          <p>Carregando perfis...</p>
        </div>

        <div v-else-if="perfisOrdenados.length === 0" class="estado-vazio">
          <i class="fas fa-users-cog fa-2x" aria-hidden="true"></i>
          <p>Nenhum perfil encontrado.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="br-table tabela-perfis" role="table">
            <thead>
              <tr>
                <th scope="col" :aria-sort="obterAriaSort('nome')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('nome')">
                    Nome do Perfil <span class="th-sort-icon">{{ obterIconeSort('nome') }}</span>
                  </button>
                </th>
                <th scope="col" :aria-sort="obterAriaSort('esfera')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('esfera')">
                    Tipo de Perfil <span class="th-sort-icon">{{ obterIconeSort('esfera') }}</span>
                  </button>
                </th>
                <th scope="col" :aria-sort="obterAriaSort('status')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('status')">
                    <i class="fas fa-certificate th-sort-btn__icone" aria-hidden="true"></i>
                    Vigência <span class="th-sort-icon">{{ obterIconeSort('status') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-acoes">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="p in perfisPaginados"
                :key="p.id"
                :class="{ 'tr-ativo': perfilSelecionado?.id === p.id }"
              >
                <td class="td-nome">{{ p.nome }}</td>
                <td>
                  <span class="tag-hierarquia" :class="'tag-hierarquia--' + p.esfera">
                    <i :class="iconeHierarquia(p.esfera)" aria-hidden="true"></i>
                    {{ labelTipoPerfil(p.esfera) }}
                  </span>
                </td>
                <td>
                  <span class="tag-situacao" :class="classeSituacao(p.status)">
                    <i :class="iconeSituacao(p.status)" aria-hidden="true"></i>
                    {{ labelSituacao(p.status) }}
                  </span>
                </td>
                <td class="td-acoes">
                  <button class="btn-acao btn-acao--visualizar" type="button" @click="abrirVisualizar(p)" title="Visualizar perfil">
                    <i class="fas fa-eye btn-acao__icone" aria-hidden="true"></i>
                    <span>Visualizar</span>
                  </button>
                  <button
                    v-if="podeEditar(p)"
                    class="btn-acao btn-acao--editar"
                    type="button"
                    @click="abrirEditar(p)"
                    title="Editar perfil"
                  >
                    <i class="fas fa-pen btn-acao__icone" aria-hidden="true"></i>
                    <span>Editar</span>
                  </button>
                  <button class="btn-acao btn-acao--historico" type="button" @click="abrirHistorico(p)" title="Histórico do perfil">
                    <i class="fas fa-history btn-acao__icone" aria-hidden="true"></i>
                    <span>Histórico</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <PaginationControls
          v-if="perfisOrdenados.length > 0"
          v-model:currentPage="paginaAtual"
          v-model:pageSize="itensPorPagina"
          :total-items="perfisOrdenados.length"
        />
      </Card>

      <Transition name="painel-fade">
        <div v-if="painelAberto" class="painel-overlay" aria-hidden="true" @click="tentarFecharPainel"></div>
      </Transition>

      <Transition name="painel-slide">
        <aside v-if="painelAberto" class="painel-lateral" :aria-label="ariaPainel">
          <PainelFormularioPerfil
            v-if="modoPainel === 'cadastrar' || modoPainel === 'editar' || modoPainel === 'visualizar'"
            :modo="modoPainel"
            :perfil="perfilSelecionado"
            @voltar="tentarFecharPainel"
            @sucesso="onSucessoSalvar"
            @dirty="onDirtyChange"
          />
          <PainelHistoricoPerfil
            v-else-if="modoPainel === 'historico' && perfilSelecionado"
            :perfil="perfilSelecionado"
            @voltar="fecharPainel"
          />
        </aside>
      </Transition>

      <!-- Modal de confirmação: sair sem salvar -->
      <Teleport to="body">
        <Transition name="modal-fade">
          <div v-if="confirmarSairVisivel" class="modal-overlay" @click.self="cancelarSair">
            <div class="modal-confirmacao" role="dialog" aria-modal="true" aria-labelledby="modal-sair-titulo">
              <div class="modal-confirmacao__header">
                <i class="fas fa-exclamation-triangle modal-confirmacao__icone" aria-hidden="true"></i>
                <h3 id="modal-sair-titulo" class="modal-confirmacao__titulo">Deseja sair sem salvar?</h3>
              </div>
              <p class="modal-confirmacao__texto">
                Existem alterações não salvas. Se você sair agora, todas as mudanças serão perdidas.
              </p>
              <div class="modal-confirmacao__acoes">
                <button class="br-button secondary" type="button" @click="cancelarSair">
                  Continuar editando
                </button>
                <button class="br-button danger" type="button" @click="confirmarSair">
                  Sair sem salvar
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Card from '@/core/components/Card/Card.vue'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'
import PainelFormularioPerfil from '../components/PainelFormularioPerfil.vue'
import PainelHistoricoPerfil from '../components/PainelHistoricoPerfil.vue'
import { listarPerfisGerenciar, obterHierarquia, type PerfilGerenciar } from '@/services/GerenciarPerfilService'
import { useNotification } from '@/core/composables/useNotification'
import { useAuthStore } from '@/stores/authStore'

defineOptions({ name: 'GerenciarPerfisPage' })

const { error } = useNotification()
const authStore = useAuthStore()

const perfis = ref<PerfilGerenciar[]>([])
const carregando = ref(false)

const sortColuna = ref<string | null>(null)
const sortAsc = ref(true)
const paginaAtual = ref(1)
const itensPorPagina = ref(10)

type ModoPainel = 'cadastrar' | 'editar' | 'visualizar' | 'historico'
const modoPainel = ref<ModoPainel>('cadastrar')
const perfilSelecionado = ref<PerfilGerenciar | null>(null)
const painelAberto = ref(false)
const formularioDirty = ref(false)
const confirmarSairVisivel = ref(false)

const esferasPermitidas = ref<string[]>(['federal', 'estadual', 'municipal'])

const HIERARQUIA_ORDEM: Record<string, number> = { federal: 0, estadual: 1, municipal: 2 }

const ariaPainel = computed(() => {
  const map: Record<ModoPainel, string> = {
    cadastrar: 'Cadastrar novo perfil',
    editar: 'Editar perfil',
    visualizar: 'Visualizar perfil',
    historico: 'Histórico do perfil',
  }
  return map[modoPainel.value]
})

const perfisOrdenados = computed(() => {
  const lista = [...perfis.value]
  if (!sortColuna.value) {
    lista.sort((a, b) => {
      const ha = HIERARQUIA_ORDEM[a.esfera] ?? 99
      const hb = HIERARQUIA_ORDEM[b.esfera] ?? 99
      if (ha !== hb) return ha - hb
      return (a.nome ?? '').localeCompare(b.nome ?? '', 'pt-BR')
    })
    return lista
  }
  const col = sortColuna.value
  const asc = sortAsc.value
  lista.sort((a, b) => {
    let cmp: number
    if (col === 'nome') {
      cmp = (a.nome ?? '').toLowerCase().localeCompare((b.nome ?? '').toLowerCase(), 'pt-BR')
    } else if (col === 'esfera') {
      const ha = HIERARQUIA_ORDEM[a.esfera] ?? 99
      const hb = HIERARQUIA_ORDEM[b.esfera] ?? 99
      cmp = ha - hb
      if (cmp === 0) cmp = (a.nome ?? '').localeCompare(b.nome ?? '', 'pt-BR')
    } else if (col === 'status') {
      cmp = labelSituacao(a.status).localeCompare(labelSituacao(b.status), 'pt-BR')
    } else {
      return 0
    }
    return asc ? cmp : -cmp
  })
  return lista
})

const perfisPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return perfisOrdenados.value.slice(inicio, fim)
})

function podeEditar(perfil: PerfilGerenciar): boolean {
  const esferaUsuario = authStore.user?.esfera_atuacao ?? 'federal'
  if (esferaUsuario === 'municipal') {
    return false
  }
  return esferasPermitidas.value.includes(perfil.esfera)
}

function ordenarPor(coluna: string) {
  if (sortColuna.value === coluna) {
    sortAsc.value = !sortAsc.value
  } else {
    sortColuna.value = coluna
    sortAsc.value = true
  }
}

function obterAriaSort(coluna: string): 'none' | 'ascending' | 'descending' {
  if (sortColuna.value !== coluna) return 'none'
  return sortAsc.value ? 'ascending' : 'descending'
}

function obterIconeSort(coluna: string): string {
  if (sortColuna.value !== coluna) return '↕'
  return sortAsc.value ? '↑' : '↓'
}

async function carregarPerfis() {
  carregando.value = true
  try {
    const [listaPerfis, hierarquia] = await Promise.all([
      listarPerfisGerenciar(),
      obterHierarquia(),
    ])
    perfis.value = listaPerfis
    paginaAtual.value = 1
    esferasPermitidas.value = hierarquia.esferas_permitidas
  } catch {
    perfis.value = []
    error('Não foi possível carregar os perfis.')
  } finally {
    carregando.value = false
  }
}

function abrirCadastrar() {
  perfilSelecionado.value = null
  modoPainel.value = 'cadastrar'
  formularioDirty.value = false
  painelAberto.value = true
}

function abrirVisualizar(p: PerfilGerenciar) {
  perfilSelecionado.value = p
  modoPainel.value = 'visualizar'
  formularioDirty.value = false
  painelAberto.value = true
}

function abrirEditar(p: PerfilGerenciar) {
  perfilSelecionado.value = p
  modoPainel.value = 'editar'
  formularioDirty.value = false
  painelAberto.value = true
}

function abrirHistorico(p: PerfilGerenciar) {
  perfilSelecionado.value = p
  modoPainel.value = 'historico'
  formularioDirty.value = false
  painelAberto.value = true
}

function tentarFecharPainel() {
  if (formularioDirty.value && (modoPainel.value === 'cadastrar' || modoPainel.value === 'editar')) {
    confirmarSairVisivel.value = true
    return
  }
  fecharPainel()
}

function confirmarSair() {
  confirmarSairVisivel.value = false
  fecharPainel()
}

function cancelarSair() {
  confirmarSairVisivel.value = false
}

function fecharPainel() {
  painelAberto.value = false
  perfilSelecionado.value = null
  formularioDirty.value = false
}

function onSucessoSalvar() {
  formularioDirty.value = false
  fecharPainel()
  carregarPerfis()
}

function onDirtyChange(dirty: boolean) {
  formularioDirty.value = dirty
}

function labelTipoPerfil(esfera: string): string {
  const map: Record<string, string> = { federal: 'Nacional', estadual: 'Estadual', municipal: 'Municipal' }
  return map[esfera] ?? esfera
}

function iconeHierarquia(esfera: string): string {
  const map: Record<string, string> = {
    federal: 'fas fa-globe-americas',
    estadual: 'fas fa-map-marked-alt',
    municipal: 'fas fa-map-pin',
  }
  return map[esfera] ?? 'fas fa-circle'
}

function labelSituacao(status: string): string {
  return status === 'ativo' ? 'Vigente' : 'Não vigente'
}

function iconeSituacao(status: string): string {
  return status === 'ativo' ? 'fas fa-check-circle' : 'fas fa-ban'
}

function classeSituacao(status: string): string {
  return status === 'ativo' ? 'tag--vigente' : 'tag--nao-vigente'
}

onMounted(() => carregarPerfis())
</script>

<style scoped>
.gerenciar-perfis {
  width: 100%;
  max-width: min(100%, 80rem);
  margin: 0 auto;
  padding: 1.25rem 1rem 2rem;
  position: relative;
  display: flex;
  flex-direction: column;
}

@media (min-width: 576px) {
  .gerenciar-perfis {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

@media (min-width: 1200px) {
  .gerenciar-perfis {
    padding-left: 2rem;
    padding-right: 2rem;
  }
}

/* Card: menos “caixa” pesada (DS gov.br — superfície neutra) */
.gerenciar-perfis :deep(.gerenciar-perfis__card) {
  border: 1px solid var(--color-secondary-03, #e8e8e8) !important;
  box-shadow: none !important;
  border-radius: 8px;
  overflow: hidden;
  background: var(--background, #fff);
}

/* ── Hero (padrão conteúdo gov.br) ── */
.pagina-hero {
  margin-bottom: 1.75rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
}

.pagina-hero__topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagina-hero__title {
  margin: 0 0 0.5rem;
  font-size: 1.375rem;
  font-weight: 700;
  line-height: 1.3;
  color: var(--color-primary-darken-02, #0c326f);
  letter-spacing: -0.015em;
}

@media (min-width: 768px) {
  .pagina-hero__title {
    font-size: 1.5rem;
  }
}

.pagina-hero__lead {
  margin: 0;
  max-width: 48rem;
  font-size: 0.9375rem;
  line-height: 1.6;
  color: var(--color-secondary-07, #555);
}

.pagina-hero__btn {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
}

.pagina-hero__btn-icone {
  font-size: 1rem;
}

/* ── Estado vazio / carregando ── */
.estado-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 3rem 1.5rem;
  color: var(--color-secondary-06, #888);
}

.estado-vazio p {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* ── Tabela (padrão limpo — DS) ── */
.table-responsive {
  margin: 0 -0.25rem;
}

@media (min-width: 768px) {
  .table-responsive {
    margin: 0;
  }
}

.tabela-perfis {
  width: 100%;
  border-collapse: collapse;
}

.tabela-perfis thead th {
  background: var(--color-secondary-01, #f8f8f8);
  font-weight: 600;
  font-size: 0.8125rem;
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-secondary-04, #ccc);
  text-align: left;
  color: var(--color-secondary-09, #333);
  vertical-align: bottom;
}

.tabela-perfis tbody tr {
  transition: background-color 0.15s ease;
}

.tabela-perfis tbody tr:hover {
  background: var(--color-secondary-01, #f8f8f8);
}

.tabela-perfis tbody td {
  padding: 0.875rem 1rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
  font-size: 0.875rem;
  vertical-align: middle;
  color: var(--color-secondary-09, #333);
}

.td-nome {
  font-weight: 600;
  color: var(--color-secondary-09, #333);
}

.th-acoes {
  text-align: right;
  width: 1%;
  white-space: nowrap;
}

.td-acoes {
  white-space: nowrap;
  text-align: right;
  vertical-align: middle;
}

/* Linha ativa: destaque lateral (menos “tinta” que fundo inteiro) */
.tr-ativo {
  background: var(--color-primary-pastel-01, #e8f0ff) !important;
  box-shadow: inset 3px 0 0 var(--color-primary-default, #1351b4);
}

/* ── Ordenação (botões no cabeçalho) ── */
.th-sort-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: none;
  background: transparent;
  padding: 0;
  margin: 0;
  color: inherit;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  text-align: left;
}

.th-sort-btn__icone {
  font-size: 0.6875rem;
  color: var(--color-primary-default, #1351b4);
  opacity: 0.85;
}

.th-sort-btn:focus-visible {
  outline: 2px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
  border-radius: 2px;
}

.th-sort-icon {
  font-size: 0.6875rem;
  color: var(--color-secondary-06, #666);
  font-weight: 400;
}

/* ── Ações: estilo contorno (ghost) — hierarquia visual clara ── */
.btn-acao {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.75rem;
  margin: 0.125rem 0 0.125rem 0.35rem;
  font-size: 0.8125rem;
  font-weight: 600;
  font-family: inherit;
  line-height: 1.2;
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 6px;
  cursor: pointer;
  background: var(--background, #fff);
  color: var(--color-secondary-09, #333);
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.btn-acao__icone {
  font-size: 0.75rem;
  flex-shrink: 0;
  opacity: 0.9;
}

.btn-acao:hover:not(:disabled) {
  border-color: var(--color-primary-default, #1351b4);
  color: var(--color-primary-default, #1351b4);
  background: var(--color-primary-pastel-01, #e8f0ff);
}

.btn-acao:focus-visible {
  outline: 2px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
}

.btn-acao--visualizar .btn-acao__icone {
  color: var(--color-primary-default, #1351b4);
}

.btn-acao--editar .btn-acao__icone {
  color: var(--color-warning-darken-01, #c26100);
}

.btn-acao--historico .btn-acao__icone {
  color: var(--color-success-darken-01, #168821);
}

/* ── Tags tipo / vigência: neutras + borda suave (menos saturadas) ── */
.tag-hierarquia {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid transparent;
  background: var(--background, #fff);
}

.tag-hierarquia i {
  font-size: 0.6875rem;
  opacity: 0.9;
}

.tag-hierarquia--federal {
  border-color: #90caf9;
  color: var(--color-primary-darken-02, #0c326f);
  background: var(--color-primary-pastel-01, #e8f0ff);
}

.tag-hierarquia--estadual {
  border-color: #ffcc80;
  color: #b34c00;
  background: #fff8f0;
}

.tag-hierarquia--municipal {
  border-color: #ce93d8;
  color: #4a148c;
  background: #faf5fc;
}

.tag-situacao {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 600;
  border-radius: 6px;
  border: 1px solid transparent;
}

.tag-situacao i {
  font-size: 0.75rem;
}

.tag--vigente {
  border-color: #a5d6a7;
  color: #1b5e20;
  background: #f1f8f2;
}

.tag--nao-vigente {
  border-color: #ffcdd2;
  color: #b71c1c;
  background: #fff8f7;
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

/* ── Shared ── */
.loading-spinner {
  width: 32px; height: 32px;
  border: 3px solid var(--color-secondary-03, #eee);
  border-top-color: var(--color-primary-default, #1351b4);
  border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 575px) {
  .pagina-hero__title { font-size: 1.25rem; }
  .pagina-hero__topo { flex-direction: column; align-items: flex-start; }
}
</style>
