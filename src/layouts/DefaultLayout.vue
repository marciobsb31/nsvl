<template>
  <div class="layout-default">
    <a href="#main-content" class="skip-link">Ir para o conteúdo principal</a>
    <!-- Cabeçalho GOV.BR -->
    <Header
      title="NVSL"
      subtitle="Sistema de Gestão"
      :logoGov="logoGov"
      redirectUrl="/gerenciar-cadastros"
    >
      <template #actions v-if="isAuthenticated">
        <div class="header-user">
          <div class="header-user-info">
            <div class="header-user-avatar" aria-hidden="true">
              <i class="fas fa-user"></i>
            </div>
            <div class="header-user-dados">
              <span class="header-user-nome" aria-label="Usuário logado">{{ userName }}</span>
              <span v-if="perfilAtivoLabel" class="header-user-perfil" :title="perfilAtivoLabel">
                {{ perfilAtivoLabel }}
              </span>
            </div>
          </div>
          <div v-if="exibirTrocaContexto" class="header-contexto-wrap">
            <button
              class="header-btn-contexto"
              type="button"
              aria-label="Trocar contexto de perfil"
              :aria-expanded="exibirComboContexto"
              aria-controls="header-contexto-panel"
              @click="toggleTrocaContexto"
            >
              <i class="fas fa-exchange-alt" aria-hidden="true"></i>
              <span class="header-btn-contexto__texto">Trocar contexto</span>
              <i
                class="fas header-btn-contexto__seta"
                :class="exibirComboContexto ? 'fa-chevron-up' : 'fa-chevron-down'"
                aria-hidden="true"
              ></i>
            </button>
            <transition name="contexto-panel-accordion">
              <div
                v-if="exibirComboContexto"
                id="header-contexto-panel"
                class="header-combo-contexto"
              >
                <div class="header-combo-contexto__header">
                  <span class="header-combo-contexto__titulo">Selecionar perfil ativo</span>
                  <!-- <span class="header-combo-contexto__badge">Atualização imediata</span> -->
                </div>
                <div v-if="perfilAtivoLabel" class="header-combo-contexto__perfil-uso">
                  <span class="header-combo-contexto__perfil-titulo">Perfil em uso:</span>
                  <span class="br-tag success perfil-ativo p-1">{{ perfilAtivoLabel }}</span>
                </div>
                <SelectAutocomplete
                  v-model="perfilSelecionadoId"
                  label="Perfil de acesso"
                  placeholder="Busque e selecione o perfil"
                  :options="opcoesTrocaContexto"
                  :disabled="trocandoContexto || trocandoPerfilHeader"
                  input-id="header-contexto-select"
                />
                <small class="header-combo-contexto__hint"
                  >A troca atualiza permissões e dados sem novo login.</small
                >
                <small v-if="erroTrocaContexto" class="header-combo-contexto__erro">{{
                  erroTrocaContexto
                }}</small>
              </div>
            </transition>
          </div>
        </div>
      </template>
    </Header>

    <!-- Botão menu mobile -->
    <button
      v-if="isMobile"
      class="layout-menu-toggle"
      type="button"
      aria-label="Abrir menu"
      :aria-expanded="sidebarAberto"
      aria-controls="app-sidebar"
      @click="sidebarAberto = !sidebarAberto"
    >
      <i class="fas" :class="sidebarAberto ? 'fa-times' : 'fa-bars'" aria-hidden="true"></i>
    </button>

    <!-- Overlay sidebar mobile -->
    <div
      v-if="isMobile && sidebarAberto"
      class="layout-sidebar-overlay"
      aria-hidden="true"
      @click="sidebarAberto = false"
    ></div>

    <!-- Conteúdo com sidebar e área principal -->
    <div class="layout-default__body">
      <Sidebar
        id="app-sidebar"
        :class="{ 'sidebar--aberto': sidebarAberto }"
        :recolhido="sidebarRecolhido"
        :aberto="sidebarAberto"
        @toggle-recolher="sidebarRecolhido = !sidebarRecolhido"
      />
      <main ref="mainRef" id="main-content" class="layout-default__main" tabindex="-1">
        <div class="container main-content" :key="contextKey">
          <Breadcrumb customClass="mb-3"></Breadcrumb>
          <slot />
        </div>
      </main>
    </div>

    <Footer inverted>
      <template #info>
        <div v-if="isMobile" class="mt-3">
          <img :src="logoGov" alt="Logo GOV" class="logo-gov" />
        </div>
        <div class="footer">© {{ currentYear }} NVSL — Todos os direitos reservados</div>
      </template>
    </Footer>

    <ScrollToTop />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'
