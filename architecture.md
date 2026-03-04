# Arquitetura da Aplicação — NVSL Frontend

## Visão Geral

O NVSL Frontend é uma Single Page Application (SPA) desenvolvida com **Vue 3** e **Vite**, seguindo princípios de Clean Code, segurança e acessibilidade. A aplicação consome o GOV.BR Design System e integra autenticação via GOV.BR SSO (OAuth2/OIDC).

## Stack Tecnológica

| Tecnologia | Versão | Finalidade |
|---|---|---|
| Vue 3 | ^3.5 | Framework reativo (Composition API) |
| Vite | ^6 | Build tool e dev server |
| TypeScript | ^5 | Tipagem estática |
| Vue Router | ^4 | Roteamento SPA |
| Pinia | ^2 | Gerenciamento de estado |
| @govbr-ds/core | latest | Tokens e estilos GOV.BR DS |
| @govbr-ds/webcomponents | latest | Web Components GOV.BR |
| @govbr-ds/webcomponents-vue | latest | Wrapper Vue para GOV.BR DS |
| oidc-client-ts | ^3 | Fluxo OAuth2/OIDC PKCE |
| Axios | ^1 | HTTP client |

## Estrutura de Pastas

```
src/
├── assets/
│   └── styles/
│       └── main.css          # Estilos globais + GOV.BR DS tokens
├── composables/
│   ├── useAuth.ts            # Composable de autenticação
│   └── useNotification.ts    # Composable de notificações
├── layouts/
│   ├── DefaultLayout.vue     # Layout padrão (autenticado)
│   └── AuthLayout.vue        # Layout de autenticação (login/callback)
├── pages/
│   ├── HomePage.vue          # Página inicial (protegida)
│   ├── LoginPage.vue         # Página de login GOV.BR
│   ├── CallbackPage.vue      # Callback OIDC
│   └── NotFoundPage.vue      # Página 404
├── router/
│   └── index.ts              # Configuração de rotas + navigation guards
├── services/
│   └── AuthService.ts        # Serviço OIDC (UserManager)
├── stores/
│   └── authStore.ts          # Estado global de autenticação (Pinia)
├── types/
│   └── auth.ts               # Tipos TypeScript para autenticação
├── App.vue                   # Componente raiz
└── main.ts                   # Entry point da aplicação
```

## Fluxo de Dados

```
main.ts → bootstrap()
    ├── createApp(App)
    ├── createPinia()
    ├── router
    └── authStore.loadUser()   → AuthService.getUser() → UserManager (OIDC)
                                                                  ↓
                                                        sessionStorage (tokens)
```

## Padrão Arquitetural

- **Composition API + Composables**: Lógica reutilizável encapsulada em `composables/`
- **Store (Pinia)**: Estado global centralizado para autenticação
- **Service Layer**: `AuthService.ts` encapsula toda lógica OIDC (single responsibility)
- **Layouts**: Separação clara entre layout de autenticação e layout autenticado
- **Lazy Loading**: Todas as páginas são carregadas sob demanda via `() => import()`

## Diagrama de Componentes

```
App.vue
└── RouterView
    ├── AuthLayout (login, callback)
    │   ├── LoginPage
    │   └── CallbackPage
    └── DefaultLayout (rotas protegidas)
        ├── HomePage
        └── NotFoundPage
```
