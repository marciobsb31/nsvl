<template>
  <div class="painel-detalhar-solicitacao">
    <div class="painel-header">
      <div class="painel-header-topo">
        <div>
          <h2 class="painel-titulo">
            {{ rotuloBotaoDetalhar(statusSolicitacaoNome) }} cadastro no sistema
          </h2>
        </div>
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
          <span class="painel-status-badge" :class="classeStatusBadge(statusSolicitacaoNome)">
            <span class="status-indicador" :class="'status-' + statusSolicitacaoNome"></span>
            {{ labelStatus(statusSolicitacaoNome) }}
          </span>
        </div>
        <div class="painel-info-item">
          <span class="painel-info-label">Perfis Vinculados</span>
          <span
            class="painel-perfis-badge"
            :class="temPerfisVinculados ? 'com-perfis' : 'sem-perfis'"
          >
            <span class="perfis-indicador" :class="temPerfisVinculados ? 'com' : 'sem'"></span>
            {{
              temPerfisVinculados
                ? `${perfisVinculadosCount} PERFIL(IS) VINCULADO(S)`
                : 'SEM PERFIS VINCULADOS'
            }}
          </span>
        </div>
      </div>
    </div>

    <!-- Bloco: Aguardando Avaliação (somente quando em_analise e usuário tem privilégio) -->
    <div v-if="podeAprovarSolicitacao" class="painel-secao">
      <h3 class="secao-titulo">Aguardando Avaliação</h3>
      <div class="secao-aguardando-avaliacao">
        <div class="secao-linha-3cols">
          <div class="br-input">
            <label>Esfera de atuação</label>
            <input
              type="text"
              :value="labelEsfera(esferaSolicitacaoNome)"
              readonly
              style="background-color: #f5f5f5"
            />
          </div>
          <div class="br-input">
            <label>UF de atuação</label>
            <input
              type="text"
              :value="estadoSolicitacaoNome"
              readonly
              style="background-color: #f5f5f5"
            />
          </div>
          <div class="br-input">
            <label>Município</label>
            <input
              type="text"
              :value="municipioSolicitacaoNome"
              readonly
              style="background-color: #f5f5f5"
            />
          </div>
        </div>
        <div class="secao-linha-orgao-cargo">
          <div class="br-input orgao-maior">
            <label>Órgão de atuação</label>
            <input type="text" :value="detalhe.orgao" readonly style="background-color: #f5f5f5" />
          </div>
          <div class="br-input cargo-menor">
            <label>Cargo/Função</label>
            <input type="text" :value="detalhe.cargo" readonly style="background-color: #f5f5f5" />
          </div>
        </div>
        <div class="secao-linha-3cols secao-avaliacao-campos">
          <div class="br-select mb-2 perfil-select">
            <label for="perfil-selecao">Perfil</label>
            <select
              id="perfil-selecao"
              v-model="perfilSelecionado"
              :disabled="opcoesPerfilPermitidasAvaliacao.length === 0"
            >
              <option :value="null" disabled>Selecione o perfil</option>
              <option
                v-for="opcao in opcoesPerfilPermitidasAvaliacao"
                :key="String(opcao.value)"
                :value="opcao.value"
              >
                {{ opcao.label }}
              </option>
            </select>
          </div>
          <div class="br-input">
            <label for="vigencia-inicio">Vigência início</label>
            <input id="vigencia-inicio" type="date" v-model="vigenciaInicio" />
          </div>
          <div class="br-input">
            <label for="vigencia-fim">Vigência fim</label>
            <input id="vigencia-fim" type="date" v-model="vigenciaFim" />
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
          @click="abrirModalAprovar"
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
            <input type="text" :value="emailInstitucional" readonly />
          </div>
        </div>
        <div class="secao-linha-2cols">
          <div class="br-input">
            <label>Telefone Institucional</label>
            <input
              type="text"
              :value="formatarTelefone(detalhe?.telefone_institucional)"
              readonly
            />
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
    <div
      v-if="statusSolicitacaoNome === StatusNomeEnum.REPROVADO"
      class="painel-secao painel-secao--historico-reprovacao"
    >
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

    <!-- Perfis Vinculados -->
    <div
      v-if="detalhe && statusSolicitacaoNome !== StatusNomeEnum.REPROVADO"
      class="painel-secao perfis-vinculados-secao"
    >
      <div class="perfis-vinculados-header">
        <div>
          <h3 class="perfis-vinculados-titulo">Perfis vinculados</h3>
          <p class="perfis-vinculados-subtitulo">
            Um usuário pode ter vários perfis. Cada vínculo possui vigência, status e contexto de
            atuação.
          </p>
        </div>
      </div>
      <div v-if="!isMobile" class="table-responsive">
        <table class="br-table tabela-perfis" role="table">
          <thead>
            <tr>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('perfil')">
                <button class="th-sort-btn" type="button" @click="alternarOrdenacao('perfil')">
                  Perfil <span class="th-sort-icon">{{ obterIndicadorSort('perfil') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('vigencia_inicio')">
                <button
                  class="th-sort-btn"
                  type="button"
                  @click="alternarOrdenacao('vigencia_inicio')"
                >
                  Vig. início
                  <span class="th-sort-icon">{{ obterIndicadorSort('vigencia_inicio') }}</span>
                </button>
              </th>
              <th scope="col" class="th-bold" :aria-sort="obterAriaSort('vigencia_fim')">
                <button
                  class="th-sort-btn"
                  type="button"
                  @click="alternarOrdenacao('vigencia_fim')"
                >
                  Vig. fim
                  <span class="th-sort-icon">{{ obterIndicadorSort('vigencia_fim') }}</span>
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
            <tr v-if="perfisVinculadosPaginados.length === 0">
              <td colspan="10" class="td-vazio-perfis">Nenhum perfil vinculado.</td>
            </tr>
            <tr v-for="(p, idx) in perfisVinculadosPaginados" :key="`perfil-${idx}-${p.id ?? idx}`">
              <td>{{ p.perfil }}</td>
              <td>{{ formatarDataExibicao(p.vigencia_inicio) }}</td>
              <td>{{ formatarDataExibicao(p.vigencia_fim) }}</td>
              <td>
                <span class="br-tag" :class="p.ativo ? 'success' : 'danger'">
                  {{ p.ativo ? 'Vigente' : 'Não vigente' }}
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
                  :disabled="
                    acaoPerfilProprioBloqueada ||
                    !operadorPodeConcederPerfil(String(p.perfil ?? ''))
                  "
                  :title="
                    acaoPerfilProprioBloqueada
                      ? 'Você não pode ativar ou desativar seu próprio cadastro'
                      : !podeGerenciarPerfis
                        ? 'Somente usuários com privilégio de avaliação podem alterar perfis vinculados'
                        : !operadorPodeConcederPerfil(String(p.perfil ?? ''))
                          ? 'Você só pode alterar perfis da sua esfera de atuação'
                          : undefined
                  "
                  @click="
                    $emit('toggle-perfil', {
                      perfilUsuarioId: p.perfil_usuario_id,
                      acao: p.ativo ? 'desativar' : 'ativar',
                    })
                  "
                >
                  {{ p.ativo ? 'Desativar' : 'Ativar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="isMobile && !temPerfisVinculados" class="perfis-vinculados-vazio">
        <p>Nenhum perfil vinculado.</p>
      </div>
      <div v-if="isMobile && temPerfisVinculados">
        <div
          class="row"
          v-for="(p, idx) in perfisVinculadosOrdenados"
          :key="`perfil-${idx}-${p.id ?? idx}`"
        >
          <div class="col-8 mb-1">
            <label for="perfil">Perfil</label>
            <p class="m-0">{{ p.perfil }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="vigente">Status</label><br />
            <span class="br-tag" :class="p.ativo ? 'success' : 'danger'">
              {{ p.ativo ? 'Vigente' : 'Não vigente' }}
            </span>
          </div>
          <div class="col-4 mb-1">
            <label for="vigencia_inicio">Vig. início</label>
            <p class="m-0">{{ formatarDataExibicao(p.vigencia_inicio) }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="vigencia_fim">Vig. fim</label>
            <p class="m-0">{{ formatarDataExibicao(p.vigencia_fim) }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="esfera">Esfera</label>
            <p class="m-0">{{ p.esfera }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="uf">UF</label>
            <p class="m-0">{{ p.uf }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="municipio">Município</label>
            <p class="m-0">{{ p.municipio }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="orgao">Órgão</label>
            <p class="m-0">{{ p.orgao }}</p>
          </div>
          <div class="col-4 mb-1">
            <label for="cargo">Cargo</label>
            <p class="m-0">{{ p.cargo }}</p>
          </div>
          <div class="col-12">
            <button
              class="br-button secondary small block"
              type="button"
              :disabled="
                acaoPerfilProprioBloqueada || !operadorPodeConcederPerfil(String(p.perfil ?? ''))
              "
              :title="
                acaoPerfilProprioBloqueada
                  ? 'Você não pode ativar ou desativar seu próprio cadastro'
                  : !podeGerenciarPerfis
                    ? 'Somente usuários com privilégio de avaliação podem alterar perfis vinculados'
                    : !operadorPodeConcederPerfil(String(p.perfil ?? ''))
                      ? 'Você só pode alterar perfis da sua esfera de atuação'
                      : undefined
              "
              @click="
                $emit('toggle-perfil', {
                  perfilUsuarioId: p.perfil_usuario_id,
                  acao: p.ativo ? 'desativar' : 'ativar',
                })
              "
            >
              {{ p.ativo ? 'Desativar' : 'Ativar' }}
            </button>
          </div>
          <div class="col-12">
            <span class="br-divider my-3"></span>
          </div>
        </div>
      </div>
      <PaginationControls
        v-if="perfisVinculadosOrdenados.length > 0"
        v-model:currentPage="paginaAtualPerfis"
        v-model:pageSize="itensPorPaginaPerfis"
        :total-items="perfisVinculadosOrdenados.length"
      />
    </div>

    <!-- Modal Adicionar Perfil -->
    <Modal
      v-if="modalAdicionarPerfilVisivel"
      title="Adicionar Perfil"
      :show-actions="false"
      @close="fecharModalAdicionarPerfil"
    >
      <form @submit.prevent="onSubmitAdicionarPerfil" class="form-modal-reprovar">
        <div class="br-select mb-3">
          <label for="perfil-adicionar">Perfil <span class="text-red-50">*</span></label>
          <select id="perfil-adicionar" v-model="perfilParaAdicionar" required>
            <option :value="null" disabled>Selecione o perfil</option>
            <option v-for="op in opcoesPerfilDisponiveis" :key="String(op.value)" :value="op.value">
              {{ op.label }}
            </option>
          </select>
        </div>
        <div class="secao-linha-2cols mb-3">
          <div class="br-input">
            <label for="vigencia-inicio-adicionar">Vigência (início)</label>
            <input id="vigencia-inicio-adicionar" type="date" v-model="vigenciaInicioAdicionar" />
          </div>
          <div class="br-input">
            <label for="vigencia-fim-adicionar">Vigência (fim)</label>
            <input id="vigencia-fim-adicionar" type="date" v-model="vigenciaFimAdicionar" />
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
            :disabled="!perfilParaAdicionar || avaliando"
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
        <p class="mb-3">
          Informe o motivo da reprovação. Esta informação ficará registrada no sistema.
        </p>
        <div class="br-textarea mb-3">
          <label for="justificativa-reprovacao"
            >Justificativa <span class="text-red-50">*</span></label
          >
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
          <button class="br-button secondary" type="button" @click="fecharModalReprovar">
            Cancelar
          </button>
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

    <!-- Modal Confirmar Aprovação -->
    <Modal
      v-if="modalAprovarVisivel"
      title="Confirmar Aprovação"
      :show-actions="false"
      @close="fecharModalAprovar"
    >
      <div class="form-modal-reprovar">
        <p class="mb-3">Confirmar aprovação desta solicitação?</p>
        <div class="form-modal-acoes">
          <button class="br-button secondary" type="button" @click="fecharModalAprovar">
            Cancelar
          </button>
          <button
            class="br-button primary"
            type="button"
            :disabled="avaliando"
            @click="onConfirmarAprovar"
          >
            Confirmar Aprovação
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue'
import { usePerfis } from '@/core/composables/usePerfis'
import { useAuth } from '@/core/composables/useAuth'
import { usePermissoes } from '@/core/composables/usePermissoes'
import Modal from '@/core/components/Modal/Modal.vue'
import PaginationControls from '@/core/components/PaginationControls/PaginationControls.vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import type {
  HistoricoReprovacaoItem,
  PerfilVinculado,
  SolicitacaoCadastroDetalhe,
} from '@/core/types/solicitacao-cadastro/SolicitacaoInterface'
import { StatusNomeEnum } from '@/core/enums/StatusEmun'

defineOptions({ name: 'PainelDetalharSolicitacao' })
const { isMobile } = useBreakpoint()
const { perfilAtivo, user } = useAuth()
const { hasPermissao } = usePermissoes()

const props = withDefaults(
  defineProps<{
    detalhe: (any & { cpf?: string }) | null
    avaliando?: boolean
    ehProprioCadastro?: boolean
  }>(),
  {},
)

const emit = defineEmits<{
  (e: 'voltar'): void
  (
    e: 'aprovar',
    payload: { perfilId: string | number | null; vigenciaInicio: string; vigenciaFim: string },
  ): void
  (e: 'reprovar', payload: { justificativa: string }): void
  (e: 'toggle-perfil', payload: { perfilUsuarioId: number; acao: 'ativar' | 'desativar' }): void
  (
    e: 'adicionar-perfil',
    payload: { perfilId: string | number; vigenciaInicio?: string; vigenciaFim?: string },
  ): void
}>()

const historicoReprovacoes = computed<HistoricoReprovacaoItem[]>(() => {
  const raw = props.detalhe?.historico_reprovacoes
  return Array.isArray(raw) ? raw : []
})

const statusSolicitacaoNome = computed(() => {
  const status = props.detalhe?.status
  if (typeof status === 'string') return status
  if (status && typeof status === 'object' && 'nome' in status) {
    const nome = (status as { nome?: unknown }).nome
    return typeof nome === 'string' ? nome : ''
  }
  return ''
})

const esferaSolicitacaoNome = computed(() => {
  const esfera = props.detalhe?.esfera
  if (typeof esfera === 'string') return esfera
  if (esfera && typeof esfera === 'object' && 'nome' in esfera) {
    const nome = (esfera as { nome?: unknown }).nome
    if (typeof nome === 'string' && nome.trim() !== '') return nome
  }
  const fallback = props.detalhe?.esfera_atuacao
  return typeof fallback === 'string' ? fallback : ''
})

const estadoSolicitacaoNome = computed(() => {
  const estado = props.detalhe?.estado
  if (typeof estado === 'string') return estado
  if (estado && typeof estado === 'object' && 'nome' in estado) {
    const nome = (estado as { nome?: unknown }).nome
    if (typeof nome === 'string' && nome.trim() !== '') return nome
  }
  const fallback = props.detalhe?.uf
  return typeof fallback === 'string' && fallback.trim() !== '' ? fallback : '—'
})

const municipioSolicitacaoNome = computed(() => {
  const municipio = props.detalhe?.municipio
  if (typeof municipio === 'string') return municipio
  if (municipio && typeof municipio === 'object' && 'nome' in municipio) {
    const nome = (municipio as { nome?: unknown }).nome
    if (typeof nome === 'string' && nome.trim() !== '') return nome
  }
  const fallback = props.detalhe?.municipio_nome
  return typeof fallback === 'string' && fallback.trim() !== '' ? fallback : '—'
})

const emailInstitucional = computed(() => {
  if (typeof props.detalhe?.email === 'string' && props.detalhe.email.trim() !== '') {
    return props.detalhe.email
  }
  if (
    typeof props.detalhe?.email_institucional === 'string' &&
    props.detalhe.email_institucional.trim() !== ''
  ) {
    return props.detalhe.email_institucional
  }
  return '—'
})

const perfilSelecionado = ref<string | number | null>(null)
const vigenciaInicio = ref('')
const vigenciaFim = ref('')
const { opcoesPerfil, carregarPerfis } = usePerfis()

type PerfilVinculadoExibicao = PerfilVinculado & {
  id: number
  perfil_usuario_id: number
  ativo: boolean
}
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
    id: (props.detalhe?.id ?? 0) * 1000 + i,
    perfil_usuario_id: p.perfil_usuario_id ?? p.id ?? (props.detalhe?.id ?? 0) * 1000 + i,
    ativo: Boolean(p.ativo ?? p.vigente),
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

function inferirEsferaPerfil(nomePerfil: string): string {
  const nome = nomePerfil.toLowerCase()

  if (nome.includes('municipal')) return 'municipal'
  if (nome.includes('estadual')) return 'estadual'
  if (nome.includes('federal') || nome.includes('nacional')) return 'federal'

  return ''
}

const esferaOperador = computed(() => {
  const contextoEsfera = String(user.value?.contexto?.esfera?.nome ?? '')
    .trim()
    .toLowerCase()
  if (contextoEsfera) return contextoEsfera

  return inferirEsferaPerfil(String(perfilAtivo.value?.nome ?? ''))
})

const podeGerenciarPerfis = computed(() => hasPermissao('solicitacoes_cadastro.analisar'))
const podeAprovarSolicitacao = computed(
  () =>
    statusSolicitacaoNome.value === StatusNomeEnum.EM_ANALISE &&
    props.detalhe?.pode_avaliar === true,
)

const esferaSolicitacaoNormalizada = computed(() => {
  return String(esferaSolicitacaoNome.value ?? '')
    .trim()
    .toLowerCase()
})

function operadorPodeConcederPerfil(nomePerfilDestino: string): boolean {
  if (!podeGerenciarPerfis.value) return false

  const esferaDestino = inferirEsferaPerfil(nomePerfilDestino)
  if (!esferaDestino) return false

  if (esferaOperador.value === 'federal') {
    return true
  }

  return esferaDestino === esferaOperador.value
}

const opcoesPerfilPermitidasOperador = computed(() => {
  return opcoesPerfil.value.filter((op) => operadorPodeConcederPerfil(String(op.label ?? '')))
})

const opcoesPerfilPermitidasAvaliacao = computed(() => {
  const esferaSolicitacao = inferirEsferaPerfil(esferaSolicitacaoNormalizada.value)
  if (!esferaSolicitacao) return []

  return opcoesPerfilPermitidasOperador.value.filter((op) => {
    const esferaPerfil = inferirEsferaPerfil(String(op.label ?? ''))
    return esferaPerfil === esferaSolicitacao
  })
})

const operadorEhAdministrador = computed(() => {
  return String(perfilAtivo.value?.nome ?? '')
    .toLowerCase()
    .includes('administrador')
})

const acaoPerfilProprioBloqueada = computed(() => {
  return Boolean(props.ehProprioCadastro && !operadorEhAdministrador.value)
})

const opcoesPerfilDisponiveis = computed(() => {
  const perfisJaVinculados = new Set(
    perfisVinculadosLista.value.map((p) => String(p.perfil).toLowerCase()),
  )
  return opcoesPerfilPermitidasOperador.value.filter(
    (op) => !perfisJaVinculados.has(String(op.label).toLowerCase()),
  )
})

const modalReprovarVisivel = ref(false)
const justificativaReprovacao = ref('')
const erroReprovar = ref('')
const modalAprovarVisivel = ref(false)
const modalAdicionarPerfilVisivel = ref(false)
const perfilParaAdicionar = ref<string | number | null>(null)
const vigenciaInicioAdicionar = ref('')
const vigenciaFimAdicionar = ref('')
const erroAdicionarPerfil = ref('')

function abrirModalAdicionarPerfil() {
  perfilParaAdicionar.value = null
  vigenciaInicioAdicionar.value = ''
  vigenciaFimAdicionar.value = ''
  erroAdicionarPerfil.value = ''
  modalAdicionarPerfilVisivel.value = true
}

function fecharModalAdicionarPerfil() {
  modalAdicionarPerfilVisivel.value = false
}

function onSubmitAdicionarPerfil() {
  if (!perfilParaAdicionar.value) {
    erroAdicionarPerfil.value = 'Selecione um perfil.'
    return
  }
  erroAdicionarPerfil.value = ''
  emit('adicionar-perfil', {
    perfilId: perfilParaAdicionar.value,
    vigenciaInicio: vigenciaInicioAdicionar.value || undefined,
    vigenciaFim: vigenciaFimAdicionar.value || undefined,
  })
  fecharModalAdicionarPerfil()
}

function abrirModalAprovar() {
  modalAprovarVisivel.value = true
}

function fecharModalAprovar() {
  modalAprovarVisivel.value = false
}

function onConfirmarAprovar() {
  fecharModalAprovar()
  emit('aprovar', {
    perfilId: perfilSelecionado.value,
    vigenciaInicio: vigenciaInicio.value,
    vigenciaFim: vigenciaFim.value,
  })
}

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
        novo.perfil_id_solicitado != null && novo.perfil_id_solicitado > 0
          ? novo.perfil_id_solicitado
          : null
      paginaAtualPerfis.value = 1
    }
  },
  { immediate: true },
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
    [StatusNomeEnum.EM_ANALISE]: 'EM ANÁLISE',
    [StatusNomeEnum.APROVADO]: 'APROVADA',
    [StatusNomeEnum.REPROVADO]: 'REPROVADA',
  }
  return map[status] ?? status
}

function classeStatusBadge(status: string) {
  const map: Record<string, string> = {
    [StatusNomeEnum.EM_ANALISE]: 'status-em-analise',
    [StatusNomeEnum.APROVADO]: 'status-aprovada',
    [StatusNomeEnum.REPROVADO]: 'status-reprovada',
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

function compararValores(
  a: PerfilVinculadoExibicao,
  b: PerfilVinculadoExibicao,
  coluna: ColunaOrdenacao,
): number {
  switch (coluna) {
    case 'vigencia_inicio':
      return normalizarData(a.vigencia_inicio) - normalizarData(b.vigencia_inicio)
    case 'vigencia_fim':
      return normalizarData(a.vigencia_fim) - normalizarData(b.vigencia_fim)
    case 'vigente':
      return Number(a.ativo) - Number(b.ativo)
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

function rotuloBotaoDetalhar(status: string) {
  return status === StatusNomeEnum.EM_ANALISE ? 'Avaliar' : 'Visualizar'
}
</script>

<style scoped>
.painel-detalhar-solicitacao {
  padding: 1rem;
  color: var(--dark-text-color);
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

.perfis-vinculados-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.75rem;
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
  color: var(--secondary-text-color);
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
  color: var(--secondary-text-color-high);
}

.painel-perfis-badge.com-perfis {
  background: #e8f5e9;
  color: #2e7d32;
}

.text-muted {
  color: var(--secondary-text-color-02);
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
  color: var(--secondary-text-color);
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

.secao-dados-solicitante-layout input[readonly] {
  background: var(--color-secondary-02, #f0f0f0);
  border-color: var(--color-secondary-04, #d9d9d9);
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
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
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
  color: var(--dark-text-color);
  margin: 0 0 0.25rem;
}

.perfis-vinculados-subtitulo {
  font-size: 0.9375rem;
  font-weight: 400;
  color: var(--dark-text-color);
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

.tabela-perfis .td-vazio-perfis {
  text-align: center;
  color: var(--secondary-text-color);
  font-style: italic;
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
