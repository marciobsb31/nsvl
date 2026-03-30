# NVSL — Frontend

Aplicação frontend do sistema **NVSL**, desenvolvida com **Vue 3 + Vite + TypeScript**, utilizando o [GOV.BR Design System](https://www.gov.br/ds/home) e autenticação via **GOV.BR SSO** mediada pelo **backend** (OAuth2/OIDC + PKCE no Laravel).

## Stack

- **Vue 3** (Composition API, `<script setup>`)
- **Vite** + TypeScript
- **Vue Router** com guards de rota
- **Pinia** para gerenciamento de estado
- **@govbr-ds** — Design System oficial do Governo Federal
- **Axios** — chamadas à API; token Sanctum em `sessionStorage`

## Quick Start

```bash
# Instalar dependências
npm install

# Copiar e editar variáveis de ambiente
cp .env.example .env

# Iniciar servidor de desenvolvimento
npm run dev
# → http://localhost:5176 (porta fixa em vite.config.ts)
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

> **Importante:** Nunca commitar o arquivo `.env` com valores reais.

# Arquitetura do Projeto

## Estrutura Geral

    src/
    ├── main.ts
    ├── App.vue
    ├── router/
    ├── config/
    ├── styles/
    ├── core/
    └── features/
    └── stores/
    └── services/

### src/

A pasta `src` é responsável por **criar, configurar e iniciar o
aplicativo Vue**.

Tudo aqui é **global**, ou seja, não pertence a nenhuma feature ou
domínio específico.

------------------------------------------------------------------------

# Core

A camada **core** representa a **infraestrutura transversal da
aplicação**.

Ela fornece **serviços e ferramentas reutilizáveis por todos os
módulos**, sem conter regras de negócio.

É o **coração técnico do projeto**, responsável por garantir:

-   Consistência
-   Reutilização
-   Isolamento entre features

## Estrutura interna

    core/
    ├── http/
    ├── utils/
    ├── types/
    ├── plugins/
    ├── composables/
    └── components/

### http/

Centraliza toda a **comunicação HTTP da aplicação**.

Responsabilidades:

-   Configuração do cliente (Axios ou Fetch)
-   Interceptadores
-   Tratamento de erros
-   Autenticação
-   Headers globais

------------------------------------------------------------------------

### utils/

Concentra **funções utilitárias puras e genéricas**, independentes do
Vue e de regras de negócio.

Exemplos:

-   Formatações
-   Cálculos
-   Validações
-   Helpers diversos

------------------------------------------------------------------------

### types/

Armazena **tipos e interfaces TypeScript globais**, utilizadas em todo o
projeto.

Não deve conter **tipagens específicas de módulos**.

------------------------------------------------------------------------

### plugins/

Contém **plugins Vue e integrações globais**, instalados apenas uma vez
na aplicação.

Exemplos:

-   Vue plugins
-   Configuração global de bibliotecas
-   Integrações externas

------------------------------------------------------------------------

### composables/

Reúne **composables reutilizáveis (`use*`)** voltados a comportamentos
ou estados comuns, sem lógica de domínio.

Exemplos:

-   `useWindowSize`
-   `useModal`
-   `usePagination`

------------------------------------------------------------------------

### components/

Componentes **genéricos e reutilizáveis**, que podem ser utilizados por
qualquer feature.

------------------------------------------------------------------------

# Stores

Define a **infraestrutura global de estado usando Pinia**, incluindo:

-   Instância principal do store
-   Plugins do Pinia
-   Persistência de estado

Nenhum **store de domínio** deve residir aqui.\
Apenas a **base global de configuração**.

------------------------------------------------------------------------

# Features

Camada responsável pelos **módulos funcionais da aplicação**.

Cada pasta dentro de `features` representa uma **feature ou domínio
independente**, com sua própria estrutura.

## Exemplo

    features/
    └── users/
        ├── components/
        ├── pages/
        ├── usersRoutes.ts
        └── index.ts

Cada módulo deve conter:

  Pasta          Responsabilidade
  -------------- -----------------------------------------
  `components`   Componentes locais da feature
  `pages`        Páginas acessadas por rotas
  `routes.ts`    Definição de rotas com lazy loading
  `services`     Comunicação com API e regras de negócio

Essa estrutura garante:

-   Isolamento entre domínios
-   Reutilização
-   Carregamento sob demanda

------------------------------------------------------------------------

# Layouts

Define **templates de layout usados no sistema**.

Cada layout controla a aparência e comportamento de um conjunto de
páginas.

Exemplos:

-   Layout padrão com menu lateral
-   Layout público (login, onboarding)
-   Layout administrativo com breadcrumbs

------------------------------------------------------------------------

# Assets

Contém arquivos **estáticos e de identidade visual** do sistema.

Exemplos:

-   Ícones
-   Logos
-   Imagens
-   Fontes
-   Arquivos SVG
-   Recursos gráficos JSON

------------------------------------------------------------------------

# Benefícios da Arquitetura

-   Modularidade e isolamento entre domínios
-   Reutilização de código entre features
-   Escalabilidade
-   Fácil manutenção
-   Separação clara entre camadas (infraestrutura × domínio)
-   Suporte nativo a lazy loading
-   Otimização de build

------------------------------------------------------------------------

# Convenções e Boas Práticas

## Nomeação de Pastas e Arquivos

  ------------------------------------------------------------------------
  Tipo                    Convenção                Exemplo
  ----------------------- ------------------------ -----------------------
  Pastas                  `kebab-case`             `user-profile`,
                                                   `order-details`

  Componentes             `PascalCase.vue`         `UserCard.vue`

  Stores                  `PascalCase.store.ts`    `UserStore.ts`

  Services                `camelCase.service.ts`   `userService.ts`

  Composables             prefixo `use`            `usePagination.ts`,
                                                   `useFetch.ts`

  Interfaces              PascalCase + `Interface` `PerfilInterface`,
                                                   `UsuarioInterface`
  ------------------------------------------------------------------------

------------------------------------------------------------------------

# Estrutura de Componentes

Cada componente deve:

-   Ter **propósito claro**
-   Ser **reutilizável**
-   Seguir **arquitetura atômica** quando aplicável
-   Conter apenas **estilos scoped**
-   Ter **props tipadas**
-   Ter **emits definidos** (`defineEmits`)

------------------------------------------------------------------------

# Boas Práticas de Código

-   Usar **Composition API (`setup`)** como padrão
-   Tipar todas as props e variáveis
-   Evitar dependências circulares entre features
-   Evitar lógica de negócio dentro de componentes
-   Centralizar chamadas HTTP em **services**
-   Utilizar **lazy loading de rotas**
-   Reutilizar composables globais antes de criar novos

------------------------------------------------------------------------

# Organização de Imports

Ordem recomendada:

1.  Módulos externos (`vue`, `pinia`, `axios`, etc.)
2.  Imports da camada `core/`
3.  Imports de `features/`
4.  Imports relativos locais (`./`, `../`)

------------------------------------------------------------------------

# Estrutura de Store (Pinia)

Cada store deve:

-   Ser criado com `defineStore`
-   Seguir o padrão **FeatureNameStore**
-   Separar claramente:

```{=html}
<!-- -->
```
    state
    getters
    actions

-   Ter **tipagem explícita de estado**

------------------------------------------------------------------------

# Convenção de Rotas

Nome das rotas:

    feature-name-action

Exemplos:

    users-list
    orders-edit
    orders-details

Lazy loading:

``` ts
() => import('../pages/UsersPage.vue')
```

Cada módulo define suas próprias rotas:

    FeatureRoutes.ts

As rotas são agregadas dinamicamente em:

    router/index.ts

------------------------------------------------------------------------

# Organização de Estilos

-   Utilizar **GOV DS** como base de estilização
-   Centralizar variáveis e tokens em `styles/`
-   Manter consistência visual entre features
-   Evitar estilos inline
-   Respeitar o design system definido (cores, espaçamento e tipografia)

