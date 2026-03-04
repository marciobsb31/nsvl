# GOV.BR Design System — Guia de Uso

## Visão Geral

O **GOV.BR Design System** (`@govbr-ds`) é a biblioteca oficial de componentes e tokens de design do Governo Federal Brasileiro. A aplicação utiliza a versão baseada em **Web Components** com o wrapper Vue oficial.

## Pacotes Instalados

| Pacote | Finalidade |
|---|---|
| `@govbr-ds/core` | Tokens de design (cores, tipografia, espaçamentos) e estilos base |
| `@govbr-ds/webcomponents` | Web Components nativos (`<br-button>`, `<br-header>`, etc.) |
| `@govbr-ds/webcomponents-vue` | Wrapper Vue para os Web Components |

## Configuração

### Vite (`vite.config.ts`)
```typescript
vue({
  template: {
    compilerOptions: {
      isCustomElement: (tag) => tag.startsWith('br-')
    }
  }
})
```
Isso informa ao Vue que elementos `br-*` são Web Components nativos (não componentes Vue).

### CSS Global (`src/assets/styles/main.css`)
```css
@import '@govbr-ds/core/dist/core-tokens.min.css';
@import '@govbr-ds/core/dist/core.min.css';
```

### Registro Global (`src/main.ts`)
```typescript
import '@govbr-ds/webcomponents'
```

## Componentes Disponíveis

| Componente | Uso | Referência |
|---|---|---|
| `<br-header>` | Cabeçalho padrão GOV.BR | Layout |
| `<br-footer>` | Rodapé padrão GOV.BR | Layout |
| `<br-button>` | Botões | Formulários |
| `<br-input>` | Campo de texto | Formulários |
| `<br-message>` | Mensagens de alerta/erro | Feedback |
| `<br-loading>` | Indicador de carregamento | Feedback |
| `<br-tag>` | Rótulos/etiquetas | UI |
| `<br-card>` | Cartões de conteúdo | Layout |
| `<br-breadcrumb>` | Migalhas de pão | Navegação |
| `<br-pagination>` | Paginação | Tabelas/Listas |

## Tokens de Design

Os tokens CSS estão disponíveis como variáveis CSS em toda a aplicação:

```css
/* Cores principais */
--color-primary-default: #1351b4    /* Azul GOV.BR */
--color-primary-darken01: #0e439b
--color-support-05: #ffcd07         /* Amarelo (foco, destaque) */

/* Cores secundárias */
--color-secondary-01: #f8f8f8       /* Cinza muito claro (fundo) */
--color-secondary-07: #555555       /* Cinza médio (texto secundário) */
--color-secondary-09: #222222       /* Cinza escuro (texto principal) */
```

## Fontes

O GOV.BR DS utiliza as fontes **Rawline** e **Raleway**. Ambas são carregadas via Google Fonts no `index.html`.

## Documentação Oficial

- **Site**: https://www.gov.br/ds/home
- **Storybook**: https://www.gov.br/ds/components/atomo/button
- **NPM**: https://www.npmjs.com/org/govbr-ds
- **GitLab**: https://gitlab.com/govbr-ds/govbr-ds
