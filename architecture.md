# Arquitetura do Frontend NVSL

## Stack

| Tecnologia | Versão | Uso |
|---|---|---|
| Vue 3 | ^3.5 | Framework SPA |
| TypeScript | ~5.9 | Tipagem estática |
| Vite | ^7.3 | Build + Dev server |
| Vue Router | ^5.0 | Roteamento SPA |
| Pinia | ^3.0 | Estado global |
| Axios | ^1.13 | HTTP client |
| GovBR DS | ^3.7 | Design System |
| oidc-client-ts | ^3.4 | Suporte OIDC (futuro) |
| Vitest | ^3.0 | Testes unitários |

## Estrutura de Diretórios

```
src/
├── assets/           # Estilos globais e imagens
├── core/
│   ├── components/   # Componentes reutilizáveis
│   ├── composables/  # Hooks Vue (useAuth, etc.)
│   └── types/        # Types TypeScript globais (auth.ts)
├── features/         # Módulos de domínio
│   ├── autenticacao/ # Login + Callback pages
│   ├── home/         # Página inicial
│   ├── solicitacao-cadastro/
│   └── erro/         # 404
├── layouts/          # Layouts de página
├── router/           # Configuração de rotas + guards
├── services/
│   ├── ApiService.ts   # Axios singleton + interceptors
│   └── AuthService.ts  # Fluxo de auth GOV.BR
└── stores/
    └── authStore.ts    # Estado de autenticação (Pinia)
```

## Fluxo de Autenticação

1. Usuário acessa rota protegida → guard redireciona para `/login`
2. Clique em "Entrar" → `authStore.login()` → `GET /api/auth/redirect`
3. Backend retorna URL do SSO → `window.location.href = url`
4. GOV.BR autentica → redireciona para `/callback?code=...&state=...`
5. `CallbackPage` → `authStore.handleCallback()` → `GET /api/auth/callback`
6. Backend retorna `{ token, user }` → armazenado em `sessionStorage`
7. `ApiService` injeta `Authorization: Bearer {token}` em todas as requisições

## Testes

```bash
npm run test          # Modo watch
npm run test:run      # Execução única
npm run test:coverage # Com relatório de cobertura
```
