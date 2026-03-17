<template>
  <DefaultLayout>
    <section class="home-page" aria-labelledby="home-title">

      <!-- Saudação ao usuário -->
      <div class="home-page__welcome">
        <h1 id="home-title" class="home-page__title">
          Bem-vindo, {{ userName || 'Usuário' }}!
        </h1>
        <p class="home-page__subtitle">
          Você está conectado ao <strong>NVSL</strong> com sua conta GOV.BR.
        </p>

        <!-- Badge de nível de conta -->
        <div class="home-page__badge" aria-label="Nível da conta GOV.BR">
          <br-tag text="Conta GOV.BR" type="success" />
        </div>
      </div>

      <!-- Cartões de funcionalidades (exemplo) -->
      <div class="home-page__grid" role="list" aria-label="Funcionalidades disponíveis">
        <article
          class="feature-card br-card"
          role="listitem"
          tabindex="0"
          aria-labelledby="card-perfil-title"
        >
          <div class="card-content">
            <span class="feature-card__icon" aria-hidden="true">👤</span>
            <h2 id="card-perfil-title" class="feature-card__title">Meu Perfil</h2>
            <p class="feature-card__desc">Visualize e atualize seus dados pessoais.</p>
          </div>
        </article>

        <article
          class="feature-card br-card"
          role="listitem"
          tabindex="0"
          aria-labelledby="card-gerenciar-title"
          @click="irParaGerenciarCadastros"
        >
          <div class="card-content">
            <span class="feature-card__icon" aria-hidden="true">📋</span>
            <h2 id="card-gerenciar-title" class="feature-card__title">Gerenciar Cadastros</h2>
            <p class="feature-card__desc">Consulte e gerencie as solicitações de cadastro.</p>
          </div>
        </article>

        <article
          class="feature-card br-card"
          role="listitem"
          tabindex="0"
          aria-labelledby="card-docs-title"
        >
          <div class="card-content">
            <span class="feature-card__icon" aria-hidden="true">📄</span>
            <h2 id="card-docs-title" class="feature-card__title">Documentos</h2>
            <p class="feature-card__desc">Gerencie e consulte seus documentos.</p>
          </div>
        </article>
      </div>
    </section>
  </DefaultLayout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAuth } from '@/core/composables/useAuth'

defineOptions({ name: 'HomePage' })

const router = useRouter()
const { userName } = useAuth()

function irParaGerenciarCadastros() {
  router.push({ name: 'gerenciar-cadastros' })
}
</script>

<style scoped>
.home-page {
  padding: 1rem 0;
}

.home-page__welcome {
  margin-bottom: 2.5rem;
}

.home-page__title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0 0 0.5rem;
}

@media (min-width: 576px) {
  .home-page__title {
    font-size: 1.75rem;
  }
}

.home-page__subtitle {
  font-size: 1.1rem;
  color: var(--color-secondary-08, #333333);
  margin: 0 0 1rem;
}

.home-page__badge {
  display: inline-block;
}

.home-page__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 576px) {
  .home-page__grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1.25rem;
  }
}

@media (min-width: 992px) {
  .home-page__grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }
}

.feature-card {
  cursor: pointer;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
  border-radius: 8px;
}

.feature-card:hover,
.feature-card:focus {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.feature-card:focus-visible {
  outline: 3px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
}

.card-content {
  padding: 1.5rem;
  text-align: center;
}

.feature-card__icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 0.75rem;
}

.feature-card__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-primary-default, #1351b4);
  margin: 0 0 0.5rem;
}

.feature-card__desc {
  font-size: 0.9rem;
  color: var(--color-secondary-07, #555555);
  margin: 0;
  line-height: 1.5;
}
</style>
