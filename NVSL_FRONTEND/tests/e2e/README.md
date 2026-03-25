# Testes E2E (Playwright)

Este diretório contém testes de fluxo completo para apresentação e validação funcional.

## Pré-requisitos

- Node.js instalado (versão compatível com o projeto)
- Frontend em execução
- Backend/API acessível para autenticação de teste

## Instalação inicial

```bash
npm install
npx playwright install chromium
```

## Executar em modo visível (ideal para apresentação)

```bash
npm run e2e:ui
```

## Executar um teste específico

```bash
npx playwright test tests/e2e/fluxo-demo-apresentacao.spec.ts --headed
```

## Debug passo a passo

```bash
npm run e2e:debug
```

## Abrir relatório HTML

```bash
npm run e2e:report
```

## Base URL do teste

Por padrão, o Playwright usa `http://127.0.0.1:5173`.

Se necessário, sobrescreva com:

```bash
set E2E_BASE_URL=http://127.0.0.1:5174 && npm run e2e:ui
```