import Header from '@/core/components/Header/Header.vue'
import Sidebar from '@/core/components/Sidebar/Sidebar.vue'
import Footer from '@/core/components/Footer/Footer.vue'
import { useBreakpoint } from '@/core/composables/useBreakpoint'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import { useTheme } from '@/core/composables/useTheme'
import Breadcrumb from '@/core/components/Breadcrumb/Breadcrumb.vue'
import ScrollToTop from '@/core/components/ScrollToTop/ScrollToTop.vue'
import SelectAutocomplete, {
  type SelectAutocompleteOption,
} from '@/core/components/SelectAutocomplete/SelectAutocomplete.vue'
import { useNotification } from '@/core/composables/useNotification'

const { isMobile } = useBreakpoint()
const sidebarAberto = ref(false)
const sidebarRecolhido = ref(localStorage.getItem('nvsl_sidebar_recolhido') === 'true')
const { mode } = useTheme()
const exibirComboContexto = ref(false)
const perfilSelecionadoId = ref<number | null>(null)
const trocandoPerfilHeader = ref(false)
const erroTrocaContexto = ref('')
const { success } = useNotification()

const router = useRouter()
const {
  isAuthenticated,
  userName,
  user,
  possuiMultiplosPerfis,
  perfisAtivos,
  perfilAtivo,
  contextKey,
  trocarContexto,
  trocandoContexto,
  refreshUser,
} = useAuth()

const exibirTrocaContexto = computed(() => isAuthenticated.value && possuiMultiplosPerfis.value)

const perfilAtivoLabel = computed(() => {
  const perfil = perfilAtivo.value
  if (perfil) {
    return montarLabelPerfil(perfil)
  }

  const nomePerfil = user.value?.contexto.perfil
  const localidade = user.value?.contexto.localidade
  if (nomePerfil && localidade) {
    return `${nomePerfil} - ${localidade}`
  }
  return nomePerfil ?? localidade ?? ''
})

const currentYear = computed(() => new Date().getFullYear())
const logoGov = ref(logoGovColor)

const opcoesTrocaContexto = computed<SelectAutocompleteOption[]>(() => {
  const perfilAtualId = perfilAtivo.value?.id

  const PRIORIDADE_TIPO: Record<string, number> = {
    gestor: 0,
    administrador: 1,
    visitante: 2,
  }
  const PRIORIDADE_ESFERA: Record<string, number> = {
    federal: 0,
    estadual: 1,
    municipal: 2,
  }

  function prioridadeTipo(nome?: string): number {
    const n = String(nome ?? '')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
    for (const [chave, ordem] of Object.entries(PRIORIDADE_TIPO)) {
      if (n.includes(chave)) return ordem
    }
    return 99
  }

  function prioridadeEsfera(esfera?: string): number {
    return (
      PRIORIDADE_ESFERA[
        String(esfera ?? '')
          .toLowerCase()
          .trim()
      ] ?? 99
    )
  }

  return [...perfisAtivos.value]
    .sort((a, b) => {
      const tipoDiff = prioridadeTipo(a.nome) - prioridadeTipo(b.nome)
      if (tipoDiff !== 0) return tipoDiff
      const esferaDiff = prioridadeEsfera(a.esfera) - prioridadeEsfera(b.esfera)
      if (esferaDiff !== 0) return esferaDiff
      return String(a.nome).localeCompare(String(b.nome), 'pt-BR')
    })
    .map((perfil) => {
      const emUso = perfil.id === perfilAtualId
      const label = montarLabelPerfil(perfil)
      return {
        value: perfil.id,
        label,
        badge: emUso ? 'Em uso' : undefined,
        inUse: emUso,
      }
    })
})

function montarLabelPerfil(perfil: {
  nome: string
  municipio?: string | null
  localidade?: string | null
  uf?: string | null
}) {
  const localidade = perfil.municipio ?? perfil.localidade ?? '—'
  const uf = perfil.uf ?? '—'
  return `${perfil.nome} - ${localidade} - ${uf}`
}

function toggleTrocaContexto() {
  exibirComboContexto.value = !exibirComboContexto.value
  erroTrocaContexto.value = ''
  if (exibirComboContexto.value) {
    perfilSelecionadoId.value = perfilAtivo.value?.id ?? null
  }
}

watch(mode, (newMode) => {
  logoGov.value = newMode === 'dark' ? logoGovBranca : logoGovColor
})

