import { createRouter, createWebHistory } from 'vue-router'
import { solicitacaoRoutes } from '@/features/solicitacao-cadastro/solicitacaoCadastroRoutes'
import { gerenciarSolicitacaoCadastroRoutes } from '@/features/gerenciar-solicitacao-cadastro/gerenciarSolicitacaoCadastroRoutes'

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
      },
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
      path: '/gerenciar-perfis',
      name: 'gerenciar-perfis',
      component: () => import('@/features/gerenciar-perfis/pages/GerenciarPerfisPage.vue'),
      meta: { title: 'Gerenciar Perfis — NVSL' },
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('@/features/erro/pages/NotFoundPage.vue'),
      meta: {
        title: 'Página não encontrada — NVSL',
      },
    }
  ],
})

// Navigation guard global: título de página
router.beforeEach((to) => {
  document.title = (to.meta.title as string) ?? 'NVSL'
})

export default router
