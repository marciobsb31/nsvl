import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { solicitacaoRoutes } from '@/features/solicitacao-cadastro/solicitacaoCadastroRoutes'

/**
 * Roteador principal da aplicação
 *
 * Rotas protegidas usam meta.requiresAuth = true
 * O navigation guard garante que apenas usuários autenticados
 * acessem rotas protegidas, redirecionando para /login caso contrário.
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/features/autenticacao/pages/LoginPage.vue'),
      meta: {
        title: 'Entrar — NVSL',
        requiresGuest: true, // só para não autenticados
      },
    },
    {
      path: '/callback',
      name: 'callback',
      component: () => import('@/features/autenticacao/pages/CallbackPage.vue'),
      meta: {
        title: 'Autenticando — NVSL',
      },
    },
    {
      path: '/',
      name: 'home',
      component: () => import('@/features/home/pages/HomePage.vue'),
      meta: {
        title: 'Início — NVSL',
        requiresAuth: true,
      },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/features/erro/pages/NotFoundPage.vue'),
      meta: {
        title: 'Página não encontrada — NVSL',
      },
    },
    ...solicitacaoRoutes
  ],
})

// Navigation guard global: autenticação e título de página
router.beforeEach(async (to) => {
  // Atualiza o título da página (acessibilidade)
  document.title = (to.meta.title as string) ?? 'NVSL'

  const authStore = useAuthStore()

  // Garante que o estado de auth foi carregado
  if (!authStore.isAuthenticated && !authStore.isLoading) {
    await authStore.loadUser()
  }

  // Rota protegida e usuário não autenticado → redireciona para login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' }
  }

  // Rota de guest e usuário já autenticado → redireciona para home
  if (to.meta.requiresGuest && authStore.isAuthenticated) {
    return { name: 'home' }
  }
})

export default router
