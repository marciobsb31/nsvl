<template>
  <DefaultLayout>
    <section class="gerenciar-cadastros" :class="{ 'painel-aberto': painelCadastroAberto || painelDetalharAberto }">
      <div class="titulo-pagina">
        <div class="titulo-pagina__topo">
          <div>
            <h1 id="titulo-gerenciar" class="titulo-pagina__h1">
              Gerenciar solicitação de cadastros no sistema
            </h1>
            <p class="titulo-pagina__subtitulo">Aplique filtros e clique em <strong>Pesquisar</strong>.</p>
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
          <p>{{ jaListou ? 'Nenhuma solicitação encontrada.' : 'Aplique os filtros e clique em Pesquisar para buscar as solicitações.' }}</p>
          <p v-if="jaListou" class="small">Ajuste os filtros ou cadastre uma nova solicitação.</p>
        </div>

        <div v-else class="table-responsive">
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
              <tr v-for="s in solicitacoesOrdenadas" :key="s.id">
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
          <div v-if="sucessoCadastro" class="br-message success mb-3" role="status">
            <div class="content">{{ sucessoCadastro }}</div>
          </div>
          <div v-if="erroCadastro" class="br-message danger mb-3" role="alert">
            <div class="content">{{ erroCadastro }}</div>
          </div>
          <div class="painel-cadastro-inner">
            <div class="formulario-header">
              <h2 class="formulario-titulo">Cadastrar usuário</h2>
              <button
                class="br-button secondary small"
                type="button"
                @click="fecharPainelCadastro"
                aria-label="Voltar"
              >
                Voltar
              </button>
            </div>
            <Form
              :key="formKeyCadastro"
              v-slot="{ values: formValues }"
              :validation-schema="schemaCadastro"
              :initial-values="initialValuesCadastro"
              :on-invalid-submit="onValidacaoInvalida"
              @submit="onConfirmarCadastro"
              class="form-cadastro"
            >
              <div class="formulario-secao">
                <h3 class="secao-titulo">Dados do solicitante</h3>
                <FormularioDadosSolicitante :modo-gov-br="false" />
              </div>
              <div class="formulario-secao">
                <h3 class="secao-titulo">Informação do(a) solicitante</h3>
                <p class="secao-subtitulo">Informações de atuação institucional do solicitante.</p>
                <FormularioInformacaoSolicitante :aplicar-regras-hierarquia="true" :usuario-logado="user" />
              </div>
              <FormularioDadosPerfil :usuario-logado="user" />
              <div class="formulario-acoes">
                <button class="br-button secondary" type="button" @click="fecharPainelCadastro">
                  Cancelar
                </button>
                <button
                  class="br-button primary"
                  type="submit"
                  :disabled="enviandoCadastro"
                >
                  {{ enviandoCadastro ? 'Confirmando...' : 'Confirmar' }}
                </button>
              </div>
            </Form>
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
            @voltar="fecharPainelDetalhar"
            @aprovar="aprovarSolicitacao"
            @reprovar="reprovarSolicitacao"
            @toggle-perfil="onTogglePerfilVinculado"
          />
        </aside>
      </Transition>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Form } from 'vee-validate'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Card from '@/core/components/Card/Card.vue'
import FiltrosGerenciarSolicitacao from '../components/FiltrosGerenciarSolicitacao.vue'
import FormularioDadosSolicitante from '@/features/solicitacao-cadastro/components/FormularioDadosSolicitante.vue'
import FormularioInformacaoSolicitante from '@/features/solicitacao-cadastro/components/FormularioInformacaoSolicitante.vue'
import FormularioDadosPerfil from '../components/FormularioDadosPerfil.vue'
import PainelDetalharSolicitacao from '../components/PainelDetalharSolicitacao.vue'
import { SolicitacaoCadastroSchemaGerenciar } from '@/features/solicitacao-cadastro/validators/solicitacaoCadastro.schema'
import {
  listarSolicitacoesGerenciar,
  aprovarSolicitacao as apiAprovar,
  reprovarSolicitacao as apiReprovar,
  ativarPerfilVinculado as apiAtivarPerfilVinculado,
  desativarPerfilVinculado as apiDesativarPerfilVinculado,
  type SolicitacaoGerenciarItem,
  type FiltrosGerenciarSolicitacao as FiltrosGerenciarSolicitacaoType,
} from '@/services/GerenciarSolicitacaoCadastroService'
import {
  enviarSolicitacaoCadastro,
  obterSolicitacaoCadastro,
  type SolicitacaoCadastroDetalhe,
  type SolicitacaoCadastroPayload,
} from '@/services/SolicitacaoCadastroService'
import { useNotification } from '@/core/composables/useNotification'
import { useAuth } from '@/core/composables/useAuth'

