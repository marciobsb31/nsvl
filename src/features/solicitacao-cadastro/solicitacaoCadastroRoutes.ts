import type { RouteRecordRaw } from 'vue-router'

export const solicitacaoRoutes: RouteRecordRaw[] = [
  {
    path: '/solicitacao-cadastro',
    name: 'solicitacao-cadastro',
    component: () => import('@/features/solicitacao-cadastro/pages/SolicitacaoCadastroIndex.vue'),
    meta: {
      title: 'Solicitação de cadastro — NVSL',
      public: true,
    },
    beforeEnter: (to, _from, next) => {
      if (!to.query.nome || !to.query.cpf) {
        next({ name: 'login' })
      } else {
        next()
      }
    },
  },
]