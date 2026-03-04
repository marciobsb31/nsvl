# Setup e Execução Local — NVSL Frontend

## Pré-requisitos

| Ferramenta | Versão mínima | Download |
|---|---|---|
| Node.js | 20.x LTS | https://nodejs.org |
| npm | 10.x | (incluído no Node.js) |

## Instalação

```bash
# 1. Clone o repositório FRONTEND
git clone <URL_DO_REPOSITORIO_FRONTEND>
cd FRONTEND

# 2. Instale as dependências
npm install

# 3. Configure as variáveis de ambiente
cp .env.example .env
# Edite .env com seus valores (especialmente VITE_GOVBR_CLIENT_ID)
```

## Variáveis de Ambiente

| Variável | Descrição | Obrigatória |
|---|---|---|
| `VITE_GOVBR_CLIENT_ID` | Client ID registrado no GOV.BR | ✅ |
| `VITE_GOVBR_REDIRECT_URI` | URI de callback após login | ✅ |
| `VITE_GOVBR_POST_LOGOUT_REDIRECT_URI` | URI de redirect após logout | ✅ |
| `VITE_GOVBR_SSO_URL` | URL base do SSO GOV.BR | ✅ |
| `VITE_GOVBR_SCOPES` | Escopos OIDC (ex: `openid email profile`) | ✅ |
| `VITE_APP_NAME` | Nome da aplicação | ❌ |
| `VITE_API_BASE_URL` | URL base da API backend | ❌ |

## Executar em Desenvolvimento

```bash
npm run dev
# Acesse http://localhost:5173
```

## Build de Produção

```bash
npm run build
# Arquivos gerados em /dist
```

## Executar Build Localmente

```bash
npm run preview
# Acesse http://localhost:4173
```

## Lint e Formatação

```bash
# Verificar e corrigir problemas de código
npm run lint

# Formatar código com Prettier
npm run format
```

## Estrutura de Scripts (package.json)

| Script | Comando | Descrição |
|---|---|---|
| `dev` | `vite` | Servidor de desenvolvimento com HMR |
| `build` | `vue-tsc -b && vite build` | Build de produção com verificação de tipos |
| `preview` | `vite preview` | Preview da build de produção |
| `lint` | `eslint .` | Lint com ESLint + oxlint |
| `format` | `prettier --write src/` | Formatação automática |
