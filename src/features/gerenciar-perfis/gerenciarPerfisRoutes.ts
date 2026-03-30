import type { RouteRecordRaw } from 'vue-router'

export const gerenciarPerfisRoutes: RouteRecordRaw[] = [
  {
    path: '/gerenciar-perfis',
    name: 'gerenciar-perfis',
    component: () => import('./pages/GerenciarPerfisEmDesenvolvimentoPage.vue'),
    meta: { title: 'Gerenciar perfis de acesso — NVSL', requiredModule: 'Gerenciar Perfis' },
  },
]