onMounted(() => {
  logoGov.value = mode.value === 'dark' ? logoGovBranca : logoGovColor
  perfilSelecionadoId.value = perfilAtivo.value?.id ?? null
})

function handleVisibilityChange() {
  if (document.visibilityState === 'visible' && isAuthenticated.value) {
    refreshUser()
  }
}
document.addEventListener('visibilitychange', handleVisibilityChange)

onUnmounted(() => document.removeEventListener('visibilitychange', handleVisibilityChange))

watch(
  () => perfilAtivo.value?.id,
  (id) => {
    perfilSelecionadoId.value = id ?? null
  },
)

watch(perfilSelecionadoId, async (novoId) => {
  if (!exibirComboContexto.value) return
  if (!novoId || novoId === perfilAtivo.value?.id) return
  if (trocandoPerfilHeader.value || trocandoContexto.value) return

  trocandoPerfilHeader.value = true
  erroTrocaContexto.value = ''
  try {
    await trocarContexto(Number(novoId))
    await router.replace(router.currentRoute.value.path)
    success('Contexto trocado com sucesso!')
    exibirComboContexto.value = false
  } catch {
    erroTrocaContexto.value = 'Não foi possível trocar o contexto. Tente novamente.'
    perfilSelecionadoId.value = perfilAtivo.value?.id ?? null
  } finally {
    trocandoPerfilHeader.value = false
  }
})

watch(isMobile, (mobile) => {
  if (!mobile) sidebarAberto.value = false
})

watch(sidebarRecolhido, (v) => {
  localStorage.setItem('nvsl_sidebar_recolhido', String(v))
})
</script>

