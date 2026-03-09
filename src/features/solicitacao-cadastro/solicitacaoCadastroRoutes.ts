import type { RouteRecordRaw } from 'vue-router'

export const solicitacaoRoutes: RouteRecordRaw[] = [
  {
    path: '/solicitacao-cadastro',
    component: () => import('@/features/solicitacao-cadastro/pages/SolicitacaoCadastroIndex.vue'),    
  },
]