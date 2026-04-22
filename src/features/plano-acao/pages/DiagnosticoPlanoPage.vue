<template>
  <DefaultLayout>
    <HeaderPage
      title="Diagnóstico"
      subtitle="Caracterize a população com deficiência e identifique as principais barreiras."
      customClass="mb-4"
    >
      <template #actions>
        <button
          class="br-button secondary"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
          Voltar para seções
        </button>
      </template>
    </HeaderPage>

    <form novalidate @submit.prevent>

      <Card
        title="Área de diagnóstico"
        subtitle="Caracterização da população com deficiência e principais barreiras identificadas."
        custom-class="mb-4"
      >
        <section class="row g-3">

          <!-- Caracterização da população -->
          <div class="col-12">
            <div class="br-input" :class="{ danger: erros.caracterizacaoPopulacao }">
              <label for="campo-caracterizacao">
                Caracterização da população com deficiência
                <span class="text-red-50 text-up-01"> *</span>
              </label>
              <textarea
                id="campo-caracterizacao"
                v-model="form.caracterizacaoPopulacao"
                rows="4"
                :placeholder="'Ex.: corresponde a aproximadamente X% ...'"
                aria-required="true"
                :aria-describedby="erros.caracterizacaoPopulacao ? 'erro-caracterizacao' : undefined"
                @blur="validarCaracterizacao"
              ></textarea>
              <span
                v-if="erros.caracterizacaoPopulacao"
                id="erro-caracterizacao"
                class="feedback danger"
                role="alert"
              >
                <i class="fas fa-times-circle" aria-hidden="true"></i>
                {{ erros.caracterizacaoPopulacao }}
              </span>
            </div>
          </div>

          <!-- Grade de barreiras 2 colunas -->
          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras urbanísticas"
              descricao="São dificuldades encontradas nas ruas e em espaços públicos que impedem ou dificultam a locomoção e o acesso das pessoas."
              :opcoes="BARREIRAS_URBANISTICAS"
              v-model="form.barreiraUrbanisticas"
            />
          </div>

          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras arquitetônicas"
              descricao="São obstáculos dentro ou na entrada de prédios que dificultam o acesso e a circulação das pessoas."
              :opcoes="BARREIRAS_ARQUITETONICAS"
              v-model="form.barreiraArquitetonicas"
            />
          </div>

          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras nos transportes"
              descricao="São dificuldades nos meios de transporte que impedem ou dificultam o uso por todas as pessoas."
              :opcoes="BARREIRAS_TRANSPORTES"
              v-model="form.barreiraTransportes"
            />
          </div>

          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras nas comunicações e informação"
              descricao="São dificuldades que impedem ou dificultam que todas as pessoas recebam, compreendam ou compartilhem informações."
              :opcoes="BARREIRAS_COMUNICACOES"
              v-model="form.barreiraComunicacoes"
            />
          </div>

          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras atitudinais"
              descricao="São preconceitos, julgamentos ou comportamentos das pessoas que dificultam ou impedem a inclusão e a participação de outras na sociedade."
              :opcoes="BARREIRAS_ATITUDINAIS"
              v-model="form.barreiraAtitudinais"
            />
          </div>

          <div class="col-12 col-md-6">
            <BarreiraCheckboxGroup
              titulo="Barreiras tecnológicas"
              descricao="São dificuldades em equipamentos, sites ou aparelhos que impedem ou dificultam o uso por todas as pessoas."
              :opcoes="BARREIRAS_TECNOLOGICAS"
              v-model="form.barreiraTecnologicas"
            />
          </div>

          <!-- Erro de validação de barreiras -->
          <div v-if="erros.barreiras" class="col-12">
            <span class="feedback danger" role="alert">
              <i class="fas fa-times-circle" aria-hidden="true"></i>
              {{ erros.barreiras }}
            </span>
          </div>

          <!-- Outras barreiras -->
          <div class="col-12">
            <div class="br-input">
              <label for="campo-outras-barreiras">Outras barreiras</label>
              <textarea
                id="campo-outras-barreiras"
                v-model="form.outrasBarreiras"
                rows="3"
                placeholder="(opcional)"
              ></textarea>
            </div>
          </div>

        </section>
      </Card>

      <!-- Barra de ações -->
      <div class="painel-acoes">
        <button
          class="br-button secondary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          @click="router.push('/enviar-plano-acao')"
        >
          <i class="fas fa-arrow-left" aria-hidden="true"></i>
          <span>Voltar</span>
        </button>
        <button
          class="br-button primary painel-acoes__btn painel-acoes__btn--com-icone"
          type="button"
          :disabled="salvando || !podeSalvar"
          @click="salvarRascunho"
        >
          <i v-if="!salvando" class="fas fa-save" aria-hidden="true"></i>
          <i v-else class="fas fa-spinner fa-spin" aria-hidden="true"></i>
          <span>{{ salvando ? 'Salvando...' : 'Salvar Rascunho' }}</span>
        </button>
      </div>

    </form>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import HeaderPage from '@/core/components/HeaderPage/HeaderPage.vue'
