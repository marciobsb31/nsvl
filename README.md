# NVSL — Frontend

Aplicação frontend do sistema **NVSL**, desenvolvida com **Vue 3 + Vite + TypeScript**, utilizando o [GOV.BR Design System](https://www.gov.br/ds/home) e autenticação via **GOV.BR SSO** (OAuth2/OIDC).

## Stack

- **Vue 3** (Composition API, `<script setup>`)
- **Vite 6** + TypeScript
- **Vue Router 4** com guards de rota
- **Pinia** para gerenciamento de estado
- **@govbr-ds** — Design System oficial do Governo Federal
- **oidc-client-ts** — Fluxo OAuth2/OIDC PKCE

## Quick Start

```bash
# Instalar dependências
npm install

# Copiar e editar variáveis de ambiente
cp .env.example .env

# Iniciar servidor de desenvolvimento
npm run dev
# → http://localhost:5173
```

## Scripts

| Comando | Objetivo |
|---|---|
| `npm run dev` | Servidor de desenvolvimento |
| `npm run build` | Build de produção |
| `npm run lint` | Lint do código |
| `npm run format` | Formatação automática |

## Docker

```bash
# A partir do repositório DOCKER
docker compose up --build -d
```

## Documentação

Acesse a documentação completa no repositório **DOCS**:

| Documento | Descrição |
|---|---|
| [Arquitetura](../DOCS/frontend/architecture.md) | Estrutura e padrões do projeto |
| [Setup](../DOCS/frontend/setup.md) | Instalação e configuração |
| [Autenticação GOV.BR](../DOCS/frontend/authentication.md) | Fluxo OIDC + PKCE |
| [Design System](../DOCS/frontend/design-system.md) | GOV.BR DS — uso e componentes |
| [Segurança](../DOCS/frontend/security.md) | Cabeçalhos, tokens, CSP |
| [Acessibilidade](../DOCS/frontend/accessibility.md) | WCAG 2.1 AA + eMAG |
| [Docker](../DOCS/frontend/docker.md) | Build e execução com Docker |
| [Contribuição](../DOCS/frontend/contributing.md) | Padrões e fluxo de trabalho |

## Variáveis de Ambiente

Veja [`.env.example`](.env.example) para todas as variáveis necessárias.

> **⚠️ Importante**: Nunca commitar o arquivo `.env` com valores reais.
