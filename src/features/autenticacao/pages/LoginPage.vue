<template>
  <main id="main-content" class="layout-auth__main" tabindex="-1">
    <button class="br-button circle small contraste" type="button" aria-label="Tema Dark" title="Alternar tema"><i class="fas fa-adjust"
        aria-hidden="true" @click="toggleTheme"></i>
    </button>
    <section class="login-page" aria-labelledby="login-title">
      <Card custom-class="login-card">
        <div class="row">
          <div class="col-lg-6 col-sm-12 logos" v-if="isMobile">
            <img :src="logoNovoViver" alt="Logo" class="logo-novo-viver" />
          </div>
          <div class="col-lg-6 col-sm-12 acessos">
            <button class="br-button primary block" type="button" :disabled="isLoading" :aria-busy="isLoading"
              aria-label="Entrar com a conta GOV.BR" @click="handleLogin">Entrar com o GOV.BR
            </button>
            <button class="br-button success block" type="button">Solicitar Cadastro
            </button>
          </div>
          <div class="col-lg-6 col-sm-12 logos" :class="{ 'border-left': !isMobile }">
            <img v-if="!isMobile" :src="logoNovoViver" alt="Logo" class="logo-novo-viver" />
            <img :src="logoGov" alt="Logo Branca" class="logo-gov" />
          </div>
        </div>

      </Card>
    </section>
  </main>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/core/composables/useAuth'
import Card from '@/core/components/Card/Card.vue'
import logoGovColor from '@/assets/images/logo/mdh_com_gov.png'
import logoGovBranca from '@/assets/images/logo/mdh_com_gov_branca.png'
import logo from '@/assets/images/logo/logo_novo_viver.png'
import logoBranca from '@/assets/images/logo/logo_novo_viver_branca.png'
import { useTheme } from '@/core/composables/useTheme'
import { useBreakpoint } from '@/core/composables/useBreakpoint'



defineOptions({ name: 'LoginPage' })

const router = useRouter()
const { isLoading, error, isAuthenticated, login, clearError } = useAuth()
const { mode, setMode } = useTheme()
const { isMobile } = useBreakpoint()

// Se já autenticado, redireciona (guard de rota também cobre isso)
if (isAuthenticated.value) {
  router.replace({ name: 'home' })
}

const logoNovoViver = computed(() => {
  return mode.value === 'dark' ? logoBranca : logo;
});

const logoGov = computed(() => {
  return mode.value === 'dark' ? logoGovBranca : logoGovColor;
});

async function handleLogin() {
  await login()
}

const toggleTheme = () => {
  setMode(mode.value === 'dark' ? 'light' : 'dark');
};

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
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
  padding: 0px !important;
  width: 100%;
  max-width: 756px;
}

.login-card :deep(.card-content) {
  padding-top: 0px !important;
  padding-bottom: 0px !important;
}

.layout-auth {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background-color: var(--color-secondary-01, #f8f8f8);
}

.layout-auth__main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  background-color: var(--background-gray);
  height: 100vh;
}

.acessos {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 0rem 3rem;
}

.logo-novo-viver {
  width: 100%;
  max-width: 250px;
}

.logo-gov {
  width: 100%;
  max-width: 250px;
}

.logos {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 3rem;
}

.border-left {
  border-left: 8px solid var(--border-login-color) !important;
}

.row {
  height: 350px;
}

.contraste{
  position: absolute;
  top: 1rem;
  right: 1rem;
}
</style>
