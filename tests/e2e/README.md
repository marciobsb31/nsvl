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

## Evidências em vídeo — Gerenciar cadastros

Roteiro e requisitos: `docs/REQUISITOS-E2E-GERENCIAR-CADASTROS.md` (na raiz do repositório).

```bash
npx playwright test tests/e2e/gerenciar-cadastros-evidencias.spec.ts --headed
```

## Base URL do teste

Por padrão, o Playwright usa `http://127.0.0.1:5174` (mesma porta do `vite.config.ts`).

Se necessário, sobrescreva com:

```powershell
$env:E2E_BASE_URL = "http://127.0.0.1:5173"
npx playwright test tests/e2e/gerenciar-cadastros-evidencias.spec.ts --headed
```
