# Guia de Contribuição

## Padrões de Código (Clean Code)

### Princípios Gerais

- **Nomes descritivos**: variáveis, funções e componentes com nomes que expressam intenção
- **Funções pequenas**: cada função faz uma única coisa (Single Responsibility)
- **Sem comentários óbvios**: o código deve ser autoexplicativo
- **DRY** (Don't Repeat Yourself): extrair lógica duplicada em composables ou utilitários
- **Composables** para lógica reutilizável entre componentes

### Convenções de Nomenclatura

| Tipo | Convenção | Exemplo |
|---|---|---|
| Componentes Vue | PascalCase | `LoginPage.vue`, `DefaultLayout.vue` |
| Composables | camelCase com prefixo `use` | `useAuth.ts`, `useNotification.ts` |
| Stores | camelCase com sufixo `Store` | `authStore.ts` |
| Serviços | PascalCase com sufixo `Service` | `AuthService.ts` |
| Tipos/Interfaces | PascalCase | `GovBrUser`, `AuthState` |
| Variáveis CSS | kebab-case com prefixo `--` | `--color-primary-default` |

### Componentes Vue

```vue
<template>
  <!-- HTML semântico, aria-*, roles -->
</template>

<script setup lang="ts">
// 1. Imports (Vue, router, stores, composables, types)
// 2. defineOptions({ name: 'NomeDoComponente' })
// 3. Props e emits
// 4. Composables e stores
// 5. Estado reativo (ref/computed)
// 6. Funções (handlers)
</script>

<style scoped>
/* CSS com variáveis do GOV.BR DS */
</style>
```

## Fluxo de Trabalho com Git

```bash
# Criar branch a partir de main
git checkout -b feat/nome-da-feature

# Commits seguindo Conventional Commits
git commit -m "feat(auth): adiciona botão de logout no header"
git commit -m "fix(router): corrige redirect após callback OIDC"
git commit -m "docs(readme): atualiza instruções de setup"

# Enviar para origin
git push origin feat/nome-da-feature
# Abrir Pull Request para main
```

## Conventional Commits

| Tipo | Quando usar |
|---|---|
| `feat` | Nova funcionalidade |
| `fix` | Correção de bug |
| `docs` | Documentação |
| `style` | Formatação (sem lógica) |
| `refactor` | Refatoração sem nova feature |
| `test` | Adição de testes |
| `chore` | Tarefas de manutenção |

## Antes de Abrir um PR

```bash
# 1. Lint
npm run lint

# 2. Formatação
npm run format

# 3. Build de verificação
npm run build

# 4. Verificar acessibilidade com Lighthouse
# DevTools → Lighthouse → Acessibility → Gerar relatório
```

## Estrutura de Pastas

Não criar arquivos soltos na raiz de `src/`. Respeitar a estrutura:
- `pages/` — apenas componentes de página (um por rota)
- `components/` — componentes reutilizáveis
- `composables/` — lógica reutilizável (hooks)
- `services/` — integrações externas (API, OIDC)
- `stores/` — estado global (Pinia)
- `types/` — tipos e interfaces TypeScript
