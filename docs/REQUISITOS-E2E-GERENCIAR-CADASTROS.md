# Requisitos cobertos pelos testes E2E — Gerenciar cadastros (NVSL)

Documento de rastreabilidade entre requisitos funcionais da tela **Gerenciar solicitação de cadastros no sistema** (`/gerenciar-cadastros`) e os cenários automatizados em Playwright.

Arquivo de teste: `NVSL_FRONTEND/tests/e2e/gerenciar-cadastros-evidencias.spec.ts`

## Pré-requisitos para gravar evidências

1. **Backend** Laravel acessível na URL configurada no frontend (proxy/API), com rota de login de teste `POST /auth/token-de-teste` ativa.
2. **Frontend** Vite em execução (porta padrão do projeto: **5174**).
3. Dependências: `npm install` e `npx playwright install chromium` dentro de `NVSL_FRONTEND`.

## Requisitos (RF)

| ID | Descrição | Evidência (teste Playwright) |
|----|-----------|------------------------------|
| **RF-01** | Usuário autenticado via perfil de teste **Federal** deve ser direcionado para a rota de gerenciamento de cadastros após login. | `RF-01 a RF-03` — step “Autenticação…” |
| **RF-02** | A tela deve exibir o título **“Gerenciar solicitação de cadastros no sistema”** e o botão **Cadastrar usuário**. | `RF-01 a RF-03` — step “Título da página…” |
| **RF-03** | Devem estar disponíveis filtros principais (ex.: CPF, Nome) e as ações **Limpar filtros** e **Pesquisar solicitações**. | `RF-01 a RF-03` — step “Bloco de filtros…” |
| **RF-04** | Ao acionar **Pesquisar**, o sistema deve responder com estado de listagem: carregamento, **tabela de solicitações** ou **mensagem** de lista vazia / orientação. | `RF-04 — Pesquisa e área de resultados` |
| **RF-05** | Ao acionar **Cadastrar usuário**, deve abrir o fluxo com o título **Cadastrar usuário** (formulário/painel). | `RF-05 — Abrir painel Cadastrar usuário` |

## Vídeos das evidências

Com `video: 'on'` em `playwright.config.ts`, cada teste gera um arquivo **`.webm`** após a execução.

Local típico (Windows / Playwright 1.x):

```text
NVSL_FRONTEND/test-results/
  gerenciar-cadastros-evidencias-Evidências—Gerenciar-solicitação-de-cadastros-RF-01-a-RF-03—chromium/
    video.webm
  gerenciar-cadastros-evidencias-Evidências—Gerenciar-solicitação-de-cadastros-RF-04—chromium/
    video.webm
  gerenciar-cadastros-evidencias-Evidências—Gerenciar-solicitação-de-cadastros-RF-05—chromium/
    video.webm
```

Os nomes exatos das pastas podem variar conforme a versão do Playwright; use o relatório HTML para abrir o vídeo associado a cada teste.

## Comando para rodar as evidências novamente

**Antes:** deixe o **Vite** (`npm run dev`) e o **backend** da API rodando; sem isso o teste falha em `page.goto` ou no login de teste.

Na pasta **`NVSL_FRONTEND`**:

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_FRONTEND
npm run e2e:evidencias-gerenciar-cadastros
```

Equivalente direto com Playwright:

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_FRONTEND
npx playwright test tests/e2e/gerenciar-cadastros-evidencias.spec.ts --headed
```

Com URL base explícita (se o Vite estiver em outra porta):

```powershell
$env:E2E_BASE_URL = "http://127.0.0.1:5174"
npx playwright test tests/e2e/gerenciar-cadastros-evidencias.spec.ts --headed
```

Gerar relatório HTML e abrir (após os testes):

```powershell
npx playwright show-report
```

Modo interface gráfica do Playwright (depuração):

```powershell
npm run e2e:ui
```

(Selecione o arquivo `gerenciar-cadastros-evidencias.spec.ts` na UI.)

---

*Última atualização: alinhado ao frontend NVSL (Vue + Vite + Playwright).*