import Card from '@/core/components/Card/Card.vue'
import BarreiraCheckboxGroup from '@/features/plano-acao/components/BarreiraCheckboxGroup.vue'
import { obterDiagnostico, salvarDiagnostico } from '@/features/plano-acao/services/PlanoAcaoDiagnosticoService'
import { useNotification } from '@/core/composables/useNotification'

defineOptions({ name: 'DiagnosticoPlanoPage' })

const router = useRouter()
const { success: notifySuccess, error: notifyError } = useNotification()

// ── Listas de opções ────────────────────────────────────────────────────────

const BARREIRAS_URBANISTICAS: string[] = [
  'Calçadas irregulares',
  'Calçadas estreitas',
  'Calçadas inexistentes',
  'Ausência de rotas acessíveis',
  'Sinalização inadequada',
  'Buracos, desníveis ou pisos irregulares',
  'Falta de manutenção',
  'Falta de rampas em esquinas',
  'Rampas muito íngremes',
  'Rampas obstruídas',
  'Escadas sem rampas',
  'Ausência de elevadores',
  'Ausência de corrimãos',
  'Ausência de piso tátil direcional',
  'Falta de piso tátil de alerta em travessias, escadas ou plataformas',
  'Falta de faixas de pedestre',
  'Semáforos sem sinal sonoro',
  'Tempo insuficiente para travessia',
  'Postes no meio da calçada',
  'Lixeiras ou bancas bloqueando passagem',
  'Paradas de ônibus sem acessibilidade',
  'Plataformas sem nivelamento adequado',
  'Placas mal posicionadas ou ilegíveis',
  'Ruas ou calçadas mal iluminadas, dificultando orientação e segurança',
  'Falta de vagas reservadas para pessoas com deficiência',
  'Vagas reservadas sem sinalização adequada',
  'Vagas muito estreitas para permitir abertura da porta ou uso de cadeira de rodas',
  'Falta de rota acessível entre a vaga e a entrada do local',
  'Falta de contraste de cores em escadas ou desníveis',
]

const BARREIRAS_TRANSPORTES: string[] = [
  'Frota parcialmente acessível',
  'Dificuldades de deslocamento interbairros',
  'Ônibus sem plataforma elevatória ou rampa',
  'Degraus muito altos para embarque nos ônibus',
  'Portas estreitas que impedem a entrada de cadeira de rodas em ônibus',
  'Falta de cinto de segurança para cadeiras de rodas em ônibus',
  'Falta de área reservada para cadeira de rodas em ônibus',
  'Corredores muito estreitos dentro do ônibus',
  'Falta de assentos preferenciais',
  'Falta de avisos sonoros sobre as próximas paradas',
  'Falta de painéis visuais indicando as paradas',
  'Informações apenas em formato visual ou apenas sonoro',
  'Falta de aplicativos ou sistemas acessíveis de informação sobre rotas',
  'Pontos de ônibus sem acesso para cadeira de rodas',
  'Falta de piso tátil em estações',
  'Falta de rampas em terminais',
  'Motoristas sem treinamento para operar equipamentos de acessibilidade',
  'Recusa de embarque de pessoa com deficiência',
  'Falta de assistência para embarque e desembarque',
  'Estações de metrô ou trem sem elevador',
  'Plataformas sem nivelamento com o trem',
  'Táxis ou aplicativos sem veículos acessíveis',
  'Aeronaves sem assistência adequada para embarque',
  'Paradas muito rápidas que impedem o embarque de pessoas com mobilidade reduzida',
  'Falta de tempo suficiente para embarque ou desembarque',
  'Motoristas que não param corretamente junto ao meio-fio',
  'Falta de rotas acessíveis entre terminal, estação e ponto de ônibus',
  'Percursos longos sem acessibilidade entre diferentes transportes',
]

