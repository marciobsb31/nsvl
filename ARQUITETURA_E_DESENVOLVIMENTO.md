# Arquitetura e Desenvolvimento do Frontend NVSL

## 1. Objetivo do repositorio

Este repositorio contem a interface web do NVSL. A aplicacao conversa com o backend via API HTTP e usa o fluxo de autenticacao mediado pelo Laravel.

Responsabilidades principais:

- telas publicas e autenticadas do NVSL
- integracao com o backend e com o fluxo GOV.BR
- persistencia do token e do contexto do usuario no navegador
- testes de componentes e testes E2E

## 2. Como subir o ambiente

### Modo oficial com Docker

O compose oficial do projeto fica em `../NVSL_DOCKER/docker-compose.yml`.

```bash
cd ../NVSL_DOCKER
docker compose up -d --build postgres postgres-test backend frontend
```

### Modo local de desenvolvimento do frontend

```bash
cd .
npm install
npm run dev
```

Referencia usual:

- Vite local: `http://localhost:5176` ou porta exibida pelo comando
- backend oficial: `http://localhost:8081`

Observacoes:

- o frontend pode rodar isolado em Vite, mas o backend oficial continua sendo o do compose em `NVSL_DOCKER`
- o arquivo `public/env-config.js` participa da configuracao de runtime e precisa permanecer coerente com o ambiente

## 3. Stack e arquitetura

### Stack principal

- Vue 3
- Vite
- TypeScript
- Vue Router
- Pinia
- Axios
- GOV.BR Design System
- Vitest para testes de unidade/componente
- Playwright para testes E2E

### Estrutura principal do codigo

- `src/main.ts`: bootstrap da aplicacao
- `src/router`: rotas e navegacao
- `src/layouts`: layouts principais
- `src/core`: componentes e utilitarios reutilizaveis
- `src/features`: modulos funcionais da aplicacao
- `src/services`: clientes e servicos HTTP
- `src/stores`: estado global com Pinia
- `src/assets`: estilos, temas e imagens
- `public/env-config.js`: configuracao de runtime exposta ao navegador

### Features observadas no repositorio

- `autenticacao`
- `solicitacao-cadastro`
- `gerenciar-solicitacao-cadastro`
- `gerenciar-perfis`
- `home`
- `relatorios`
- `plano-acao`
- `erro`

### Diretrizes de organizacao

- `core` concentra componentes e utilitarios transversais
- `features` concentra telas, componentes e regras proximas do dominio
- `services` deve centralizar integracoes com backend
- `stores` deve manter estado compartilhado e contexto do usuario

## 4. Configuracao e integracao com backend

Pontos importantes do frontend:

- a aplicacao consome a API do backend reconstruido em Laravel 13
- o token de autenticacao e mantido no navegador para chamadas autenticadas
- o contexto do usuario e refletido na UI a partir dos dados retornados pela API
- o arquivo `public/env-config.js` deve apontar para a base URL correta da API em cada ambiente

## 5. Testes

### Estrutura atual

- testes de unidade/componente proximos das features, por exemplo:
  - `src/features/gerenciar-perfis/pages/GerenciarPerfisPage.spec.ts`
  - `src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.spec.ts`
- testes E2E em:
  - `tests/e2e`

### Como rodar

Testes unitarios/componentes:

```bash
cd .
npm run test:run
```

Modo watch:

```bash
cd .
npm run test
```

E2E:

```bash
cd .
npm run e2e
```

### Coverage

```bash
cd .
npm run test:coverage
```

Diretrizes:

- testes de componente devem ficar proximos da feature testada
- cenarios E2E devem cobrir jornadas completas e evidencias relevantes
- ao alterar regra de negocio refletida na tela, atualizar teste unitario/componente e, quando aplicavel, E2E

## 6. Convencoes de desenvolvimento

### Desenvolvimento do dia a dia

- subir o backend oficial pelo compose
- rodar o frontend em Vite para iteracao rapida
- validar chamadas reais contra a API oficial do workspace

### Padrrao de versionamento

Diretriz adotada para o time:

1. partir sempre da branch `main`, que representa a referencia de producao
2. atualizar a `main` local ao inicio de cada sprint ou novo ciclo de trabalho
3. criar branch de trabalho a partir dessa `main` atualizada
4. devolver alteracoes via PR

Padrao sugerido de nomes:

- `feature/<tema>`
- `fix/<tema>`
- `chore/<tema>`

## 7. Checklist rapido antes de abrir PR

- frontend sobe em modo local
- integracao principal com backend esta funcional
- testes unitarios/componentes passam
- coverage foi revisado para as areas tocadas
- E2E criticos seguem coerentes com o comportamento atual
- `env-config.js` e demais configuracoes de runtime continuam alinhados ao ambiente alvo
