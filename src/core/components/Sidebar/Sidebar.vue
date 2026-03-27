<template>
  <aside
    class="sidebar"
    :class="{ 'sidebar--recolhido': recolhido, 'sidebar--aberto': aberto }"
    aria-label="Menu lateral"
  >
    <div class="sidebar__menu">
      <div class="sidebar__cabecalho">
        <h2 v-if="!recolhido" class="sidebar__titulo">Menu</h2>
        <button
          v-if="mostrarToggle"
          type="button"
          class="sidebar__toggle"
          :aria-label="recolhido ? 'Expandir menu' : 'Recolher menu'"
          @click="$emit('toggle-recolher')"
        >
          <i class="fas" :class="recolhido ? 'fa-chevron-right' : 'fa-chevron-left'" aria-hidden="true"></i>
        </button>
      </div>
      <nav class="sidebar__nav">
        <router-link
          to="/gerenciar-cadastros"
          class="sidebar__item"
          active-class="sidebar__item--ativo"
          title="Gerenciar Cadastros"
        >
          <i class="fas fa-users sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">Gerenciar Cadastros</span>
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link to="/relatorios" class="sidebar__item" active-class="sidebar__item--ativo" title="Relatórios">
          <i class="fas fa-chart-bar sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">Relatórios</span>
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link
          to="/gestao-planos-acao"
          class="sidebar__item"
          active-class="sidebar__item--ativo"
          title="Gestão de Planos de ação"
        >
          <i class="fas fa-clipboard-list sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">Gestão de Planos de ação</span>
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link
          to="/enviar-plano-acao"
          class="sidebar__item"
          active-class="sidebar__item--ativo"
          title="Enviar plano de ação"
        >
          <i class="fas fa-paper-plane sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">Enviar plano de ação</span>
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link
          to="/gerenciar-perfis"
          class="sidebar__item"
          active-class="sidebar__item--ativo"
          title="Gerenciar Perfis"
        >
          <i class="fas fa-user-shield sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">Gerenciar Perfis</span>
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <button
          type="button"
          class="sidebar__item sidebar__item--btn"
          title="Sair"
          @click="handleSair"
          :disabled="saindo"
          aria-label="Sair"
        >
          <i class="fas fa-sign-out-alt sidebar__icon" aria-hidden="true"></i>
          <span v-if="!recolhido" class="sidebar__texto">{{ saindo ? 'Saindo...' : 'Sair' }}</span>
        </button>
      </nav>
    </div>
  </aside>
  
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'
import { useBreakpoint } from '@/core/composables/useBreakpoint'

defineOptions({ name: 'Sidebar' })

defineProps<{
  recolhido?: boolean
  aberto?: boolean
}>()

defineEmits<{
  (e: 'toggle-recolher'): void
}>()


const { isMobile } = useBreakpoint()
const mostrarToggle = computed(() => !isMobile.value)

const router = useRouter()
const { logout } = useAuth()
const saindo = ref(false)

async function handleSair() {
  saindo.value = true
  try {
    await logout()
    router.push({ name: 'home' })
  } finally {
    saindo.value = false
  }
}
</script>

<style scoped>
.sidebar {
  width: 260px;
  min-width: 260px;
  background-color: var(--background);
  border-right: 1px solid var(--color-secondary-04, #ddd);
  padding: 1.5rem 0;
  flex-shrink: 0;
  transition: width 0.25s ease, min-width 0.25s ease;
}

/* Desktop: recolhido */
@media (min-width: 992px) {
  .sidebar.sidebar--recolhido {
    width: 64px;
    min-width: 64px;
  }

  .sidebar.sidebar--recolhido .sidebar__titulo,
  .sidebar.sidebar--recolhido .sidebar__texto {
    opacity: 0;
    overflow: hidden;
    width: 0;
    padding: 0;
    margin: 0;
  }

  .sidebar.sidebar--recolhido .sidebar__divisor {
    display: none;
  }

  .sidebar.sidebar--recolhido .sidebar__item {
    justify-content: center;
    padding: 0.75rem;
  }

  .sidebar.sidebar--recolhido .sidebar__icon {
    margin-right: 0;
  }

  .sidebar.sidebar--recolhido .sidebar__cabecalho {
    justify-content: center;
    margin: 0 0.5rem 0.5rem;
  }
}

/* Mobile: drawer lateral */
@media (max-width: 991px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 998;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    box-shadow: 4px 0 16px rgba(0, 0, 0, 0.1);
  }

  .sidebar.sidebar--aberto {
    transform: translateX(0);
  }

  .sidebar__toggle {
    display: none;
  }
}

.sidebar__menu {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.sidebar__cabecalho {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 0 1rem 0.5rem;
  min-height: 2rem;
}

.sidebar__titulo {
  font-size: 1rem;
  font-weight: 600;
  color: var(--primary-text-color);
  margin: 0;
  padding: 0;
}

.sidebar__toggle {
  width: 36px;
  height: 36px;
  border: none;
  background: none;
  color: var(--primary-text-color);
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s, color 0.2s;
}

.sidebar__toggle:hover {
  background: var(--color-primary-pastel, #e8f4fc);
  color: var(--color-primary-default, #1351b4);
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
}

.sidebar__item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: var(--primary-text-color);
  text-decoration: none;
  font-weight: 500;
  transition: background-color 0.2s, color 0.2s;
}

.sidebar__item:hover {
  background-color: var(--color-secondary-03, #e8e8e8);
  color: var(--color-primary-default, #1351b4);
}

.sidebar__item--ativo {
  background-color: var(--color-primary-pastel, #e8f4fc);
  color: var(--color-primary-default, #1351b4);
}

.sidebar__icon {
  flex-shrink: 0;
  width: 1.25rem;
  margin-right: 0.75rem;
  text-align: center;
}

.sidebar__texto {
  white-space: nowrap;
}

.sidebar__divisor {
  display: block;
  height: 1px;
  background-color: var(--color-secondary-04, #ddd);
  margin: 0 1rem;
}

.sidebar__item--btn {
  width: 100%;
  text-align: left;
  border: none;
  background: none;
  cursor: pointer;
  font-family: inherit;
  font-size: inherit;
}

.sidebar__item--btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}
</style>
