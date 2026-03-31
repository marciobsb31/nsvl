<template>
  <DefaultLayout>
    <section class="gerenciar-perfis" :class="{ 'painel-aberto': painelAberto }">
      <div class="titulo-pagina">
        <div class="titulo-pagina__topo">
          <div>
            <h1 id="titulo-gerenciar-perfis" class="titulo-pagina__h1">
              Gerenciar perfis de acesso no sistema
            </h1>
            <p class="titulo-pagina__subtitulo">
              Visualize, cadastre e edite os perfis de acesso do NVSL. A vigência e a hierarquia (federal, estadual e
              municipal) definem quem pode criar ou alterar cada perfil.
            </p>
          </div>
          <button
            class="br-button primary small"
            type="button"
            @click="abrirCadastrar"
            aria-label="Cadastrar novo perfil"
          >
            <span class="titulo-pagina__btn-conteudo">
              <i class="fas fa-plus-circle" aria-hidden="true"></i>
              <span>Novo perfil</span>
            </span>
          </button>
        </div>
      </div>

      <div v-if="contextoAtualLabel" class="contexto-banner" role="status" aria-live="polite">
        <i class="fas fa-shield-alt contexto-banner__icon" aria-hidden="true"></i>
        <span class="contexto-banner__label">Contexto ativo:</span>
        <strong class="contexto-banner__valor">{{ contextoAtualLabel }}</strong>
      </div>

      <Card custom-class="gerenciar-perfis__card mb-4">
        <div v-if="carregando" class="br-loading p-4" role="status" aria-live="polite">
          <div class="loading-spinner" aria-hidden="true"></div>
          <p class="mt-2">Carregando perfis...</p>
        </div>

        <div v-else-if="perfisOrdenados.length === 0" class="p-4 text-center text-muted">
          <i class="fas fa-users-cog fa-3x mb-3" aria-hidden="true"></i>
          <p class="mb-0">Nenhum perfil encontrado.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="br-table tabela-perfis" role="table">
            <thead>
              <tr>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('nome')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('nome')">
                    Nome do perfil <span class="th-sort-icon">{{ obterIndicadorSort('nome') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('status')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('status')">
                    Situação <span class="th-sort-icon">{{ obterIndicadorSort('status') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold th-acoes">Ações</th>
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
                  <span class="br-tag" :class="classeSituacao(p.status)">
                    {{ labelSituacao(p.status) }}
                  </span>
                </td>
                <td class="td-acoes">
                  <div class="tabela-perfis__acoes">
                    <button
                      class="br-button secondary small btn-acao btn-acao--visualizar"
                      type="button"
                      @click="abrirVisualizar(p)"
                      title="Visualizar perfil"
                    >
                      Visualizar
                    </button>
                    <button
                      v-if="podeEditar(p)"
                      class="br-button secondary small btn-acao btn-acao--editar"
                      type="button"
                      @click="abrirEditar(p)"
                      title="Editar perfil"
                    >
                      Editar
                    </button>
                    <button
                      class="br-button secondary small btn-acao btn-acao--historico"
                      type="button"
                      @click="abrirHistorico(p)"
                      title="Histórico do perfil"
                    >
                      Histórico
                    </button>
                  </div>
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
import { useAuth } from '@/core/composables/useAuth'
import { useAuthStore } from '@/stores/authStore'

defineOptions({ name: 'GerenciarPerfisPage' })

const { error } = useNotification()
const authStore = useAuthStore()
const { user, perfilAtivo } = useAuth()

const esferaMap: Record<string, string> = {
  federal: 'Federal',
  estadual: 'Estadual',
  municipal: 'Municipal',
}

const contextoAtualLabel = computed(() => {
  const perfil = perfilAtivo.value
  const esfera = user.value?.esfera_atuacao
  const uf = user.value?.uf_lotacao
  const municipio = user.value?.municipio_lotacao
  if (!perfil) {
    return esfera ? esferaMap[esfera] ?? esfera : ''
  }
  const partes: string[] = []
  if (perfil.nome) partes.push(perfil.nome)
  if (esfera) partes.push(esferaMap[esfera] ?? esfera)
  if (uf) partes.push(uf)
  if (municipio) partes.push(municipio)
  return partes.join(' — ')
})

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
    lista.sort((a, b) => (a.nome ?? '').localeCompare(b.nome ?? '', 'pt-BR'))
    return lista
  }
  const col = sortColuna.value
  const asc = sortAsc.value
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

const perfisPaginados = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return perfisOrdenados.value.slice(inicio, fim)
})

function podeEditar(_perfil: PerfilGerenciar): boolean {
  const esferaUsuario = (authStore.user?.esfera_atuacao ?? 'federal').toLowerCase()
  if (esferaUsuario === 'municipal') {
    return false
  }
  return esferaUsuario === 'federal' || esferasPermitidas.value.length > 0
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

function obterIndicadorSort(coluna: string): string {
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

function labelSituacao(status: string): string {
  return status === 'ativo' ? 'Vigente' : 'Não vigente'
}

/** Modificadores do componente br-tag (GOVBR DS) */
function classeSituacao(status: string): string {
  return status === 'ativo' ? 'success' : 'warning'
}

onMounted(() => carregarPerfis())
</script>

<style scoped>
.gerenciar-perfis {
  width: 100%;
  padding: 1.5rem 0;
  position: relative;
  display: flex;
  flex-direction: column;
}

.contexto-banner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  margin-bottom: 1rem;
  background: var(--color-primary-pastel, #dbe8fb);
  border-left: 4px solid var(--color-primary-default, #1351b4);
  border-radius: 4px;
  font-size: 0.875rem;
  color: var(--color-secondary-08, #333);
  transition: all 0.3s ease;
}

.contexto-banner__icon {
  color: var(--color-primary-default, #1351b4);
  font-size: 1rem;
  flex-shrink: 0;
}

.contexto-banner__label {
  color: var(--color-secondary-06, #888);
  white-space: nowrap;
}

.contexto-banner__valor {
  color: var(--color-primary-default, #1351b4);
}

[data-theme='dark'] .contexto-banner {
  background: rgba(19, 81, 180, 0.15);
  border-left-color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme='dark'] .contexto-banner__icon,
[data-theme='dark'] .contexto-banner__valor {
  color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme='dark'] .contexto-banner__label {
  color: rgba(255, 255, 255, 0.6);
}

/* Card — superfície neutra (GOVBR DS) */
.gerenciar-perfis :deep(.gerenciar-perfis__card) {
  border: 1px solid var(--color-secondary-03, #e8e8e8) !important;
  box-shadow: none !important;
  border-radius: 8px;
  overflow: hidden;
  background: var(--background, #fff);
}

/* ── Título da página (alinhado a Gerenciar solicitações) ── */
.titulo-pagina {
  margin-bottom: 1.5rem;
}

.titulo-pagina__topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  flex-wrap: wrap;
}

.titulo-pagina__h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0;
}

.titulo-pagina__subtitulo {
  font-size: 1rem;
  color: var(--color-secondary-07, #555);
  margin: 0.5rem 0 0;
  max-width: 48rem;
  line-height: 1.5;
}

.titulo-pagina__btn-conteudo {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.text-muted {
  color: var(--color-secondary-07, #555);
}

@media (max-width: 575px) {
  .titulo-pagina__h1 {
    font-size: 1.25rem;
  }

  .titulo-pagina__subtitulo {
    font-size: 0.875rem;
  }

  .titulo-pagina__topo {
    flex-direction: column;
    align-items: flex-start;
  }
}

/* ── Tabela br-table (Design System eGov) ── */
.table-responsive {
  margin: 0 -0.25rem;
}

@media (min-width: 768px) {
  .table-responsive {
    margin: 0;
  }
}

.tabela-perfis th.th-bold {
  font-weight: 700;
}

.td-nome {
  font-weight: 600;
}

.tabela-perfis td:nth-child(2) .br-tag {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.th-acoes {
  text-align: right;
  width: 1%;
  white-space: nowrap;
}

.td-acoes {
  text-align: right;
  vertical-align: middle;
}

.tabela-perfis__acoes {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  justify-content: flex-end;
  align-items: center;
}

.tr-ativo {
  background: var(--color-primary-pastel-01, #e8f0ff) !important;
  box-shadow: inset 3px 0 0 var(--color-primary-default, #1351b4);
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.mt-2 {
  margin-top: 0.5rem;
}

.mb-3 {
  margin-bottom: 1rem;
}

.mb-0 {
  margin-bottom: 0;
}

.p-4 {
  padding: 1rem;
}

.text-center {
  text-align: center;
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
