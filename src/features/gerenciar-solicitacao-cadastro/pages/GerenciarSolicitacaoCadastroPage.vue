<template>
  <DefaultLayout>
    <section class="gerenciar-cadastros" :class="{ 'painel-aberto': painelCadastroAberto }">
      <div class="titulo-pagina">
        <div class="titulo-pagina__topo">
          <div>
            <h1 id="titulo-gerenciar" class="titulo-pagina__h1">
              Gerenciar solicitação de cadastros no sistema
            </h1>
            <p class="titulo-pagina__subtitulo">Aplique filtros e clique em Listar.</p>
          </div>
          <button
            class="br-button primary small"
            type="button"
            @click="abrirPainelCadastro"
            aria-label="Cadastrar usuário"
          >
            Cadastrar usuário
          </button>
        </div>
      </div>

      <Card custom-class="mb-4">
        <FiltrosGerenciarSolicitacao
          :carregando="carregando"
          @pesquisar="aplicarFiltros"
          @limpar="limparEpesquisar"
        />
      </Card>

      <Card custom-class="mb-4">

        <div v-if="carregando" class="br-loading p-4" role="status" aria-live="polite">
          <div class="loading-spinner" aria-hidden="true"></div>
          <p class="mt-2">Carregando solicitações...</p>
        </div>

        <div v-else-if="solicitacoes.length === 0" class="p-4 text-center text-muted">
          <i class="fas fa-inbox fa-3x mb-3" aria-hidden="true"></i>
          <p>Nenhuma solicitação encontrada.</p>
          <p class="small">Ajuste os filtros ou cadastre uma nova solicitação.</p>
        </div>

        <div v-else class="table-responsive">
          <table class="br-table" role="table">
            <thead>
              <tr>
                <th scope="col">CPF</th>
                <th scope="col">Nome completo</th>
                <th scope="col">Esfera de atuação</th>
                <th scope="col">UF</th>
                <th scope="col">Município</th>
                <th scope="col">Órgão</th>
                <th scope="col">Situação</th>
                <th scope="col">Ações</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in solicitacoes" :key="s.id">
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
                    :aria-label="rotuloBotaoDetalhar(s.status)"
                  >
                    {{ rotuloBotaoDetalhar(s.status) }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>

      <Modal
        v-if="modalDetalhar"
        title="Detalhes da solicitação"
        @close="fecharModalDetalhar"
      >
        <div v-if="detalheSelecionado" class="detalhe-solicitacao">
          <dl class="detalhe-lista">
            <div class="detalhe-item">
              <dt>Nome</dt>
              <dd>{{ detalheSelecionado.nome }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>E-mail institucional</dt>
              <dd>{{ detalheSelecionado.email_institucional }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Telefone institucional</dt>
              <dd>{{ detalheSelecionado.telefone_institucional }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Telefone pessoal</dt>
              <dd>{{ detalheSelecionado.telefone_pessoal || '—' }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Esfera</dt>
              <dd>{{ labelEsfera(detalheSelecionado.esfera_atuacao) }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>UF</dt>
              <dd>{{ detalheSelecionado.uf }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Município</dt>
              <dd>{{ detalheSelecionado.municipio }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Órgão</dt>
              <dd>{{ detalheSelecionado.orgao }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Cargo</dt>
              <dd>{{ detalheSelecionado.cargo }}</dd>
            </div>
            <div class="detalhe-item">
              <dt>Status</dt>
              <dd>
                <span class="br-tag" :class="classeStatus(detalheSelecionado.status)">
                  {{ labelStatus(detalheSelecionado.status) }}
                </span>
              </dd>
            </div>
            <div class="detalhe-item">
              <dt>Data de cadastro</dt>
              <dd>{{ formatarData(detalheSelecionado.created_at) }}</dd>
            </div>
          </dl>
        </div>
      </Modal>

      <Transition name="painel-fade">
        <div
          v-if="painelCadastroAberto"
          class="painel-overlay"
          aria-hidden="true"
          @click="fecharPainelCadastro"
        ></div>
      </Transition>
      <Transition name="painel-slide">
        <aside
          v-if="painelCadastroAberto"
          class="painel-cadastro"
          aria-label="Formulário cadastrar usuário"
        >
          <FormularioCadastrarUsuario
            @voltar="fecharPainelCadastro"
            @confirmar="onConfirmarCadastro"
          />
        </aside>
      </Transition>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Card from '@/core/components/Card/Card.vue'
import Modal from '@/core/components/Modal/Modal.vue'
import FiltrosGerenciarSolicitacao from '../components/FiltrosGerenciarSolicitacao.vue'
import FormularioCadastrarUsuario from '../components/FormularioCadastrarUsuario.vue'
import {
  listarSolicitacoesGerenciar,
  type SolicitacaoGerenciarItem,
  type FiltrosGerenciarSolicitacao as FiltrosGerenciarSolicitacaoType,
} from '@/services/GerenciarSolicitacaoCadastroService'
import {
  obterSolicitacaoCadastro,
  type SolicitacaoCadastroDetalhe,
} from '@/services/SolicitacaoCadastroService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'GerenciarSolicitacaoCadastroPage' })

const router = useRouter()
const { error, success } = useNotification()

const solicitacoes = ref<SolicitacaoGerenciarItem[]>([])
const carregando = ref(false)
const filtrosAtivos = ref<FiltrosGerenciarSolicitacaoType>({})
const modalDetalhar = ref(false)
const detalheSelecionado = ref<SolicitacaoCadastroDetalhe | null>(null)
const painelCadastroAberto = ref(false)

async function carregarSolicitacoes() {
  carregando.value = true
  try {
    solicitacoes.value = await listarSolicitacoesGerenciar(filtrosAtivos.value)
  } catch {
    solicitacoes.value = []
    error('Não foi possível carregar as solicitações. Verifique se o backend está em execução.')
  } finally {
    carregando.value = false
  }
}

function aplicarFiltros(filtros: FiltrosGerenciarSolicitacaoType) {
  filtrosAtivos.value = filtros
  carregarSolicitacoes()
}

function limparEpesquisar() {
  filtrosAtivos.value = {}
  carregarSolicitacoes()
}

function abrirPainelCadastro() {
  painelCadastroAberto.value = true
}

function fecharPainelCadastro() {
  painelCadastroAberto.value = false
}

function onConfirmarCadastro(_values: Record<string, unknown>) {
  // TODO: integrar com API de cadastro de usuário
  success('Cadastro enviado. Aguarde a integração com o backend.')
  fecharPainelCadastro()
  carregarSolicitacoes()
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

async function detalhar(s: SolicitacaoGerenciarItem) {
  try {
    detalheSelecionado.value = await obterSolicitacaoCadastro(s.id)
    modalDetalhar.value = true
  } catch {
    error('Erro ao carregar detalhes da solicitação.')
  }
}

function fecharModalDetalhar() {
  modalDetalhar.value = false
  detalheSelecionado.value = null
}

onMounted(() => {
  carregarSolicitacoes()
})
</script>

<style scoped>
.gerenciar-cadastros {
  padding: 1.5rem 0;
  position: relative;
  display: flex;
  flex-direction: column;
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

.detalhe-lista {
  list-style: none;
  padding: 0;
  margin: 0;
}

.detalhe-item {
  display: flex;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--color-secondary-03, #eee);
}

.detalhe-item dt {
  font-weight: 600;
  min-width: 140px;
  margin: 0;
}

.detalhe-item dd {
  margin: 0;
  flex: 1;
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
</style>