const BARREIRAS_ATITUDINAIS: string[] = [
  'Práticas capacitistas ainda presentes nos serviços públicos',
  'Práticas capacitistas ainda presentes na sociedade',
  'Falta de treinamento para profissionais sobre a conscientização contra o capacitismo',
  'Falta de campanhas sociais para conscientização contra o capacitismo',
  'Ausência de divulgação sobre a importância da acessibilidade',
  'Ausência de treinamento em linguagem simples',
]

const BARREIRAS_ARQUITETONICAS: string[] = [
  'Prédios públicos sem adequações completas de acessibilidade',
  'Entrada do edifício apenas por escadas',
  'Ausência de rampas',
  'Ausência de elevadores',
  'Portas muito estreitas para passagem de cadeira de rodas',
  'Corredores estreitos',
  'Escadas sem corrimão',
  'Falta de sinalização visual ou tátil nos degraus',
  'Falta de banheiro adaptado',
  'Auditórios sem espaços reservados para cadeiras de rodas',
  'Palcos ou púlpitos sem rampa de acesso',
  'Ausência de placas em braile',
]

const BARREIRAS_COMUNICACOES: string[] = [
  'Ausência de materiais acessíveis',
  'Falta de audiodescrição',
  'Falta de linguagem simples (textos com linguagem excessivamente técnica ou complexa)',
  'Falta de legendagem em eventos',
  'Documentos sem versão em braile',
  'Materiais informativos com letras muito pequenas',
  'Ausência de intérprete de Libras em serviços públicos',
  'Servidores que não sabem como se comunicar com pessoas surdas',
  'Falta de alternativas para comunicação escrita ou digital',
  'Placas sem braile',
  'Falta de sinalização tátil',
  'Falta de contraste visual em placas e avisos',
  'Palestras sem intérprete de Libras',
  'Falta de legendagem em eventos ao vivo',
  'Falta de recursos de audiodescrição',
  'Sites que não funcionam com leitores de tela',
  'Aplicativos sem recursos de acessibilidade',
  'Formulários online inacessíveis',
  'Botões ou menus sem descrição para tecnologias assistivas',
  'Informações governamentais sem versões acessíveis',
  'Comunicados apenas em formato visual ou apenas sonoro',
  'Ausência de canais acessíveis para solicitar serviços',
  'Informação apenas em texto, sem áudio ou vídeo',
  'Falta de recursos visuais de apoio',
  'Falta de ampliação de texto ou contraste em sistemas',
  'Falta de dispositivos de comunicação alternativa',
]

const BARREIRAS_TECNOLOGICAS: string[] = [
  'Sites que não funcionam com leitores de tela',
  'Imagens sem descrição (texto alternativo)',
  'Formulários online que não podem ser navegados por teclado',
  'Botões ou menus sem identificação acessível',
  'Aplicativos incompatíveis com leitores de tela',
  'Falta de opção de aumento de fonte',
  'Falta de contraste de cores',
  'Falta de possibilidade de intérprete de Libras na tela',
  'Controles de áudio e vídeo pouco acessíveis',
  'Sistemas que não permitem ajuste de tamanho de texto',
  'Interfaces que não permitem mudança de cores',
  'Ausência de modos de alto contraste',
  'Portais governamentais inacessíveis',
  'Sistemas de inscrição online não adaptados',
  'Plataformas de serviços digitais sem recursos de acessibilidade',
  'Tecnologias criadas sem considerar usuários com deficiência',
]

// ── Estado do formulário ─────────────────────────────────────────────────────

const form = ref({
  caracterizacaoPopulacao: '',
  barreiraUrbanisticas: [] as string[],
  barreiraTransportes: [] as string[],
  barreiraAtitudinais: [] as string[],
  barreiraArquitetonicas: [] as string[],
  barreiraComunicacoes: [] as string[],
  barreiraTecnologicas: [] as string[],
  outrasBarreiras: '',
})

const salvando = ref(false)

const erros = ref({
  caracterizacaoPopulacao: '',
  barreiras: '',
})

