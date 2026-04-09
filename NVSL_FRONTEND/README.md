# NVSL Frontend

Aplicacao web do NVSL em Vue 3 + Vite + TypeScript.

O guia principal do repositorio esta em `ARQUITETURA_E_DESENVOLVIMENTO.md`.

Atalhos rapidos:

- instalar dependencias: `npm install`
- subir modo dev local: `npm run dev`
- testes unitarios/componentes: `npm run test:run`
- coverage: `npm run test:coverage`
- e2e: `npm run e2e`

Observacoes importantes:

- o compose oficial do projeto e somente `../NVSL_DOCKER/docker-compose.yml`
- `public/env-config.js` participa da configuracao de runtime
- a autenticacao consumida pelo frontend depende do backend Laravel 13 ativo em `NVSL_BACKEND`
