<template>
  <AuthLayout>
    <section class="login-page" aria-labelledby="login-title">
      <div class="login-card">
        <!-- Logo e Cabeçalho -->
        <div class="login-card__header">
          <img
            src="/favicon.ico"
            alt="Logo NVSL"
            width="64"
            height="64"
            class="login-card__logo"
          />
          <h1 id="login-title" class="login-card__title">NVSL</h1>
          <p class="login-card__subtitle">Sistema de Gestão</p>
        </div>

        <!-- Mensagem de erro -->
        <br-message
          v-if="error"
          state="danger"
          :message="error"
          closable
          @close="clearError"
          role="alert"
          aria-live="assertive"
        />

        <!-- Corpo do card -->
        <div class="login-card__body">
          <p class="login-card__description">
            Para acessar o sistema, utilize sua conta
            <strong>GOV.BR</strong> — a identificação digital do cidadão
            brasileiro.
          </p>

          <!-- Botão de login GOV.BR -->
          <button
            type="button"
            class="br-sign-in"
            :disabled="isLoading"
            :aria-busy="isLoading"
            aria-label="Entrar com a conta GOV.BR"
            @click="handleLogin"
          >
            <span v-if="isLoading" aria-hidden="true">
              <br-loading size="small" />
            </span>
            <span v-else class="br-sign-in__content">
              <!-- Ícone GOV.BR -->
              <svg
                aria-hidden="true"
                focusable="false"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"
                />
              </svg>
              Entrar com
              <span class="br-sign-in__govbr">gov.br</span>
            </span>
          </button>

          <!-- Qualificação de nível de conta -->
          <p class="login-card__info">
            <small>
              Apenas contas com nível
              <strong>Prata</strong> ou <strong>Ouro</strong> têm acesso ao
              sistema.
            </small>
          </p>
        </div>

        <!-- Rodapé do card -->
        <div class="login-card__footer">
          <p>
            Problemas para acessar?
            <a
              href="https://www.gov.br/governodigital/pt-br/conta-gov-br/conta-gov-br"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Saiba mais sobre a conta GOV.BR (abre em nova aba)"
            >
              Saiba mais sobre o GOV.BR
            </a>
          </p>
        </div>
      </div>
    </section>
  </AuthLayout>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuth } from '@/composables/useAuth'

defineOptions({ name: 'LoginPage' })

const router = useRouter()
const { isLoading, error, isAuthenticated, login, clearError } = useAuth()

// Se já autenticado, redireciona (guard de rota também cobre isso)
if (isAuthenticated.value) {
  router.replace({ name: 'home' })
}

async function handleLogin() {
  await login()
}
</script>

<style scoped>
.login-page {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 2rem 1rem;
}

.login-card {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 440px;
}

.login-card__header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.login-card__logo {
  margin-bottom: 0.75rem;
}

.login-card__title {
  font-size: 2rem;
  font-weight: 700;
  color: var(--color-primary-default, #1351b4);
  margin: 0;
}

.login-card__subtitle {
  font-size: 1rem;
  color: var(--color-secondary-07, #555555);
  margin: 0.25rem 0 0;
}

.login-card__body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.login-card__description {
  text-align: center;
  color: var(--color-secondary-08, #333333);
  margin: 0;
  line-height: 1.6;
}

/* Botão padrão GOV.BR Sign-In */
.br-sign-in {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  padding: 0.875rem 1.5rem;
  background-color: var(--color-primary-default, #1351b4);
  color: #ffffff;
  border: none;
  border-radius: 100px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  gap: 0.5rem;
  transition:
    background-color 0.2s ease,
    transform 0.1s ease,
    box-shadow 0.2s ease;
  font-family: inherit;
  min-height: 48px; /* Alvo de toque acessível */
}

.br-sign-in:hover:not(:disabled) {
  background-color: var(--color-primary-darken01, #0e439b);
  box-shadow: 0 4px 12px rgba(19, 81, 180, 0.35);
}

.br-sign-in:active:not(:disabled) {
  transform: scale(0.98);
}

.br-sign-in:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.br-sign-in:focus-visible {
  outline: 3px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
}

.br-sign-in__content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.br-sign-in__govbr {
  font-weight: 800;
  letter-spacing: 0.02em;
}

.login-card__info {
  text-align: center;
  color: var(--color-secondary-06, #777777);
  margin: 0;
}

.login-card__footer {
  text-align: center;
  padding-top: 1.25rem;
  border-top: 1px solid var(--color-secondary-03, #e0e0e0);
  font-size: 0.875rem;
  color: var(--color-secondary-07, #555555);
}

.login-card__footer a {
  color: var(--color-primary-default, #1351b4);
  text-decoration: underline;
}

.login-card__footer a:hover {
  color: var(--color-primary-darken01, #0e439b);
}
</style>
