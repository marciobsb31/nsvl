<template>
  <DefaultLayout>
      <HeaderPage title="Gerenciar solicitação de cadastros no sistema"
        :subtitle="'Aplique filtros e clique em <strong>Pesquisar</strong>.'"
        customClass="mb-3">
        <template v-slot:actions>
          <br-button :color-mode="$appTheme ==='dark' ? $appTheme : undefined" emphasis="primary" @click="onCliqueCadastrarUsuario" aria-label="Cadastrar usuário">
            Cadastrar usuário
          </br-button>
        </template>
      </HeaderPage>

          <Contexto />

      <Card custom-class="mb-4">
        <FiltrosGerenciarSolicitacao
          :key="contextKey"
          :carregando="carregando"
          @pesquisar="aplicarFiltros"
          @limpar="limparEpesquisar"
        />
      </Card>

      <Card custom-class="mb-4" v-if="!isMobile">

        <div v-if="carregando" class="loading-container" role="status" aria-live="polite">
          <div class="loading-spinner" aria-hidden="true"></div>
          <p class="mt-2">Carregando solicitações...</p>
        </div>

        <div v-else-if="solicitacoes.length === 0" class="p-4 text-center text-muted">
          <i class="fas fa-inbox fa-3x mb-3" aria-hidden="true"></i>
          <p>{{ jaListou ? 'Nenhuma solicitação encontrada.' : 'Aplique os filtros e clique em Pesquisar para buscar as solicitações.' }}</p>
          <p v-if="jaListou" class="small">Ajuste os filtros ou cadastre uma nova solicitação.</p>
        </div>

        <div v-else class="table-responsive" >
          <table class="br-table tabela-solicitacoes" role="table">
            <thead>
              <tr>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('cpf')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('cpf')">
                    CPF <span class="th-sort-icon">{{ obterIndicadorSort('cpf') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('nome')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('nome')">
                    Nome completo <span class="th-sort-icon">{{ obterIndicadorSort('nome') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('esfera')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('esfera')">
                    Esfera de atuação <span class="th-sort-icon">{{ obterIndicadorSort('esfera') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('uf')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('uf')">
                    UF <span class="th-sort-icon">{{ obterIndicadorSort('uf') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('municipio')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('municipio')">
                    Município <span class="th-sort-icon">{{ obterIndicadorSort('municipio') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('orgao')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('orgao')">
                    Órgão <span class="th-sort-icon">{{ obterIndicadorSort('orgao') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold" :aria-sort="obterAriaSort('status')">
                  <button class="th-sort-btn" type="button" @click="ordenarPor('status')">
                    Situação <span class="th-sort-icon">{{ obterIndicadorSort('status') }}</span>
                  </button>
                </th>
                <th scope="col" class="th-bold">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in solicitacoesPaginadas" :key="s.id">
                <td>{{ s.cpf ?? '—' }}</td>
                <td>{{ s.nome }}</td>
                <td>{{ labelEsfera(s.esfera_atuacao) }}</td>
                <td>{{ s.uf ?? '—' }}</td>
                <td>{{ s.municipio ?? '—' }}</td>
                <td>{{ s.orgao ?? '—' }}</td>
                <td>
                  <span class="br-tag" :class="classeStatus(s.status)">
                    {{ labelStatus(s.status) }}
                  </span>
                </td>
                <td>
                  <button
                    class="br-button secondary small"
                    type="button"
                    @click="detalhar(s)"
                    :disabled="carregandoDetalhe"
                    :aria-label="rotuloBotaoDetalhar(s.status)"
                  >
                    {{ rotuloBotaoDetalhar(s.status) }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <PaginationControls
          v-if="solicitacoesOrdenadas.length > 0"
          v-model:currentPage="paginaAtual"
          v-model:pageSize="itensPorPagina"
          :total-items="solicitacoesOrdenadas.length"
        />

      </Card>
      <Card custom-class="mb-4" v-if="isMobile && solicitacoesPaginadas.length > 0">
        <div class="row table-mobile" v-for="s in solicitacoesPaginadas" :key="s.id">
          <div class="col-12 mb-1">
            <label for="nome">Nome completo</label>
            <p class="m-0">{{ s.nome }}</p>
          </div>
          <div class="col-6 mb-1">
            <label for="cpf">CPF</label>
            <p class="m-0">{{ s.cpf }}</p>
          </div>
          <div class="col-6 mb-1">
            <label for="esfera">Esfera de atuação</label>
            <p class="m-0">{{ labelEsfera(s.esfera_atuacao) }}</p>
          </div>
          <div class="col-6 mb-1">
            <label for="orgao">Órgão</label>
            <p class="m-0">{{ s.orgao }}</p>
          </div>
          <div class="col-6 mb-1">
          <label for="situacao">Situação</label><br></br>
           <span class="br-tag" :class="classeStatus(s.status)">
                    {{ labelStatus(s.status) }}
                  </span>
          </div>
          <div class="col-12 mt-3">
           <button
                    class="br-button secondary small block"
                    type="button"
                    @click="detalhar(s)"
                    :disabled="carregandoDetalhe"
                    :aria-label="rotuloBotaoDetalhar(s.status)"
                    :title="rotuloBotaoDetalhar(s.status)"
                    slot="trigger"
                  >
                    {{ rotuloBotaoDetalhar(s.status) }}
                  </button>
          </div>
          <div class="col-12 mt-3">
            <span class="br-divider my-3"></span>
          </div>
        </div>
      </Card>

      <Transition name="painel-fade">
        <div
          v-if="painelCadastroAberto || painelDetalharAberto"
          class="painel-overlay"
          aria-hidden="true"
          @click="fecharPainelAberto"
        ></div>
      </Transition>
      <Transition name="painel-slide">
        <aside
          v-if="painelCadastroAberto"
          ref="painelCadastroRef"
          class="painel-cadastro"
          aria-label="Formulário cadastrar usuário"
        >
          <div class="painel-cadastro-inner">
            <FormularioCadastrarUsuario
              :usuario-logado="user ?? undefined"
              @voltar="fecharPainelCadastro"
              @sucesso="onCadastroSucesso"
            />
          </div>
        </aside>
      </Transition>
      <Transition name="painel-slide">
        <aside
          v-if="painelDetalharAberto"
          class="painel-cadastro"
          aria-label="Detalhar solicitação de cadastro"
        >
          <PainelDetalharSolicitacao
            :detalhe="detalheSelecionado"
            :avaliando="avaliando"
            :eh-proprio-cadastro="ehProprioCadastro"
            @voltar="fecharPainelDetalhar"
            @aprovar="aprovarSolicitacao"
            @reprovar="reprovarSolicitacao"
            @toggle-perfil="onTogglePerfilVinculado"
          />
        </aside>
      </Transition>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Card from '@/core/components/Card/Card.vue'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'
import FiltrosGerenciarSolicitacao from '../components/FiltrosGerenciarSolicitacao.vue'
import FormularioCadastrarUsuario from '../components/FormularioCadastrarUsuario.vue'
import PainelDetalharSolicitacao from '../components/PainelDetalharSolicitacao.vue'
import {
  listarSolicitacoesGerenciar,
  aprovarSolicitacao as apiAprovar,
  reprovarSolicitacao as apiReprovar,
  ativarPerfilVinculado as apiAtivarPerfilVinculado,
  desativarPerfilVinculado as apiDesativarPerfilVinculado,
  type SolicitacaoGerenciarItem,
  type FiltrosGerenciarSolicitacao as FiltrosGerenciarSolicitacaoType,
} from '@/services/GerenciarSolicitacaoCadastroService'
import { obterSolicitacaoCadastro } from '@/services/SolicitacaoCadastroService'
import { useNotification } from '@/core/composables/useNotification'
import { useAuth } from '@/core/composables/useAuth'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import { BrButton } from '@govbr-ds/webcomponents-vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import Contexto from '@/core/components/Contexto/Contexto.vue'
import type { SolicitacaoCadastroDetalhe } from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'


defineOptions({ name: 'GerenciarSolicitacaoCadastroPage' })

const { error, success } = useNotification()
const { isMobile } = useBreakpoint()

const { user, contextKey, perfilAtivo } = useAuth()

const solicitacoes = ref<SolicitacaoGerenciarItem[]>([])
const carregando = ref(false)
const jaListou = ref(false)
const ordenarColuna = ref<string | null>(null)
const ordenarAsc = ref(true)
const paginaAtual = ref(1)
const itensPorPagina = ref(10)

const solicitacoesOrdenadas = computed(() => {
  const lista = [...solicitacoes.value]
  const prioridadeStatus = (status?: string) => (status === 'em_analise' ? 0 : 1)

  if (!ordenarColuna.value) {
    return lista.sort((a, b) => {
      const prioridade = prioridadeStatus(a.status) - prioridadeStatus(b.status)
      if (prioridade !== 0) return prioridade

      const dataA = new Date(a.created_at ?? '').getTime()
      const dataB = new Date(b.created_at ?? '').getTime()
      return dataB - dataA
    })
  }
  const col = ordenarColuna.value
  const asc = ordenarAsc.value
  lista.sort((a, b) => {
    const prioridade = prioridadeStatus(a.status) - prioridadeStatus(b.status)
    if (prioridade !== 0) return prioridade

    let va: string | number
    let vb: string | number
    if (col === 'cpf') {
      va = (a.cpf ?? '').toLowerCase()
      vb = (b.cpf ?? '').toLowerCase()
    } else if (col === 'nome') {
      va = (a.nome ?? '').toLowerCase()
      vb = (b.nome ?? '').toLowerCase()
    } else if (col === 'esfera') {
      va = labelEsfera(a.esfera_atuacao).toLowerCase()
      vb = labelEsfera(b.esfera_atuacao).toLowerCase()
    } else if (col === 'uf') {
      va = (a.uf ?? '').toLowerCase()
      vb = (b.uf ?? '').toLowerCase()
    } else if (col === 'municipio') {
      va = (a.municipio ?? '').toLowerCase()
      vb = (b.municipio ?? '').toLowerCase()
    } else if (col === 'orgao') {
      va = (a.orgao ?? '').toLowerCase()
      vb = (b.orgao ?? '').toLowerCase()
    } else if (col === 'status') {
      va = labelStatus(a.status).toLowerCase()
      vb = labelStatus(b.status).toLowerCase()
    } else {
      return 0
    }
    const cmp = String(va).localeCompare(String(vb), 'pt-BR')
    return asc ? cmp : -cmp
  })
  return lista
})

const solicitacoesPaginadas = computed(() => {
  const inicio = (paginaAtual.value - 1) * itensPorPagina.value
  const fim = inicio + itensPorPagina.value
  return solicitacoesOrdenadas.value.slice(inicio, fim)
})

function ordenarPor(coluna: string) {
  if (ordenarColuna.value === coluna) {
    ordenarAsc.value = !ordenarAsc.value
  } else {
    ordenarColuna.value = coluna
    ordenarAsc.value = true
  }
}
const filtrosAtivos = ref<FiltrosGerenciarSolicitacaoType>({})
const painelDetalharAberto = ref(false)
const detalheSelecionado = ref<(SolicitacaoCadastroDetalhe & { cpf?: string }) | null>(null)
const painelCadastroAberto = ref(false)
const avaliando = ref(false)

const ehProprioCadastro = computed(() => {
  if (!detalheSelecionado.value?.usuario_id || !user.value?.id) return false
  return detalheSelecionado.value.usuario_id === user.value.id
})

function onCadastroSucesso() {
  fecharPainelCadastro()
  carregarSolicitacoes()
}

async function carregarSolicitacoes() {
  carregando.value = true
  jaListou.value = true
  try {
    solicitacoes.value = await listarSolicitacoesGerenciar(filtrosAtivos.value)
     paginaAtual.value = 1
  } catch {
    solicitacoes.value = []
    error('Não foi possível carregar as solicitações. Verifique se o backend está em execução.')
  } finally {
    carregando.value = false
  }
}

function aplicarFiltros(filtros: FiltrosGerenciarSolicitacaoType) {
  filtrosAtivos.value = filtros
  ordenarColuna.value = null
  carregarSolicitacoes()
}

function limparEpesquisar() {
  filtrosAtivos.value = {}
  ordenarColuna.value = null
  carregarSolicitacoes()
}

function abrirPainelCadastro() {
  painelDetalharAberto.value = false
  painelCadastroAberto.value = true
}

function onCliqueCadastrarUsuario() {
  abrirPainelCadastro()
}

function fecharPainelCadastro() {
  painelCadastroAberto.value = false
}

function fecharPainelDetalhar() {
  painelDetalharAberto.value = false
  detalheSelecionado.value = null
}

function fecharPainelAberto() {
  if (painelCadastroAberto.value) fecharPainelCadastro()
  if (painelDetalharAberto.value) fecharPainelDetalhar()
}

function formatarData(data: string | undefined) {
  if (!data) return '—'
  try {
    return new Date(data).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return data
  }
}

function labelStatus(status: string) {
  const map: Record<string, string> = {
    em_analise: 'Em análise',
    aprovado: 'Aprovada',
    reprovado: 'Reprovada',
  }
  return map[status] ?? status
}

function classeStatus(status: string) {
  const map: Record<string, string> = {
    em_analise: 'warning',
    aprovado: 'success',
    reprovado: 'danger',
  }
  return map[status] ?? ''
}

function labelEsfera(esfera?: string) {
  if (!esfera) return '—'
  const map: Record<string, string> = {
    federal: 'Federal',
    estadual: 'Estadual',
    municipal: 'Municipal',
  }
  return map[esfera] ?? esfera
}

/**
 * Regra: "Em análise" → "Detalhar/Analisar"; demais → "Detalhar"
 */
function rotuloBotaoDetalhar(status: string) {
  return status === 'em_analise' ? 'Detalhar/Analisar' : 'Detalhar'
}

const carregandoDetalhe = ref(false)


async function detalhar(s: SolicitacaoGerenciarItem) {
  const id = s?.id
  if (id == null || id === undefined) {
    console.error('[Detalhar] ID inválido — item:', s)
    error('Não foi possível identificar a solicitação. Tente clicar em Listar novamente.')
    return
  }
  console.log('[Detalhar] Iniciando — id:', id, 'nome:', s?.nome)
  carregandoDetalhe.value = true
  painelCadastroAberto.value = false
  painelDetalharAberto.value = false
  detalheSelecionado.value = null
  try {
    const detalhe = await obterSolicitacaoCadastro(id)
    console.log('[Detalhar] Resposta da API:', detalhe ? { id: detalhe.id, nome: detalhe.nome, perfis: detalhe.perfis_vinculados?.length } : null)
    if (!detalhe?.id) {
      throw new Error('Resposta da API inválida: dados incompletos.')
    }
    detalheSelecionado.value = detalhe
    painelDetalharAberto.value = true
    console.log('[Detalhar] Painel aberto com sucesso.')
  } catch (e: unknown) {
    const err = e as { response?: { status?: number; data?: { message?: string } }; message?: string }
    const status = err?.response?.status
    let msg =
      err?.response?.data?.message ??
      err?.message ??
      'Erro ao carregar detalhes da solicitação.'
    if (status === 401) {
      msg = 'Sessão expirada. Faça login novamente.'
    } else if (status === 403) {
      msg = 'Acesso negado a esta solicitação.'
    } else if (status === 404) {
      msg = 'Solicitação não encontrada.'
    }
    console.error('[Detalhar] Erro ao abrir painel — status:', status, 'msg:', msg, 'objeto:', e)
    error(msg)
  } finally {
    carregandoDetalhe.value = false
  }
}

async function aprovarSolicitacao(payload?: { perfilId?: string | number | null; vigenciaInicio?: string; vigenciaFim?: string }) {
  if (!detalheSelecionado.value) return
  avaliando.value = true
  try {
    await apiAprovar(detalheSelecionado.value.id, payload)
    success('Solicitação aprovada com sucesso.')
    fecharPainelDetalhar()
    carregarSolicitacoes()
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Erro ao aprovar.'
    error(msg)
  } finally {
    avaliando.value = false
  }
}

async function reprovarSolicitacao(payload: { justificativa: string }) {
  if (!detalheSelecionado.value) return
  avaliando.value = true
  try {
    await apiReprovar(detalheSelecionado.value.id, payload.justificativa)
    success('Solicitação reprovada.')
    fecharPainelDetalhar()
    carregarSolicitacoes()
  } catch (e: unknown) {
    const msg = (e as { response?: { data?: { message?: string } } })?.response?.data?.message ?? 'Erro ao reprovar.'
    error(msg)
  } finally {
    avaliando.value = false
  }
}

async function onTogglePerfilVinculado(payload: { perfilUsuarioId: number; acao: 'ativar' | 'desativar' }) {
  if (!detalheSelecionado.value) return

  avaliando.value = true
  const solicitacaoId = detalheSelecionado.value.id
  try {
    if (payload.acao === 'ativar') {
      await apiAtivarPerfilVinculado(solicitacaoId, payload.perfilUsuarioId)
      success('Cadastro ativado com sucesso.')
    } else {
      await apiDesativarPerfilVinculado(solicitacaoId, payload.perfilUsuarioId)
      success('Cadastro desativado com sucesso.')
    }

    // Atualização otimista imediata para refletir no badge
    if (detalheSelecionado.value?.perfis_vinculados) {
      const perfisAtualizados = detalheSelecionado.value.perfis_vinculados.map(p => ({
        ...p,
        ativo: p.perfil_usuario_id === payload.perfilUsuarioId
          ? (payload.acao === 'ativar')
          : p.ativo,
        vigente: p.perfil_usuario_id === payload.perfilUsuarioId
          ? (payload.acao === 'ativar')
          : p.vigente,
      }))
      detalheSelecionado.value = { ...detalheSelecionado.value, perfis_vinculados: perfisAtualizados }
    }

    // Re-fetch do servidor para garantir consistência
    const atualizado = await obterSolicitacaoCadastro(solicitacaoId)
    detalheSelecionado.value = { ...atualizado }
  } catch (e: unknown) {
    const msg =
      (e as { response?: { data?: { message?: string } } })?.response?.data?.message ??
      'Erro ao atualizar status do cadastro.'
    error(msg)
  } finally {
    avaliando.value = false
  }
}

function obterAriaSort(coluna: string) {
  if (ordenarColuna.value !== coluna) return 'none'
  return ordenarAsc.value ? 'ascending' : 'descending'
}

function obterIndicadorSort(coluna: string) {
  if (ordenarColuna.value !== coluna) return '↕'
  return ordenarAsc.value ? '↑' : '↓'
}


const esferaMap: Record<string, string> = {
  federal: 'Federal',
  estadual: 'Estadual',
  municipal: 'Municipal',
}


watch(contextKey, () => {
  painelCadastroAberto.value = false
  painelDetalharAberto.value = false
  detalheSelecionado.value = null
  limparEpesquisar()
})

onMounted(() => {
  limparEpesquisar()
})

</script>

<style scoped>
.gerenciar-cadastros {
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

[data-theme="dark"] .contexto-banner {
  background: rgba(19, 81, 180, 0.15);
  border-left-color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme="dark"] .contexto-banner__icon,
[data-theme="dark"] .contexto-banner__valor {
  color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme="dark"] .contexto-banner__label {
  color: rgba(255, 255, 255, 0.6);
}

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

@media (max-width: 575px) {
  .titulo-pagina__h1 {
    font-size: 1.25rem;
  }

  .titulo-pagina__subtitulo {
    font-size: 0.875rem;
  }

  .titulo-pagina__topo {
    flex-direction: column;
  }
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
}

.painel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 999;
}

.painel-cadastro {
  position: fixed;
  top: 0;
  right: 0;
  width: 100%;
  max-width: 100%;
  height: 100vh;
  background: var(--background, #fff);
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow-y: auto;
}

.painel-cadastro-inner {
  padding: 1rem;
}

.formulario-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.formulario-titulo {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  text-transform: uppercase;
}

.formulario-secao {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-secondary-04, #ddd);
}

.formulario-secao .secao-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
}

.secao-subtitulo {
  font-size: 0.875rem;
  color: var(--color-secondary-07, #555);
  margin: 0 0 1rem;
}

.formulario-acoes {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
}

.painel-fade-enter-active,
.painel-fade-leave-active {
  transition: opacity 0.3s ease;
}

.painel-fade-enter-from,
.painel-fade-leave-to {
  opacity: 0;
}

.painel-slide-enter-active,
.painel-slide-leave-active {
  transition: transform 0.3s ease;
}

.painel-slide-enter-from,
.painel-slide-leave-to {
  transform: translateX(100%);
}

@media (min-width: 576px) {
  .painel-cadastro {
    width: 75%;
  }
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
  padding: 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--color-secondary-03, #eee);
  border-top-color: var(--color-primary-default, #1351b4);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.table-responsive {
  overflow-x: auto;
}

.tabela-solicitacoes th.th-bold {
  font-weight: 700;
}

.tabela-solicitacoes th:nth-child(1),
.tabela-solicitacoes td:nth-child(1) {
  white-space: nowrap;
  min-width: 9.25rem;
}

.tabela-solicitacoes td:nth-child(7) .br-tag {
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}

.th-sort-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border: none;
  background: transparent;
  padding: 0;
  color: inherit;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
}

.th-sort-btn:focus-visible {
  outline: 2px solid var(--color-primary-default, #1351b4);
  outline-offset: 2px;
  border-radius: 2px;
}

.th-sort-icon {
  font-size: 0.75rem;
  color: var(--color-secondary-07, #555);
}


</style>
