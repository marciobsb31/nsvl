<template>
  <aside class="sidebar" aria-label="Menu lateral">
    <div class="sidebar__menu">
      <h2 class="sidebar__titulo">Menu</h2>
      <nav class="sidebar__nav">
        <router-link to="/gerenciar-cadastros" class="sidebar__item" active-class="sidebar__item--ativo">
          Gerenciar Cadastros
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link to="/relatorios" class="sidebar__item" active-class="sidebar__item--ativo">
          Relatórios
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link to="/gestao-planos-acao" class="sidebar__item" active-class="sidebar__item--ativo">
          Gestão de Planos de ação
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link to="/enviar-plano-acao" class="sidebar__item" active-class="sidebar__item--ativo">
          Enviar plano de ação
        </router-link>
        <span class="sidebar__divisor" aria-hidden="true"></span>
        <router-link to="/gerenciar-perfis" class="sidebar__item" active-class="sidebar__item--ativo">
          Gerenciar Perfis
        </router-link>
      </nav>
    </div>
  </aside>
</template>

<script setup lang="ts">
defineOptions({ name: 'Sidebar' })
</script>

<style scoped>
.sidebar {
  width: 260px;
  min-width: 260px;
  background-color: var(--color-secondary-02, #f0f0f0);
  border-right: 1px solid var(--color-secondary-04, #ddd);
  padding: 1.5rem 0;
  flex-shrink: 0;
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
}

@media (min-width: 992px) {
  .sidebar.sidebar--aberto {
    /* mantém visível em desktop */
  }
}

.sidebar__menu {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.sidebar__titulo {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-secondary-08, #333);
  margin: 0 1rem 0.5rem;
  padding: 0;
}

.sidebar__nav {
  display: flex;
  flex-direction: column;
}

.sidebar__item {
  display: block;
  padding: 0.75rem 1rem;
  color: var(--color-secondary-08, #333);
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

.sidebar__divisor {
  display: block;
  height: 1px;
  background-color: var(--color-secondary-04, #ddd);
  margin: 0 1rem;
}
</style>
