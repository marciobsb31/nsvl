import type { RouteRecordRaw } from 'vue-router'

export const gerenciarSolicitacaoCadastroRoutes: RouteRecordRaw[] = [
  {
    path: '/gerenciar-cadastros',
    name: 'gerenciar-cadastros',
    component: () =>
      import('@/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue'),
    meta: {
      title: 'Gerenciar Cadastros — NVSL',
    },
  },
  {
    path: '/detalhar-solicitacao/:id',
    name: 'detalhar-solicitacao',
    component: () =>
      import('@/features/gerenciar-solicitacao-cadastro/pages/DetalherSolicitacaoCadastroPage.vue'),
    meta: {
      title: 'Detalhar/Avaliar Solicitação de Cadastro — NVSL',
    },
  },
]
