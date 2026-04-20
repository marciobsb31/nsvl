import { createRouter, createWebHistory } from 'vue-router'
import { solicitacaoRoutes } from '@/features/solicitacao-cadastro/solicitacaoCadastroRoutes'
import { gerenciarSolicitacaoCadastroRoutes } from '@/features/gerenciar-solicitacao-cadastro/gerenciarSolicitacaoCadastroRoutes'
import { useAuthStore } from '@/stores/authStore'
import { useNotification } from '@/core/composables/useNotification'
import { exibirGerenciarPerfis } from '@/core/config/featureFlags'

/**
 * Roteador principal da aplicação
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/features/autenticacao/pages/LoginPage.vue'),
      meta: { title: 'Entrar — NVSL', public: true },
    },
    {
      path: '/callback',
      name: 'callback',
      component: () => import('@/features/autenticacao/pages/LoginPage.vue'),
      meta: { title: 'Entrar — NVSL', public: true },
    },
    {
      path: '/',
      name: 'home',
      component: () => import('@/features/home/pages/HomePage.vue'),
      meta: {
        title: 'Início — NVSL',
      },
    },
    ...gerenciarSolicitacaoCadastroRoutes,
    ...solicitacaoRoutes,
    {
      path: '/relatorios',
      name: 'relatorios',
      component: () => import('@/features/relatorios/pages/RelatoriosPage.vue'),
      meta: { title: 'Relatórios — NVSL' },
    },
    {
      path: '/plano-acao',
      name: 'plano-acao',
      component: () => import('@/features/plano-acao/pages/PlanoAcaoPage.vue'),
      meta: { title: 'Plano de Ação — NVSL' },
    },
    {
      path: '/gestao-planos-acao',
      name: 'gestao-planos-acao',
      component: () => import('@/features/plano-acao/pages/PlanoAcaoPage.vue'),
      meta: { title: 'Gestão de Planos de ação — NVSL' },
    },
    {
      path: '/enviar-plano-acao',
      name: 'enviar-plano-acao',
      component: () => import('@/features/plano-acao/pages/PlanoAcaoPage.vue'),
      meta: { title: 'Enviar plano de ação — NVSL' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/features/erro/pages/NotFoundPage.vue'),
      meta: {
        title: 'Página não encontrada — NVSL',
      },
    },
  ],
})

// Navigation guard: título e autenticação
router.beforeEach(async (to) => {
  document.title = (to.meta.title as string) ?? 'NVSL'

  const publicRoutes = ['login', 'callback', 'solicitacao-cadastro']
  if (publicRoutes.includes(to.name as string)) return true

  const token = sessionStorage.getItem('nvsl_token')
  const authStore = useAuthStore()

  if (!token) {
    return { name: 'login' }
  }

  if (!exibirGerenciarPerfis && to.name === 'gerenciar-perfis') {
    return { name: 'gerenciar-cadastros' }
  }

  if (!authStore.user && to.name !== 'login') {
    try {
      const api = (await import('@/services/ApiService')).default
      const { data } = await api.get<Record<string, unknown>>('/usuario')
      authStore.setUser(data)
      // Seleciona automaticamente o perfil hierarquicamente mais alto
      const maisAlto = authStore.perfilHierarquicoMaisAlto
      if (maisAlto && maisAlto.id !== authStore.perfilAtivo?.id) {
        await authStore.trocarContexto(maisAlto.id)
      }
    } catch {
      sessionStorage.removeItem('nvsl_token')
      return { name: 'login' }
    }
  }

  if (to.meta.requiredModule && authStore.user) {
    const modulo = to.meta.requiredModule as string
    const temPermissao = authStore.temPermissao(modulo)
    if (!temPermissao) {
      const { error } = useNotification()
      error('Acesso não permitido.')
      return { name: 'home' }
    }
  }

  return true
})

export default router