const temBarreiraSelecionada = computed(() =>
  form.value.barreiraUrbanisticas.length > 0 ||
  form.value.barreiraTransportes.length > 0 ||
  form.value.barreiraAtitudinais.length > 0 ||
  form.value.barreiraArquitetonicas.length > 0 ||
  form.value.barreiraComunicacoes.length > 0 ||
  form.value.barreiraTecnologicas.length > 0,
)

const podeSalvar = computed(() =>
  form.value.caracterizacaoPopulacao.trim().length > 0 && temBarreiraSelecionada.value,
)

function validarCaracterizacao(): boolean {
  if (!form.value.caracterizacaoPopulacao.trim()) {
    erros.value.caracterizacaoPopulacao = 'Campo obrigatório não preenchido.'
    return false
  }
  erros.value.caracterizacaoPopulacao = ''
  return true
}

function validarBarreiras(): boolean {
  if (!temBarreiraSelecionada.value) {
    erros.value.barreiras = 'Selecione pelo menos uma barreira para continuar.'
    return false
  }
  erros.value.barreiras = ''
  return true
}

function validarForm(): boolean {
  const campoOk = validarCaracterizacao()
  const barreiraOk = validarBarreiras()
  return campoOk && barreiraOk
}

async function salvarRascunho(): Promise<void> {
  if (!validarForm()) return

  salvando.value = true
  try {
    await salvarDiagnostico({
      caracterizacao_populacao: form.value.caracterizacaoPopulacao.trim(),
      barreiras_urbanisticas: form.value.barreiraUrbanisticas,
      barreiras_transportes: form.value.barreiraTransportes,
      barreiras_atitudinais: form.value.barreiraAtitudinais,
      barreiras_arquitetonicas: form.value.barreiraArquitetonicas,
      barreiras_comunicacoes: form.value.barreiraComunicacoes,
      barreiras_tecnologicas: form.value.barreiraTecnologicas,
      outras_barreiras: form.value.outrasBarreiras.trim(),
    })
    notifySuccess('Diagnóstico salvo com sucesso!')
    router.push('/enviar-plano-acao')
  } catch (err: unknown) {
    const apiErr = err as { response?: { data?: { message?: string } } }
    notifyError(
      apiErr?.response?.data?.message ?? 'Ocorreu um erro ao salvar. Tente novamente.',
    )
  } finally {
    salvando.value = false
  }
}

onMounted(async () => {
  try {
    const dados = await obterDiagnostico()
    if (dados) {
      form.value.caracterizacaoPopulacao = dados.caracterizacao_populacao ?? ''
      form.value.barreiraUrbanisticas = dados.barreiras_urbanisticas ?? []
      form.value.barreiraTransportes = dados.barreiras_transportes ?? []
      form.value.barreiraAtitudinais = dados.barreiras_atitudinais ?? []
      form.value.barreiraArquitetonicas = dados.barreiras_arquitetonicas ?? []
      form.value.barreiraComunicacoes = dados.barreiras_comunicacoes ?? []
      form.value.barreiraTecnologicas = dados.barreiras_tecnologicas ?? []
      form.value.outrasBarreiras = dados.outras_barreiras ?? ''
    }
  } catch {
    // sem registro prévio, formulário começa vazio
  }
})
</script>

<style scoped>
/* ── Textarea dentro de br-input ── */
.br-input textarea {
  width: 100%;
  min-height: 80px;
  resize: vertical;
  padding: 0.5rem 0.75rem;
  border: 1px solid #888;
  border-radius: 4px;
  font-size: 0.9375rem;
  font-family: inherit;
  color: #1c1c1c;
  background: #fff;
  outline: none;
  transition: border-color 0.15s;
}

.br-input textarea:focus {
  border-color: #1351b4;
  box-shadow: 0 0 0 2px rgba(19, 81, 180, 0.2);
}

.br-input.danger textarea {
  border-color: #e52207;
}

/* ── Barra de ações ── */
.painel-acoes {
  display: flex;
  flex-direction: column-reverse;
  gap: 0.75rem;
  padding: 1.25rem 0 0;
  border-top: 1px solid var(--color-secondary-03, #e8e8e8);
}

.painel-acoes__btn {
  width: 100%;
  min-height: 2.75rem;
  justify-content: center;
}

@media (min-width: 576px) {
  .painel-acoes {
    flex-direction: row;
    justify-content: flex-end;
    align-items: center;
  }

  .painel-acoes__btn {
    width: auto;
    min-width: 10rem;
  }
}
</style>
