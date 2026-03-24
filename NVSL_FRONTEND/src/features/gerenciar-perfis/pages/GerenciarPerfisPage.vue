<template>
  <DefaultLayout>
    <section class="gerenciar-perfis" :class="{ 'painel-aberto': painelAberto }">
      <header class="pagina-hero">
        <div class="pagina-hero__topo">
          <h1 class="pagina-hero__title">Gerenciar Perfis</h1>
          <button class="br-button primary pagina-hero__btn" type="button" @click="abrirCadastrar" aria-label="Novo perfil">
            <i class="fas fa-plus" aria-hidden="true"></i> Novo Perfil
          </button>
        </div>
        <p class="pagina-hero__lead">
          Consulte, cadastre e edite os perfis de acesso do sistema NVSL.
        </p>
      </header>

      <Card custom-class="mb-4">
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
                    Situação <span class="th-sort-icon">{{ obterIconeSort('status') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-acoes">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="p in perfisOrdenados"
                :key="p.id"
                :class="{ 'tr-ativo': perfilSelecionado?.id === p.id }"
              >
                <td class="td-nome">{{ p.nome }}</td>
                <td>{{ labelTipoPerfil(p.esfera) }}</td>
                <td>
                  <span class="tag-situacao" :class="classeSituacao(p.status)">
                    {{ labelSituacao(p.status) }}
                  </span>
                </td>
                <td class="td-acoes">
                  <button class="btn-acao btn-acao--visualizar" type="button" @click="abrirVisualizar(p)" title="Visualizar perfil">
                    <i class="fas fa-eye" aria-hidden="true"></i> Visualizar
                  </button>
                  <button
                    v-if="podeEditar(p)"
                    class="btn-acao btn-acao--editar"
                    type="button"
                    @click="abrirEditar(p)"
                    title="Editar perfil"
                  >
                    <i class="fas fa-edit" aria-hidden="true"></i> Editar
                  </button>
                  <button class="btn-acao btn-acao--historico" type="button" @click="abrirHistorico(p)" title="Histórico do perfil">
                    <i class="fas fa-history" aria-hidden="true"></i> Histórico
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
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
import PainelFormularioPerfil from '../components/PainelFormularioPerfil.vue'
import PainelHistoricoPerfil from '../components/PainelHistoricoPerfil.vue'
import { listarPerfisGerenciar, type PerfilGerenciar } from '@/services/GerenciarPerfilService'
import { useNotification } from '@/core/composables/useNotification'
import { useAuthStore } from '@/stores/authStore'

defineOptions({ name: 'GerenciarPerfisPage' })

const { error } = useNotification()
const authStore = useAuthStore()

const perfis = ref<PerfilGerenciar[]>([])
const carregando = ref(false)

const sortColuna = ref<string | null>(null)
const sortAsc = ref(true)

type ModoPainel = 'cadastrar' | 'editar' | 'visualizar' | 'historico'
const modoPainel = ref<ModoPainel>('cadastrar')
const perfilSelecionado = ref<PerfilGerenciar | null>(null)
const painelAberto = ref(false)
const formularioDirty = ref(false)
const confirmarSairVisivel = ref(false)

const esferaUsuario = computed(() => authStore.user?.esfera_atuacao ?? 'federal')

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
  if (!sortColuna.value) return lista
  const col = sortColuna.value
  const asc = sortAsc.value
  lista.sort((a, b) => {
    let va: string
    let vb: string
    if (col === 'nome') {
      va = (a.nome ?? '').toLowerCase()
      vb = (b.nome ?? '').toLowerCase()
    } else if (col === 'esfera') {
      va = labelTipoPerfil(a.esfera).toLowerCase()
      vb = labelTipoPerfil(b.esfera).toLowerCase()
    } else if (col === 'status') {
      va = labelSituacao(a.status).toLowerCase()
      vb = labelSituacao(b.status).toLowerCase()
    } else {
      return 0
    }
    const cmp = va.localeCompare(vb, 'pt-BR')
    return asc ? cmp : -cmp
  })
  return lista
})

