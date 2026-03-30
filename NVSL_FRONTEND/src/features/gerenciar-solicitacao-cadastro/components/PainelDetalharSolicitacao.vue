<template>
  <div class="painel-detalhar-solicitacao">
    <div class="painel-header">
      <div class="painel-header-topo">
        <h2 class="painel-titulo">Detalhar/Avaliar Solicitação de Cadastro</h2>
        <button
          class="br-button secondary small"
          type="button"
          @click="$emit('voltar')"
          aria-label="Voltar"
        >
          Voltar
        </button>
      </div>

      <!-- Cabeçalho: Status e Perfis Vinculados -->
      <div v-if="detalhe" class="painel-cabecalho-info">
        <div class="painel-info-item">
          <span class="painel-info-label">Status da Solicitação</span>
          <span class="painel-status-badge" :class="classeStatusBadge(detalhe.status)">
            <span class="status-indicador" :class="'status-' + detalhe.status"></span>
            {{ labelStatus(detalhe.status) }}
          </span>
        </div>
        <div class="painel-info-item">
          <span class="painel-info-label">Perfis Vinculados</span>
          <span class="painel-perfis-badge" :class="temPerfisVinculados ? 'com-perfis' : 'sem-perfis'">
            <span class="perfis-indicador" :class="temPerfisVinculados ? 'com' : 'sem'"></span>
            {{ temPerfisVinculados ? `${perfisVinculadosCount} PERFIL(IS) VINCULADO(S)` : 'SEM PERFIS VINCULADOS' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Bloco: Aguardando Avaliação (somente quando em_analise e usuário tem privilégio) -->
    <div v-if="detalhe?.status === 'em_analise' && detalhe?.pode_avaliar !== false" class="painel-secao">
      <h3 class="secao-titulo">Aguardando Avaliação</h3>
      <div class="secao-aguardando-avaliacao">
        <div class="secao-linha-3cols">
          <div class="br-input">
            <label>Esfera de atuação</label>
            <input type="text" :value="labelEsfera(detalhe.esfera_atuacao)" readonly />
          </div>
          <div class="br-input">
            <label>UF</label>
            <input type="text" :value="detalhe.uf" readonly />
          </div>
          <div class="br-input">
            <label>Município</label>
            <input type="text" :value="detalhe.municipio" readonly />
          </div>
        </div>
        <div class="secao-linha-orgao-cargo">
          <div class="br-input orgao-maior">
            <label>Órgão de atuação</label>
            <input type="text" :value="detalhe.orgao" readonly />
          </div>
          <div class="br-input cargo-menor">
            <label>Cargo/Função</label>
            <input type="text" :value="detalhe.cargo" readonly />
          </div>
        </div>
        <div v-if="detalhe.vigencia_inicio_solicitada || detalhe.vigencia_fim_solicitada" class="secao-linha-3cols">
          <div class="br-input">
            <label>Vigência informada na solicitação (início)</label>
            <input
              type="text"
              :value="detalhe.vigencia_inicio_solicitada ? formatarDataExibicao(detalhe.vigencia_inicio_solicitada) : '—'"
              readonly
            />
          </div>
          <div class="br-input">
            <label>Vigência informada na solicitação (fim)</label>
            <input
              type="text"
              :value="detalhe.vigencia_fim_solicitada ? formatarDataExibicao(detalhe.vigencia_fim_solicitada) : '—'"
              readonly
            />
          </div>
        </div>
        <div class="secao-linha-3cols secao-avaliacao-campos">
          <div class="br-select mb-2 perfil-select">
            <label for="perfil-selecao">Perfil</label>
            <select
              id="perfil-selecao"
              v-model="perfilSelecionado"
              :disabled="opcoesPerfil.length === 0"
            >
              <option :value="null" disabled>Selecione o perfil</option>
              <option
                v-for="opcao in opcoesPerfil"
                :key="String(opcao.value)"
                :value="opcao.value"
              >
                {{ opcao.label }}
              </option>
            </select>
          </div>
          <div class="br-input">
            <label for="vigencia-inicio">Vigência (inicial)</label>
            <input
              id="vigencia-inicio"
              type="date"
              v-model="vigenciaInicio"
            />
          </div>
          <div class="br-input">
            <label for="vigencia-fim">Vigência (fim)</label>
            <input
              id="vigencia-fim"
              type="date"
              v-model="vigenciaFim"
            />
          </div>
        </div>
      </div>
      <div class="secao-acoes">
        <button
          class="br-button danger"
          type="button"
          @click="abrirModalReprovar"
          :disabled="avaliando"
        >
          Reprovar
        </button>
        <button
          class="br-button primary"
          type="button"
          @click="$emit('aprovar', { perfilId: perfilSelecionado, vigenciaInicio, vigenciaFim })"
          :disabled="avaliando || !perfilSelecionado"
        >
          Aprovar
        </button>
      </div>
    </div>

    <!-- Bloco: Dados do Solicitante -->
    <div class="painel-secao">
      <h3 class="secao-titulo">Dados do Solicitante</h3>
      <div class="secao-dados-solicitante-layout">
        <div class="secao-linha-3cols">
          <div class="br-input">
            <label>Nome</label>
            <input type="text" :value="detalhe?.nome" readonly />
          </div>
          <div class="br-input">
            <label>CPF</label>
            <input type="text" :value="detalhe?.cpf ?? '***.***.***-**'" readonly />
          </div>
          <div class="br-input">
            <label>E-mail Institucional</label>
            <input type="text" :value="detalhe?.email_institucional" readonly />
          </div>
        </div>
        <div class="secao-linha-2cols">
          <div class="br-input">
            <label>Telefone Institucional</label>
            <input type="text" :value="formatarTelefone(detalhe?.telefone_institucional)" readonly />
          </div>
          <div class="br-input">
            <label>Telefone pessoal</label>
            <input
              type="text"
              :value="detalhe?.telefone_pessoal ? formatarTelefone(detalhe.telefone_pessoal) : '—'"
              readonly
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Histórico de reprovação (status reprovado) — abaixo dos dados do solicitante -->
    <div v-if="detalhe?.status === 'reprovado'" class="painel-secao painel-secao--historico-reprovacao">
      <h3 class="secao-titulo">Histórico de reprovação</h3>
      <p class="secao-descricao">
        Registro das decisões de reprovação com data e motivo informado pelo avaliador.
      </p>
      <div v-if="historicoReprovacoes.length === 0" class="br-message warning" role="status">
        <div class="content">Nenhum motivo de reprovação registrado para esta solicitação.</div>
      </div>
      <div v-else class="table-responsive">
        <table
          class="br-table tabela-historico-reprovacao"
          role="table"
          aria-label="Histórico de reprovações da solicitação"
        >
          <thead>
            <tr>
              <th scope="col" class="th-bold">Data</th>
              <th scope="col" class="th-bold">Motivo</th>
              <th scope="col" class="th-bold">Responsável pela avaliação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in historicoReprovacoes" :key="`reprov-${idx}-${item.data}`">
              <td>{{ formatarDataHoraPtBr(item.data) }}</td>
              <td class="td-motivo-reprovacao">{{ item.motivo }}</td>
              <td>{{ item.avaliador ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Perfis Vinculados — visível quando solicitação aprovada -->
    <div v-if="detalhe?.status === 'aprovado'" class="painel-secao perfis-vinculados-secao">
      <div class="perfis-vinculados-header">
        <div>
          <h3 class="perfis-vinculados-titulo">Perfis vinculados</h3>
          <p class="perfis-vinculados-subtitulo">
            Um usuário pode ter vários perfis. Cada vínculo possui vigência, status e contexto de atuação.
          </p>
        </div>
      </div>
      <div v-if="!temPerfisVinculados" class="perfis-vinculados-vazio">
        <i class="fas fa-users fa-2x mb-2" aria-hidden="true"></i>
        <p>Nenhum perfil vinculado. Clique em <strong>Adicionar Perfil</strong> para vincular um novo perfil ao usuário.</p>
      </div>
      <div v-else class="table-responsive">
        <table class="br-table tabela-perfis" role="table">
          <thead>
            <tr>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('perfil')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('perfil')">
                  Perfil <span class="th-sort-icon">{{ obterIndicadorSort('perfil') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('vigencia_inicio')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('vigencia_inicio')">
                  Vig. início <span class="th-sort-icon">{{ obterIndicadorSort('vigencia_inicio') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('vigencia_fim')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('vigencia_fim')">
                  Vig. fim <span class="th-sort-icon">{{ obterIndicadorSort('vigencia_fim') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('vigente')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('vigente')">
                  Status <span class="th-sort-icon">{{ obterIndicadorSort('vigente') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('esfera')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('esfera')">
                  Esfera <span class="th-sort-icon">{{ obterIndicadorSort('esfera') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('uf')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('uf')">
                  UF <span class="th-sort-icon">{{ obterIndicadorSort('uf') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('municipio')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('municipio')">
                  Município <span class="th-sort-icon">{{ obterIndicadorSort('municipio') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('orgao')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('orgao')">
                  Órgão <span class="th-sort-icon">{{ obterIndicadorSort('orgao') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('cargo')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('cargo')">
                  Cargo/Função <span class="th-sort-icon">{{ obterIndicadorSort('cargo') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, idx) in perfisVinculadosPaginados" :key="`perfil-${idx}-${p.id ?? idx}`">
              <td>{{ p.perfil }}</td>
              <td>{{ formatarDataExibicao(p.vigencia_inicio) }}</td>
              <td>{{ formatarDataExibicao(p.vigencia_fim) }}</td>
              <td>
                <span class="br-tag" :class="p.vigente ? 'success' : 'danger'">
                  {{ p.vigente ? 'Ativo' : 'Inativo' }}
                </span>
              </td>
              <td>{{ p.esfera }}</td>
              <td>{{ p.uf }}</td>
              <td>{{ p.municipio }}</td>
              <td>{{ p.orgao }}</td>
              <td>{{ p.cargo }}</td>
              <td>
                <button
                  class="br-button secondary small"
                  type="button"
                  @click="$emit('toggle-perfil', { perfilUsuarioId: p.id, acao: p.vigente ? 'desativar' : 'ativar' })"
                >
                  {{ p.vigente ? 'Desativar' : 'Ativar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <PaginationControls
        v-if="perfisVinculadosOrdenados.length > 0"
        v-model:currentPage="paginaAtualPerfis"
        v-model:pageSize="itensPorPaginaPerfis"
        :total-items="perfisVinculadosOrdenados.length"
      />
      <div class="perfis-vinculados-acoes">
        <button
          class="br-button primary small"
          type="button"
          @click="abrirModalAdicionarPerfil"
          :disabled="opcoesPerfilDisponiveis.length === 0"
          aria-label="Adicionar perfil"
        >
          Adicionar Perfil
        </button>
      </div>
    </div>

    <!-- Modal Adicionar Perfil -->
    <Modal
      v-if="modalAdicionarPerfilVisivel"
      title="Adicionar Perfil"
      :show-actions="false"
      @close="fecharModalAdicionarPerfil"
    >
      <form @submit.prevent="onSubmitAdicionarPerfil" class="form-modal-adicionar-perfil">
        <div class="form-modal-linha">
          <div class="br-select mb-3 form-modal-perfil">
            <label for="modal-perfil">Perfil</label>
            <select
              id="modal-perfil"
              v-model="formAdicionarPerfil.perfilId"
              required
            >
              <option :value="null" disabled>Selecione o perfil</option>
              <option
                v-for="op in opcoesPerfilDisponiveis"
                :key="String(op.value)"
                :value="op.value"
              >
                {{ op.label }}
              </option>
            </select>
          </div>
        </div>
        <div class="form-modal-linha form-modal-vigencias">
          <div class="br-input mb-3">
            <label for="modal-vigencia-inicio">Vigência (início)</label>
            <input
              id="modal-vigencia-inicio"
              type="date"
              v-model="formAdicionarPerfil.vigenciaInicio"
            />
          </div>
          <div class="br-input mb-3">
            <label for="modal-vigencia-fim">Vigência (fim)</label>
            <input
              id="modal-vigencia-fim"
              type="date"
              v-model="formAdicionarPerfil.vigenciaFim"
              :min="formAdicionarPerfil.vigenciaInicio || undefined"
            />
          </div>
        </div>
        <div v-if="erroAdicionarPerfil" class="br-message danger mb-3" role="alert">
          <div class="content">{{ erroAdicionarPerfil }}</div>
        </div>
        <div class="form-modal-acoes">
          <button class="br-button secondary" type="button" @click="fecharModalAdicionarPerfil">
            Cancelar
          </button>
          <button
            class="br-button primary"
            type="submit"
            :disabled="!formAdicionarPerfil.perfilId"
          >
            Adicionar
          </button>
        </div>
      </form>
    </Modal>

    <!-- Modal Reprovar com Justificativa -->
    <Modal
      v-if="modalReprovarVisivel"
      title="Reprovar Solicitação"
      :show-actions="false"
      @close="fecharModalReprovar"
    >
      <form @submit.prevent="onSubmitReprovar" class="form-modal-reprovar">
        <p class="mb-3">Informe o motivo da reprovação. Esta informação ficará registrada no sistema.</p>
        <div class="br-textarea mb-3">
          <label for="justificativa-reprovacao">Justificativa <span class="text-red-50">*</span></label>
          <textarea
            id="justificativa-reprovacao"
            v-model="justificativaReprovacao"
            rows="4"
            placeholder="Descreva o motivo da reprovação (mínimo 10 caracteres)..."
            required
            minlength="10"
            maxlength="1000"
          ></textarea>
          <span class="input-hint">{{ justificativaReprovacao.length }}/1000 caracteres</span>
        </div>
        <div v-if="erroReprovar" class="br-message danger mb-3" role="alert">
          <div class="content">{{ erroReprovar }}</div>
        </div>
        <div class="form-modal-acoes">
          <button class="br-button secondary" type="button" @click="fecharModalReprovar">Cancelar</button>
          <button
            class="br-button danger"
            type="submit"
            :disabled="justificativaReprovacao.trim().length < 10"
          >
            Confirmar Reprovação
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue'
import { usePerfis } from '@/core/composables/usePerfis'
import Modal from '@/core/components/Modal/Modal.vue'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'
import type {
  SolicitacaoCadastroDetalhe,
  PerfilVinculado,
  HistoricoReprovacaoItem,
} from '@/services/SolicitacaoCadastroService'

defineOptions({ name: 'PainelDetalharSolicitacao' })

const props = withDefaults(
  defineProps<{
    detalhe: SolicitacaoCadastroDetalhe & { cpf?: string } | null
    avaliando?: boolean
  }>(),
  {}
)

const emit = defineEmits<{
  (e: 'voltar'): void
  (e: 'aprovar', payload: { perfilId: string | number | null; vigenciaInicio: string; vigenciaFim: string }): void
  (e: 'reprovar', payload: { justificativa: string }): void
  (e: 'toggle-perfil', payload: { perfilUsuarioId: number; acao: 'ativar' | 'desativar' }): void
  (e: 'adicionar-perfil', payload: { perfilId: number | string; vigenciaInicio?: string; vigenciaFim?: string }): void
}>()

const historicoReprovacoes = computed<HistoricoReprovacaoItem[]>(() => {
  const raw = props.detalhe?.historico_reprovacoes
  return Array.isArray(raw) ? raw : []
})

const perfilSelecionado = ref<string | number | null>(null)
const vigenciaInicio = ref('')
const vigenciaFim = ref('')
const { opcoesPerfil, carregarPerfis } = usePerfis()

type PerfilVinculadoExibicao = PerfilVinculado & { id: number }
type ColunaOrdenacao =
  | 'perfil'
  | 'vigencia_inicio'
  | 'vigencia_fim'
  | 'vigente'
  | 'esfera'
  | 'uf'
  | 'municipio'
  | 'orgao'
  | 'cargo'
type DirecaoOrdenacao = 'asc' | 'desc'

const perfisVinculadosLista = computed<PerfilVinculadoExibicao[]>(() => {
  const raw = props.detalhe?.perfis_vinculados
  if (!Array.isArray(raw) || raw.length === 0) return []
  return raw.map((p, i) => ({
    id: p.id ?? (props.detalhe?.id ?? 0) * 1000 + i,
    perfil: p.perfil ?? '—',
    vigencia_inicio: p.vigencia_inicio ?? '—',
    vigencia_fim: p.vigencia_fim ?? '—',
    vigente: Boolean(p.vigente),
    esfera: p.esfera ?? '—',
    uf: p.uf ?? '—',
    municipio: p.municipio ?? '—',
    orgao: p.orgao ?? '—',
    cargo: p.cargo ?? '—',
  }))
})

const perfisVinculadosCount = computed(() => perfisVinculadosLista.value.length)
const temPerfisVinculados = computed(() => perfisVinculadosCount.value > 0)
const colunaOrdenacao = ref<ColunaOrdenacao | null>(null)
const direcaoOrdenacao = ref<DirecaoOrdenacao>('asc')
const paginaAtualPerfis = ref(1)
const itensPorPaginaPerfis = ref(10)
const perfisVinculadosOrdenados = computed<PerfilVinculadoExibicao[]>(() => {
  const lista = [...perfisVinculadosLista.value]
  if (!colunaOrdenacao.value) return lista

  const coluna = colunaOrdenacao.value
  const direcao = direcaoOrdenacao.value === 'asc' ? 1 : -1

  return lista.sort((a, b) => compararValores(a, b, coluna) * direcao)
})
const perfisVinculadosPaginados = computed<PerfilVinculadoExibicao[]>(() => {
  const inicio = (paginaAtualPerfis.value - 1) * itensPorPaginaPerfis.value
  const fim = inicio + itensPorPaginaPerfis.value
  return perfisVinculadosOrdenados.value.slice(inicio, fim)
})

const modalAdicionarPerfilVisivel = ref(false)
const erroAdicionarPerfil = ref('')
const formAdicionarPerfil = reactive<{
  perfilId: string | number | null
  vigenciaInicio: string
  vigenciaFim: string
}>({
  perfilId: null,
  vigenciaInicio: '',
  vigenciaFim: '',
})

const opcoesPerfilDisponiveis = computed(() => {
  const perfisJaVinculados = new Set(
    perfisVinculadosLista.value.map((p) => String(p.perfil).toLowerCase())
  )
  return opcoesPerfil.value.filter(
    (op) => !perfisJaVinculados.has(String(op.label).toLowerCase())
  )
})

function abrirModalAdicionarPerfil() {
  const hoje = new Date().toISOString().slice(0, 10)
  formAdicionarPerfil.perfilId = null
  formAdicionarPerfil.vigenciaInicio = hoje
  formAdicionarPerfil.vigenciaFim = ''
  erroAdicionarPerfil.value = ''
  modalAdicionarPerfilVisivel.value = true
}

function fecharModalAdicionarPerfil() {
  modalAdicionarPerfilVisivel.value = false
}

function onSubmitAdicionarPerfil() {
  if (!formAdicionarPerfil.perfilId) return
  erroAdicionarPerfil.value = ''
  emit('adicionar-perfil', {
    perfilId: formAdicionarPerfil.perfilId,
    vigenciaInicio: formAdicionarPerfil.vigenciaInicio || undefined,
    vigenciaFim: formAdicionarPerfil.vigenciaFim || undefined,
  })
  fecharModalAdicionarPerfil()
}

const modalReprovarVisivel = ref(false)
const justificativaReprovacao = ref('')
const erroReprovar = ref('')

function abrirModalReprovar() {
  justificativaReprovacao.value = ''
  erroReprovar.value = ''
  modalReprovarVisivel.value = true
}

function fecharModalReprovar() {
  modalReprovarVisivel.value = false
}

function onSubmitReprovar() {
  if (justificativaReprovacao.value.trim().length < 10) {
    erroReprovar.value = 'A justificativa deve ter pelo menos 10 caracteres.'
    return
  }
  erroReprovar.value = ''
  emit('reprovar', { justificativa: justificativaReprovacao.value.trim() })
  fecharModalReprovar()
}

onMounted(async () => {
  await carregarPerfis()
})

function toInputDate(s?: string | null): string {
  if (!s) return ''
  const t = String(s).trim()
  return t.length >= 10 ? t.slice(0, 10) : t
}

watch(
  () => props.detalhe,
  async (novo) => {
    if (novo) {
      await carregarPerfis()
      const hoje = new Date().toISOString().slice(0, 10)
      vigenciaInicio.value = toInputDate(novo.vigencia_inicio_solicitada) || hoje
      vigenciaFim.value = toInputDate(novo.vigencia_fim_solicitada) || ''
      perfilSelecionado.value =
        novo.perfil_id_solicitado != null && novo.perfil_id_solicitado > 0 ? novo.perfil_id_solicitado : null
      paginaAtualPerfis.value = 1
    }
  },
  { immediate: true }
)

function formatarDataExibicao(val: string) {
  if (!val || val === '—') return '—'
  try {
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return val
  }
}

/** Data e hora em pt-BR (histórico de reprovação). */
function formatarDataHoraPtBr(val: string | null | undefined) {
  if (!val) return '—'
  try {
    const d = new Date(val)
    if (isNaN(d.getTime())) return val
    return d.toLocaleString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return val
  }
}

function labelEsfera(esfera?: string) {
  const map: Record<string, string> = {
    federal: 'Federal',
    estadual: 'Estadual',
    municipal: 'Municipal',
  }
  return map[esfera ?? ''] ?? esfera ?? '—'
}

function labelStatus(status: string) {
  const map: Record<string, string> = {
    em_analise: 'EM ANÁLISE',
    aprovado: 'APROVADA',
    reprovado: 'REPROVADA',
  }
  return map[status] ?? status
}

function classeStatusBadge(status: string) {
  const map: Record<string, string> = {
    em_analise: 'status-em-analise',
    aprovado: 'status-aprovada',
    reprovado: 'status-reprovada',
  }
  return map[status] ?? ''
}

function formatarTelefone(tel?: string) {
  if (!tel) return ''
  const d = tel.replace(/\D/g, '')
  if (d.length === 11) {
    return `(${d.slice(0, 2)}) ${d.slice(2, 7)}-${d.slice(7)}`
  }
  if (d.length === 10) {
    return `(${d.slice(0, 2)}) ${d.slice(2, 6)}-${d.slice(6)}`
  }
  return tel
}

function alternarOrdenacao(coluna: ColunaOrdenacao) {
  if (colunaOrdenacao.value === coluna) {
    direcaoOrdenacao.value = direcaoOrdenacao.value === 'asc' ? 'desc' : 'asc'
    return
  }
  colunaOrdenacao.value = coluna
  direcaoOrdenacao.value = 'asc'
}

function obterAriaSort(coluna: ColunaOrdenacao) {
  if (colunaOrdenacao.value !== coluna) return 'none'
  return direcaoOrdenacao.value === 'asc' ? 'ascending' : 'descending'
}

function obterIndicadorSort(coluna: ColunaOrdenacao) {
  if (colunaOrdenacao.value !== coluna) return '↕'
  return direcaoOrdenacao.value === 'asc' ? '↑' : '↓'
}

function normalizarTexto(valor: unknown): string {
  return String(valor ?? '')
    .trim()
    .toLocaleLowerCase('pt-BR')
}

function normalizarData(valor: string): number {
  if (!valor || valor === '—') return -1
  const data = new Date(valor).getTime()
  return Number.isNaN(data) ? -1 : data
}

function compararTexto(a: unknown, b: unknown): number {
  return normalizarTexto(a).localeCompare(normalizarTexto(b), 'pt-BR', { sensitivity: 'base' })
}

function compararValores(a: PerfilVinculadoExibicao, b: PerfilVinculadoExibicao, coluna: ColunaOrdenacao): number {
  switch (coluna) {
    case 'vigencia_inicio':
      return normalizarData(a.vigencia_inicio) - normalizarData(b.vigencia_inicio)
    case 'vigencia_fim':
      return normalizarData(a.vigencia_fim) - normalizarData(b.vigencia_fim)
    case 'vigente':
      return Number(a.vigente) - Number(b.vigente)
    case 'perfil':
    case 'esfera':
    case 'uf':
    case 'municipio':
    case 'orgao':
    case 'cargo':
      return compararTexto(a[coluna], b[coluna])
    default:
      return 0
  }
}
</script>

<style scoped>
.painel-detalhar-solicitacao {
  padding: 1rem;
  color: var(--color-secondary-08, #333);
}

.painel-header {
  margin-bottom: 1.5rem;
}

.painel-header-topo {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.painel-titulo {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
}

.painel-cabecalho-info {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem 1.5rem;
}

.painel-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.painel-info-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-secondary-07, #555);
}

.painel-status-badge,
.painel-perfis-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.7rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.status-indicador,
.perfis-indicador {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.status-indicador.status-em_analise,
.perfis-indicador.sem {
  background: #f0ad4e;
}

.status-indicador.status-aprovado,
.perfis-indicador.com {
  background: #5cb85c;
}

.status-indicador.status-reprovado {
  background: #d9534f;
}

.painel-status-badge.status-em-analise {
  background: #fff8e6;
  color: #b8860b;
}

.painel-status-badge.status-aprovada {
  background: #e8f5e9;
  color: #2e7d32;
}

.painel-status-badge.status-reprovada {
  background: #ffebee;
  color: #c62828;
}

.painel-perfis-badge.sem-perfis {
  background: var(--color-secondary-02, #f0f0f0);
  color: var(--color-secondary-07, #555);
}

.painel-perfis-badge.com-perfis {
  background: #e8f5e9;
  color: #2e7d32;
}

.text-muted {
  color: var(--color-secondary-06, #888);
}

.painel-secao {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--color-secondary-04, #ddd);
}

.painel-secao:last-child {
  border-bottom: none;
}

.secao-titulo {
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0 0 0.75rem;
}

.secao-descricao {
  font-size: 0.875rem;
  color: var(--color-secondary-07, #555);
  margin: 0 0 1rem;
  line-height: 1.5;
}

.painel-secao--historico-reprovacao .tabela-historico-reprovacao th.th-bold {
  font-weight: 700;
}

.tabela-historico-reprovacao td {
  vertical-align: top;
}

.tabela-historico-reprovacao .td-motivo-reprovacao {
  white-space: pre-wrap;
  word-break: break-word;
  max-width: 28rem;
}

.secao-grid-readonly,
.secao-grid-dados {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

/* Aguardando Avaliação: linha 1 = Esfera, UF, Município | linha 2 = Órgão (maior), Cargo | linha 3 = Perfil, Vigências */
.secao-aguardando-avaliacao {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.secao-dados-solicitante-layout {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.secao-linha-3cols {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.secao-linha-2cols {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.secao-linha-orgao-cargo {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
}

.secao-linha-orgao-cargo .orgao-maior {
  min-width: 0;
}

.secao-linha-orgao-cargo .cargo-menor {
  min-width: 0;
}

.secao-avaliacao-campos {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.perfil-select {
  position: relative;
}

.perfil-select select {
  width: 100%;
  min-height: 2.75rem;
  padding: 0.75rem 2.5rem 0.75rem 0.75rem;
  border: 1px solid var(--color-secondary-05, #9e9d9d);
  border-radius: 0.25rem;
  background-color: #fff;
  color: var(--color-secondary-09, #1b1b1b);
  font-size: 1rem;
  line-height: 1.25rem;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.perfil-select::after {
  content: '';
  position: absolute;
  right: 0.875rem;
  top: calc(50% + 0.5rem);
  width: 0.625rem;
  height: 0.625rem;
  border-right: 2px solid var(--color-secondary-07, #555);
  border-bottom: 2px solid var(--color-secondary-07, #555);
  transform: translateY(-50%) rotate(45deg);
  pointer-events: none;
}

.perfil-select select:focus {
  outline: none;
  border-color: var(--color-primary-default, #1351b4);
  box-shadow: 0 0 0 1px var(--color-primary-default, #1351b4);
}

.perfil-select select:disabled {
  background-color: var(--color-secondary-02, #f0f0f0);
  color: var(--color-secondary-06, #888);
  cursor: not-allowed;
}

.secao-acoes {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 1rem;
}

/* Perfis Vinculados — layout igual ao anexo */
.perfis-vinculados-secao {
  margin-bottom: 2rem;
}

.perfis-vinculados-titulo {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-secondary-08, #333);
  margin: 0 0 0.25rem;
}

.perfis-vinculados-subtitulo {
  font-size: 0.9375rem;
  font-weight: 400;
  color: var(--color-secondary-08, #333);
  margin: 0;
  line-height: 1.4;
}

.perfis-vinculados-header {
  margin-bottom: 1rem;
}

.perfis-vinculados-acoes {
  margin-top: 1rem;
  display: flex;
  justify-content: flex-end;
}

.perfis-vinculados-vazio {
  padding: 2rem;
  text-align: center;
  background: var(--color-secondary-01, #f8f8f8);
  border-radius: 8px;
  color: var(--color-secondary-07, #555);
}

.perfis-vinculados-vazio i {
  display: block;
  color: var(--color-secondary-05, #999);
}

.form-modal-adicionar-perfil .form-modal-linha {
  margin-bottom: 1rem;
}

.form-modal-adicionar-perfil .form-modal-vigencias {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-modal-adicionar-perfil .form-modal-perfil select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-family: inherit;
}

.form-modal-adicionar-perfil .form-modal-acoes,
.form-modal-reprovar .form-modal-acoes {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-secondary-04, #ddd);
}

.form-modal-reprovar textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  font-family: inherit;
  font-size: 0.875rem;
  border: 1px solid var(--color-secondary-04, #ccc);
  border-radius: 6px;
  resize: vertical;
}

.form-modal-reprovar .input-hint {
  display: block;
  font-size: 0.75rem;
  color: var(--color-secondary-06, #888);
  margin-top: 0.25rem;
}

/* Padrão igual à tabela de Gerenciar solicitação de cadastros */
.table-responsive .tabela-perfis {
  width: 100%;
  table-layout: fixed;
  font-size: 0.875rem;
}

.table-responsive {
  overflow-x: hidden;
  padding-right: 0;
}

.tabela-perfis th,
.tabela-perfis td {
  padding: 0.5rem 0.4rem;
  vertical-align: middle;
  word-break: break-word;
  overflow-wrap: anywhere;
}

.tabela-perfis th:nth-child(2),
.tabela-perfis th:nth-child(3),
.tabela-perfis th:nth-child(4),
.tabela-perfis th:nth-child(6),
.tabela-perfis td:nth-child(2),
.tabela-perfis td:nth-child(3),
.tabela-perfis td:nth-child(4),
.tabela-perfis td:nth-child(6) {
  white-space: nowrap;
}

.tabela-perfis td:last-child {
  text-align: center;
}

.tabela-perfis td:last-child .br-button {
  font-size: 0.8125rem;
  min-height: 2rem;
  padding: 0.25rem 0.6rem;
}

.tabela-perfis th.th-bold {
  font-weight: 700;
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

/* br-tag sem modifier = "Não vigente" (neutro) */
.tabela-perfis .br-tag:not(.success):not(.warning):not(.danger) {
  background: var(--color-secondary-02, #f5f5f5);
  color: var(--color-secondary-07, #555);
  border: 1px solid var(--color-secondary-04, #ddd);
}

.perfis-acoes-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 1rem;
}

@media (max-width: 575px) {
  .secao-grid-readonly,
  .secao-grid-dados {
    grid-template-columns: 1fr;
  }

  .secao-linha-3cols,
  .secao-linha-2cols,
  .secao-linha-orgao-cargo,
  .secao-avaliacao-campos {
    grid-template-columns: 1fr;
  }
}

@media (min-width: 576px) and (max-width: 991px) {
  .secao-linha-3cols,
  .secao-linha-2cols,
  .secao-avaliacao-campos {
    grid-template-columns: repeat(2, 1fr);
  }

  .secao-linha-orgao-cargo {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