<style scoped>
.layout-default {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.layout-default__body {
  display: flex;
  flex: 1;
  min-height: 0;
}

.layout-default__main {
  flex: 1;
  padding: 1rem 0;
  background-color: var(--background);
  overflow: auto;
}

@media (min-width: 576px) {
  .layout-default__main {
    padding: 1.5rem 0;
  }
}

@media (min-width: 992px) {
  .layout-default__main {
    padding: 2rem 0;
  }
}

.layout-menu-toggle {
  position: fixed;
  bottom: 9rem;
  right: 1.5rem;
  z-index: 998;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: var(--color-primary-default, #1351b4);
  color: #fff;
  border: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

@media (min-width: 992px) {
  .layout-menu-toggle {
    display: none;
  }
}

.layout-sidebar-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 997;
}

@media (min-width: 992px) {
  .layout-sidebar-overlay {
    display: none;
  }
}

.header-user {
  position: relative;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.375rem 0.625rem;
  border-radius: 10px;
  background: color-mix(in srgb, var(--color-primary-default, #1351b4) 8%, white);
  border: 1px solid color-mix(in srgb, var(--color-primary-default, #1351b4) 18%, white);
}

.header-combo-contexto {
  position: absolute;
  top: calc(100% + 0.5rem);
  left: 50%;
  transform: translateX(-50%);
  min-width: 360px;
  width: min(540px, 88vw);
  max-width: min(540px, 88vw);
  padding: 1rem;
  border-radius: 10px;
  background: var(--background);
  border: 1px solid var(--color-secondary-04, #c5c5c5);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.14);
  z-index: 1001;
}

.header-combo-contexto__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
}

.header-combo-contexto__titulo {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--primary-text-color);
}

.header-combo-contexto__badge {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.45rem;
  border-radius: 999px;
  font-size: 0.66rem;
  font-weight: 700;
  color: var(--color-primary-darken-01, #0c326f);
  background: color-mix(in srgb, var(--color-primary-default, #1351b4) 16%, white);
}

.header-combo-contexto__perfil-uso {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.35rem;
}

.header-combo-contexto__perfil-titulo {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-secondary-07, #555);
}

.header-combo-contexto__badge-uso {
  display: inline-flex;
  align-items: center;
  padding: 0.16rem 0.5rem;
  border-radius: 999px;
  font-size: 0.67rem;
  font-weight: 700;
  color: #0b6b2d;
  background: #d4f7df;
  border: 1px solid #97e0b0;
}

.header-combo-contexto__hint {
  display: inline-block;
  margin-top: 0.35rem;
  color: var(--color-secondary-07, #555);
  font-size: 0.69rem;
}

.header-combo-contexto__erro {
  display: inline-block;
  margin-top: 0.35rem;
  color: #b50909;
}

.header-user-info {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.header-user-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary-default, #1351b4);
  background: color-mix(in srgb, var(--color-primary-default, #1351b4) 12%, white);
}

.header-user-icon {
  font-size: 1.75rem;
  color: var(--color-primary-default, #1351b4);
}

.header-user-dados {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.125rem;
}

.header-user-nome {
  font-weight: 600;
  font-size: 1rem;
  color: var(--primary-text-color);
}

.header-user-perfil {
  font-size: 0.75rem;
  max-width: 360px;
  color: var(--color-secondary-07, #555);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.header-btn-contexto {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  white-space: nowrap;
  padding: 0.38rem 0.65rem;
  font-size: 0.75rem;
  font-weight: 600;
  font-family: inherit;
  border: 1px solid var(--color-primary-default, #1351b4);
  border-radius: 999px;
  background: #fff;
  color: var(--color-primary-darken-01, #0c326f);
  cursor: pointer;
  transition:
    background-color 0.2s,
    color 0.2s,
    box-shadow 0.2s;
  flex-shrink: 0;
}

.header-btn-contexto:hover {
  background: var(--color-primary-default, #1351b4);
  color: #fff;
  box-shadow: 0 4px 12px rgba(19, 81, 180, 0.25);
}

.header-btn-contexto i {
  font-size: 0.7rem;
}

.header-btn-contexto__seta {
  font-size: 0.62rem !important;
}

.header-contexto-wrap {
  position: static;
}

[data-theme='dark'] .header-btn-contexto {
  border-color: var(--pure-0);
  color: var(--pure-0);
  background: rgba(255, 255, 255, 0.06);
}

[data-theme='dark'] .header-btn-contexto:hover {
  background: var(--color-primary-lighten-01, #4d7fd6);
  color: #fff;
}

[data-theme='dark'] .header-user {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.15);
}

[data-theme='dark'] .header-user-avatar {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.9);
}

[data-theme='dark'] .header-user-nome {
  color: rgba(255, 255, 255, 0.95);
}

[data-theme='dark'] .header-user-perfil {
  color: rgba(255, 255, 255, 0.65);
}

[data-theme='dark'] .header-user-icon {
  color: var(--color-primary-lighten-01, #4d7fd6);
}

[data-theme='dark'] .header-combo-contexto {
  background: var(--background);
  border-color: rgba(255, 255, 255, 0.18);
}

[data-theme='dark'] .header-combo-contexto__badge {
  color: rgba(255, 255, 255, 0.92);
  background: rgba(255, 255, 255, 0.14);
}

[data-theme='dark'] .header-combo-contexto__perfil-titulo {
  color: rgba(255, 255, 255, 0.75);
}

[data-theme='dark'] .header-combo-contexto__badge-uso {
  color: #d8ffe4;
  background: rgba(16, 128, 62, 0.38);
  border-color: rgba(128, 236, 168, 0.55);
}

[data-theme='dark'] .header-combo-contexto__hint {
  color: rgba(255, 255, 255, 0.7);
}

.contexto-panel-accordion-enter-active,
.contexto-panel-accordion-leave-active {
  overflow: hidden;
  transition:
    max-height 0.22s ease,
    padding-top 0.22s ease,
    padding-bottom 0.22s ease,
    border-width 0.22s ease;
}

.contexto-panel-accordion-enter-from,
.contexto-panel-accordion-leave-to {
  max-height: 0;
  padding-top: 0;
  padding-bottom: 0;
  border-width: 0;
}

.contexto-panel-accordion-enter-to,
.contexto-panel-accordion-leave-from {
  max-height: 320px;
}

.perfil-ativo {
  font-size: 0.85rem;
}

@media (max-width: 575px) {
  .header-user {
    justify-content: center;
    width: 100%;
    gap: 0.55rem;
  }

  .header-user-info {
    max-width: 120px;
  }

  .header-user-nome {
    font-size: 0.95rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .header-user-perfil {
    display: none;
  }

  .header-btn-contexto {
    padding: 0.35rem 0.58rem;
  }

  .header-combo-contexto {
    position: fixed;
    top: 72px;
    right: 0.4rem;
    left: 0.4rem;
    transform: none;
    min-width: unset;
    max-width: unset;
    width: auto;
  }

  .header-combo-contexto__titulo {
    font-size: 0.8rem;
  }
}

@media (max-width: 360px) {
  .header-btn-contexto__texto {
    display: none;
  }
}

.footer {
  margin: 1rem;
}

.logo-gov {
  height: 40px;
}
</style>