function podeEditar(perfil: PerfilGerenciar): boolean {
  const esfera = esferaUsuario.value
  if (esfera === 'federal') return true
  if (esfera === 'estadual') return perfil.esfera === 'estadual'
  if (esfera === 'municipal') return perfil.esfera === 'municipal'
  return false
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
    perfis.value = await listarPerfisGerenciar()
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

function labelSituacao(status: string): string {
  return status === 'ativo' ? 'Vigente' : 'Não Vigente'
}

function classeSituacao(status: string): string {
  return status === 'ativo' ? 'tag--vigente' : 'tag--nao-vigente'
}

onMounted(() => carregarPerfis())
</script>

<style scoped>
.gerenciar-perfis {
  padding: 1.5rem 0;
  position: relative;
  display: flex;
  flex-direction: column;
}

/* ── Hero (padrão solicitacao-hero) ── */
.pagina-hero { margin-bottom: 1.5rem; }

.pagina-hero__topo {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagina-hero__title {
  margin: 0 0 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.25;
  color: var(--color-primary-darken-02, #0c326f);
  letter-spacing: -0.02em;
}

@media (min-width: 768px) {
  .pagina-hero__title { font-size: 1.75rem; }
}

.pagina-hero__lead {
  margin: 0;
  max-width: 62rem;
  font-size: 0.9375rem;
  line-height: 1.55;
  color: var(--color-secondary-08, #333);
}

.pagina-hero__btn {
  flex-shrink: 0;
}

/* ── Estado vazio / carregando ── */
.estado-vazio {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2.5rem 1rem;
  color: var(--color-secondary-06, #888);
}

.estado-vazio p { margin: 0; font-size: 0.875rem; }

/* ── Tabela ── */
.tabela-perfis { width: 100%; border-collapse: collapse; }

.tabela-perfis thead th {
  background: var(--color-secondary-02, #f0f0f0);
  font-weight: 700;
  font-size: 0.8125rem;
  padding: 0.75rem 1rem;
  border-bottom: 2px solid var(--color-secondary-04, #ccc);
  text-align: left;
  color: var(--color-secondary-08, #333);
}

.tabela-perfis tbody tr { transition: background 0.12s; }
.tabela-perfis tbody tr:hover { background: var(--color-secondary-01, #f8f8f8); }

.tabela-perfis tbody td {
  padding: 0.625rem 1rem;
  border-bottom: 1px solid var(--color-secondary-03, #e8e8e8);
  font-size: 0.875rem;
  vertical-align: middle;
  color: var(--color-secondary-08, #333);
}

.td-nome { font-weight: 600; }
.th-acoes { text-align: center; width: 1%; white-space: nowrap; }
.td-acoes { white-space: nowrap; text-align: center; vertical-align: middle; }
.tr-ativo { background: var(--color-primary-pastel, #dbeeff) !important; }

/* ── Sort buttons ── */
.th-sort-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  border: none; background: transparent; padding: 0;
  color: inherit; font: inherit; font-weight: 700; cursor: pointer;
}
.th-sort-btn:focus-visible { outline: 2px solid var(--color-primary-default, #1351b4); outline-offset: 2px; border-radius: 2px; }
.th-sort-icon { font-size: 0.75rem; color: var(--color-secondary-06, #666); }

/* ── Botões de ação na tabela ── */
.btn-acao {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.6rem; font-size: 0.75rem; font-weight: 600;
  border: 1px solid; border-radius: 4px; cursor: pointer;
  transition: all 0.15s; margin: 0 0.15rem;
}
.btn-acao i { font-size: 0.65rem; }
.btn-acao:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }

.btn-acao--visualizar { color: #1351b4; border-color: #1351b4; background: #eef3fb; }
.btn-acao--visualizar:hover { background: #1351b4; color: #fff; }
.btn-acao--editar { color: #c26100; border-color: #c26100; background: #fff5eb; }
.btn-acao--editar:hover { background: #c26100; color: #fff; }
.btn-acao--historico { color: #2e7d32; border-color: #2e7d32; background: #edf7ed; }
.btn-acao--historico:hover { background: #2e7d32; color: #fff; }

/* ── Tag de situação ── */
.tag-situacao {
  display: inline-block; padding: 0.2rem 0.625rem;
  font-size: 0.75rem; font-weight: 600; border-radius: 100em; letter-spacing: 0.02em;
}
.tag--vigente { background: #e0f5e4; color: #0a6621; }
.tag--nao-vigente { background: #fde0db; color: #b71c1c; }

/* ── Overlay + Painel ── */
.painel-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.35); z-index: 999;
}

.painel-lateral {
  position: fixed; top: 0; right: 0;
  width: 100%; max-width: 100%; height: 100vh;
  background: var(--background, #fff);
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  z-index: 1000; overflow-y: auto;
}

@media (min-width: 576px) { .painel-lateral { width: 62%; } }
@media (min-width: 1200px) { .painel-lateral { width: 55%; } }

/* ── Modal de confirmação ── */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.5); z-index: 2000;
  display: flex; align-items: center; justify-content: center;
}

.modal-confirmacao {
  background: #fff; border-radius: 12px; padding: 2rem;
  max-width: 420px; width: 90%; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
}

.modal-confirmacao__header {
  display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;
}

.modal-confirmacao__icone { font-size: 1.5rem; color: #c26100; }

.modal-confirmacao__titulo {
  font-size: 1.125rem; font-weight: 700; margin: 0;
  color: var(--color-secondary-09, #1a1a1a);
}

.modal-confirmacao__texto {
  font-size: 0.875rem; color: var(--color-secondary-07, #555);
  margin: 0 0 1.5rem; line-height: 1.5;
}

.modal-confirmacao__acoes {
  display: flex; gap: 0.75rem; justify-content: flex-end;
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