defineOptions({ name: 'GerenciarSolicitacaoCadastroPage' })

const router = useRouter()
const { error, success } = useNotification()
const { user } = useAuth()

const solicitacoes = ref<SolicitacaoGerenciarItem[]>([])
const carregando = ref(false)
const jaListou = ref(false)
const ordenarColuna = ref<string | null>(null)
const ordenarAsc = ref(true)

const solicitacoesOrdenadas = computed(() => {
  const lista = [...solicitacoes.value]
  if (!ordenarColuna.value) return lista
  const col = ordenarColuna.value
  const asc = ordenarAsc.value
  lista.sort((a, b) => {
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
const painelCadastroRef = ref<HTMLElement | null>(null)
const erroCadastro = ref('')
const sucessoCadastro = ref('')
const avaliando = ref(false)
const enviandoCadastro = ref(false)
const formKeyCadastro = ref(0)
const schemaCadastro = SolicitacaoCadastroSchemaGerenciar
const initialValuesCadastro = {
  nome: '',
  CPF: '',
  emailInstitucional: '',
  telefoneInstitucional: '',
  telefonePessoal: '',
  esferaAtuacao: '',
  uf: '',
  municipio: '',
  orgao: '',
  cargo: '',
  perfil: null as string | number | null,
  vigenciaInicio: '',
  vigenciaFim: '',
}

const MENSAGENS_CPF: Record<string, string> = {
  'Este CPF já possui cadastro ativo no sistema.': 'Este CPF já está em uso. Faça login ou solicite recuperação de acesso.',
  'Já existe uma solicitação em análise para este CPF.': 'Este CPF já possui uma solicitação em análise. Aguarde o retorno.',
  'O CPF informado é inválido.': 'CPF inválido. Confira os números digitados.',
}

function mapearMensagemCpf(original: string): string {
  return MENSAGENS_CPF[original] ?? original
}

function mapearMensagemCadastro(original: string): string {
  if (original === 'Acesso não permitido.') {
    return 'Acesso não permitido para os dados informados. No cadastro interno, use a mesma esfera/UF/município da sua lotação.'
  }
  return original
}

async function onValidacaoInvalida(ctx: { values: Record<string, unknown>; errors: Partial<Record<string, string>> }) {
  const primeiroErro = Object.values(ctx.errors ?? {}).find((e): e is string => typeof e === 'string')
  erroCadastro.value = primeiroErro ?? 'Preencha todos os campos obrigatórios corretamente.'
  sucessoCadastro.value = ''
  await nextTick()
  painelCadastroRef.value?.scrollTo({ top: 0, behavior: 'smooth' })
}

function camposObrigatoriosPreenchidos(values: Record<string, unknown>) {
  const obrigatorios = [
    'nome',
    'CPF',
    'emailInstitucional',
    'telefoneInstitucional',
    'esferaAtuacao',
    'uf',
    'municipio',
    'orgao',
    'cargo',
    'perfil',
    'vigenciaInicio',
  ]
  return obrigatorios.every((campo) => {
    const v = values[campo]
    if (campo === 'perfil') return v != null && v !== ''
    return String(v ?? '').trim() !== ''
  })
}

async function carregarSolicitacoes() {
  carregando.value = true
  jaListou.value = true
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
  erroCadastro.value = ''
  sucessoCadastro.value = ''
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

async function onConfirmarCadastro(
  values: Record<string, unknown>,
  actions?: { setFieldError: (field: string, message: string | undefined) => void }
) {
  erroCadastro.value = ''
  enviandoCadastro.value = true
  const perfilNum = values.perfil != null && values.perfil !== ''
    ? Number(values.perfil)
    : NaN
  const cpfVal = String(values.CPF ?? '').replace(/\D/g, '')
  const payload: SolicitacaoCadastroPayload = {
    nome: String(values.nome ?? ''),
    CPF: cpfVal || undefined,
    emailInstitucional: String(values.emailInstitucional ?? ''),
    telefoneInstitucional: String(values.telefoneInstitucional ?? '').replace(/\D/g, ''),
    telefonePessoal: values.telefonePessoal
      ? String(values.telefonePessoal).replace(/\D/g, '')
      : undefined,
    esferaAtuacao: String(values.esferaAtuacao ?? ''),
    uf: String(values.uf ?? '').toUpperCase(),
    municipio: String(values.municipio ?? ''),
    orgao: String(values.orgao ?? ''),
    cargo: String(values.cargo ?? ''),
    perfilId: !Number.isNaN(perfilNum) && perfilNum > 0 ? perfilNum : undefined,
    vigenciaInicio: String(values.vigenciaInicio ?? '').trim() || undefined,
    vigenciaFim: String(values.vigenciaFim ?? '').trim() || undefined,
  }
  try {
    await enviarSolicitacaoCadastro(payload)
    erroCadastro.value = ''
    actions?.setFieldError('CPF', undefined)
    sucessoCadastro.value = 'Cadastro realizado com sucesso! A solicitação foi registrada e está disponível na lista.'
    success('Cadastro realizado com sucesso! A solicitação foi registrada e está disponível na lista.')
    formKeyCadastro.value++
    await carregarSolicitacoes()
    await new Promise((r) => setTimeout(r, 1500))
    fecharPainelCadastro()
  } catch (e: unknown) {
    sucessoCadastro.value = ''
    const res = (e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } })?.response
    const data = res?.data
    let msg = data?.message ?? 'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.'
    if (data?.errors && typeof data.errors === 'object') {
      const firstField = Object.keys(data.errors)[0]
      const firstMsg = firstField ? data.errors[firstField]?.[0] : null
      if (firstMsg) msg = firstMsg
    }
    if (!res && (e as Error)?.message) {
      msg = (e as Error).message
    }
    const isCpfError =
      msg.toLowerCase().includes('cpf') ||
      (data?.errors && 'CPF' in (data.errors as object))
    if (isCpfError) {
      const cpfMsg = data?.errors && typeof data.errors === 'object' && 'CPF' in data.errors
        ? (data.errors as Record<string, string[]>).CPF?.[0]
        : msg
      msg = mapearMensagemCpf(cpfMsg ?? msg)
      actions?.setFieldError('CPF', msg)
    }
    msg = mapearMensagemCadastro(msg)
    erroCadastro.value = msg
    error(msg)
    await nextTick()
    painelCadastroRef.value?.scrollTo({ top: 0, behavior: 'smooth' })
  } finally {
    enviandoCadastro.value = false
  }
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

async function reprovarSolicitacao() {
  if (!detalheSelecionado.value) return
  avaliando.value = true
  try {
    await apiReprovar(detalheSelecionado.value.id)
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
  try {
    if (payload.acao === 'ativar') {
      await apiAtivarPerfilVinculado(detalheSelecionado.value.id, payload.perfilUsuarioId)
      success('Cadastro ativado com sucesso.')
    } else {
      await apiDesativarPerfilVinculado(detalheSelecionado.value.id, payload.perfilUsuarioId)
      success('Cadastro desativado com sucesso.')
    }

    detalheSelecionado.value = await obterSolicitacaoCadastro(detalheSelecionado.value.id)
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
