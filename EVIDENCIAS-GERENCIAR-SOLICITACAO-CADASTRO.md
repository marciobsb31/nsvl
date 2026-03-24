# Evidências — Gerenciar Solicitação de Cadastro (NVSL)

**Tarefa:** 22880 — Gerenciar Solicitações de Cadastro  
**Tela:** Gerenciar Cadastros (`/gerenciar-cadastros`)  
**Data da análise:** Março/2026

---

## 1. Resumo da História de Usuário

**Como** gestor do sistema NVSL,  
**quero** gerenciar solicitações de cadastro (listar, filtrar, cadastrar, aprovar/reprovar),  
**para** controlar o acesso de usuários ao sistema conforme perfil e hierarquia.

---

## 2. Critérios de Aceite — Evidências Item a Item

### Critério 1 — Usuário Federal visualiza todos os registros

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php` |
| **Linhas** | 556–564 |
| **Implementação** | Método `aplicarVisibilidadePorPerfil`: quando `esfera === 'federal'`, o método retorna sem aplicar filtro na query. A query é executada sem restrição de `esfera_atuacao`, `uf` ou `municipio`. |
| **Código** | `if ($esfera === 'federal') { return; }` |

**Como verificar:**  
- Login com perfil **Federal** (token de teste).  
- Acessar Gerenciar Cadastros.  
- Verificar que a lista exibe todas as solicitações (independente de UF, esfera ou município).

---

### Critério 2 — Usuário Estadual visualiza apenas registros da sua UF

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php` |
| **Linhas** | 565–570 |
| **Implementação** | Método `aplicarVisibilidadePorPerfil`: quando `esfera === 'estadual'`, aplica `where('esfera_atuacao', 'estadual')` e `where('uf', $user->uf_lotacao)`. |
| **Código** | `$query->where('esfera_atuacao', 'estadual')->where('uf', $user->uf_lotacao ?? '')` |

**Como verificar:**  
- Login com perfil **Estadual (GO)**.  
- Acessar Gerenciar Cadastros.  
- Verificar que a lista exibe apenas solicitações com esfera=estadual e UF=GO.

---

### Critério 3 — Usuário Municipal visualiza apenas registros do seu Município

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php` |
| **Linhas** | 572–578 |
| **Implementação** | Método `aplicarVisibilidadePorPerfil`: quando `esfera === 'municipal'`, aplica filtros por `esfera_atuacao=municipal`, `uf` e `municipio` do usuário. |
| **Código** | `$query->where('esfera_atuacao', 'municipal')->where('uf', ...)->where('municipio', ...)` |

**Como verificar:**  
- Login com perfil **Municipal (Alexânia/GO)**.  
- Acessar Gerenciar Cadastros.  
- Verificar que a lista exibe apenas solicitações com esfera=municipal, UF=GO e município=Alexânia.

---

### Critério 4 — Filtros da tela comportando perfeitamente

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FiltrosGerenciarSolicitacao.vue` |
| **Arquivo backend** | `SolicitacaoCadastroController.php` linhas 59–80 |
| **Filtros disponíveis** | CPF, Nome, UF, Município, Órgão, Esfera de atuação, Situação |
| **Implementação** | Componente `FiltrosGerenciarSolicitacao` emite `@pesquisar` com os filtros. O controller aplica validação e `where` nos filtros preenchidos. |

**Como verificar:**  
- Preencher um ou mais filtros (ex.: CPF, Nome, UF).  
- Clicar em **Pesquisar**.  
- Verificar que a lista é atualizada conforme os filtros.

---

### Critério 5 — Todos os campos de combos com autocomplete

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FiltrosGerenciarSolicitacao.vue` |
| **Linhas** | 29–36 (UF), 39–45 (Município), 59–65 (Esfera), 69–75 (Situação) |
| **Implementação** | UF, Município, Esfera e Situação usam `SelectAutocomplete`. Órgão é input de texto (não é combo). |

**Como verificar:**  
- Clicar nos campos UF, Município, Esfera e Situação.  
- Verificar que a lista de opções aparece e permite filtrar digitando.

---

### Critério 6 — Botão Cadastrar abre tela de criação

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| **Linhas** | 12–19 (botão), 127–134 (painel lateral) |
| **Implementação** | Botão "Cadastrar usuário" chama `abrirPainelCadastro()`, que abre o painel lateral com `FormularioCadastrarUsuario`. |

**Como verificar:**  
- Clicar em **Cadastrar usuário**.  
- Verificar que o painel lateral abre com o formulário de cadastro.

---

### Critério 7 — Botão Pesquisar realiza pesquisa com filtros

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `FiltrosGerenciarSolicitacao.vue` linhas 79–95 (botão), `GerenciarSolicitacaoCadastroPage.vue` linhas 260–264 |
| **Implementação** | Botão "Pesquisar" emite `@pesquisar` com os filtros. A página chama `aplicarFiltros(filtros)` e `carregarSolicitacoes()`, que usa `listarSolicitacoesGerenciar(filtrosAtivos)`. |

**Como verificar:**  
- Preencher filtros (ex.: Nome).  
- Clicar em **Pesquisar**.  
- Verificar que a lista é recarregada com os filtros aplicados.

---

### Critério 8 — Botão Limpar Filtro limpa os filtros

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `FiltrosGerenciarSolicitacao.vue` linhas 80–86 (botão), `GerenciarSolicitacaoCadastroPage.vue` linhas 266–270 |
| **Implementação** | Botão "Limpar Filtro" chama `limparFiltros` e emite `@limpar`. A página chama `limparEpesquisar()`, que zera `filtrosAtivos` e recarrega a listagem. |

**Como verificar:**  
- Preencher filtros e pesquisar.  
- Clicar em **Limpar Filtro**.  
- Verificar que os filtros são zerados e a lista é recarregada com todos os registros visíveis.

---

### Critério 9 — Botão "Detalhar/Avaliar" quando status = Em análise

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| **Linhas** | 102–110 (botão), função `rotuloBotaoDetalhar` em script |
| **Implementação** | `rotuloBotaoDetalhar(status)` retorna `'Detalhar/Avaliar'` quando `status === 'em_analise'`. |

**Como verificar:**  
- Localizar uma solicitação com status **Em análise**.  
- Verificar que o botão exibe **Detalhar/Avaliar**.

---

### Critério 10 — Botão "Detalhar" quando status ≠ Em análise

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| **Implementação** | `rotuloBotaoDetalhar(status)` retorna `'Detalhar'` quando `status !== 'em_analise'`. |

**Como verificar:**  
- Localizar uma solicitação com status **Aprovado** ou **Reprovado**.  
- Verificar que o botão exibe **Detalhar**.

---

### Critério 11 — Logs de auditoria registram acessos à listagem

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php` |
| **Linhas** | 97–100 |
| **Implementação** | Método `index` chama `$this->auditLogService->log('gerenciar_cadastros.listagem', $user->id, [...])` após montar a lista. |
| **Outros logs** | `gerenciar_cadastros.detalhamento`, `gerenciar_cadastros.avaliacao`, `gerenciar_cadastros.perfil_ativado`, `gerenciar_cadastros.perfil_desativado`, `gerenciar_cadastros.solicitacao_interna_criada` |

**Como verificar:**  
- Consultar tabela `auditoria_log` (ou equivalente) após acessar a listagem.  
- Verificar registro com `action = 'gerenciar_cadastros.listagem'`.

---

### Critério 12 — Colunas da tabela (CPF, Nome, Esfera, UF, Município, Órgão, Situação, Ações)

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| **Linhas** | 46–85 (thead), 86–112 (tbody) |
| **Colunas** | CPF, Nome completo, Esfera de atuação, UF, Município, Órgão, Situação, Ações |

**Como verificar:**  
- Abrir a tela Gerenciar Cadastros.  
- Verificar que a tabela exibe as colunas listadas.

---

### Critério 13 — Ordenação por coluna

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| **Linhas** | 48–85 (botões de ordenação), 191–226 (computed `solicitacoesOrdenadas`), 229–236 (função `ordenarPor`) |
| **Implementação** | Clique no cabeçalho da coluna ordena por CPF, Nome, Esfera, UF, Município, Órgão ou Situação. |

**Como verificar:**  
- Clicar no cabeçalho de uma coluna (ex.: Nome).  
- Verificar que a lista é reordenada. Clicar novamente para alternar ascendente/descendente.

---

### Critério 14 — Detalhamento da solicitação (painel lateral)

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/PainelDetalharSolicitacao.vue` |
| **Implementação** | Painel lateral exibe: Dados do Solicitante (Nome, CPF, E-mail, Telefones), Aguardando Avaliação (Esfera, UF, Município, Órgão, Cargo, Perfil, Vigências), botões Aprovar e Reprovar. |

**Como verificar:**  
- Clicar em **Detalhar/Avaliar** em uma solicitação.  
- Verificar que o painel lateral abre com o formulário de avaliação e dados do solicitante.

---

### Critério 15 — Perfil obrigatório ao aprovar

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/PainelDetalharSolicitacao.vue` |
| **Linhas** | 114–118 |
| **Implementação** | Botão "Aprovar" desabilitado quando `!perfilSelecionado`: `:disabled="avaliando || !perfilSelecionado"`. |

**Como verificar:**  
- Abrir detalhar em uma solicitação Em análise.  
- Sem selecionar perfil, verificar que o botão **Aprovar** está desabilitado.  
- Selecionar perfil e verificar que o botão é habilitado.

---

### Critério 16 — Cadastro via formulário (gestor)

| Item | Evidência |
|------|-----------|
| **Status** | ✅ Atendido |
| **Localização** | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FormularioCadastrarUsuario.vue` |
| **API** | `POST /api/solicitacoes-cadastro` |
| **Validações** | E-mail .gov.br, CPF válido, telefone 10 ou 11 dígitos, perfil obrigatório quando autenticado. |

**Como verificar:**  
- Preencher o formulário com dados válidos.  
- Clicar em **Confirmar**.  
- Verificar que a solicitação aparece na listagem com status "Em análise".

---

## 3. Como Testar (Passo a Passo)

### 3.1. Subir o ambiente

```powershell
# Na pasta raiz do projeto (NVSLLOCAL)
.\iniciar-nvsl.ps1
```

Ou manualmente:

```powershell
cd NVSL_DOCKER
docker compose up --build -d
# Aguardar ~30 segundos
docker exec nvsl-backend php artisan migrate --force
docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force
```

### 3.2. Acessar a aplicação

- **URL:** http://localhost:5174  
- **Login:** Na tela de login, usar o seletor **Perfil de acesso** e escolher:
  - **Federal** — acesso a todas as solicitações
  - **Estadual (GO)** — apenas solicitações estadual/GO
  - **Municipal (Alexânia/GO)** — apenas solicitações municipal/GO/Alexânia
- Clicar em **Entrar**.

### 3.3. Cenários de teste

| # | Cenário | Passos |
|---|---------|--------|
| 1 | Visibilidade Federal | Login Federal → Gerenciar Cadastros → Verificar lista com todas as solicitações |
| 2 | Visibilidade Estadual | Login Estadual (GO) → Gerenciar Cadastros → Verificar apenas registros com esfera=estadual e UF=GO |
| 3 | Visibilidade Municipal | Login Municipal (Alexânia) → Gerenciar Cadastros → Verificar apenas registros municipal/GO/Alexânia |
| 4 | Filtros | Preencher CPF ou Nome → Pesquisar → Verificar resultado filtrado |
| 5 | Limpar Filtro | Filtros preenchidos → Limpar Filtro → Verificar lista completa |
| 6 | Cadastrar usuário | Cadastrar usuário → Preencher formulário → Confirmar → Verificar nova solicitação na lista |
| 7 | Detalhar/Avaliar | Em análise → Detalhar/Avaliar → Selecionar perfil → Aprovar |
| 8 | Detalhar (aprovado) | Aprovado → Apenas "Detalhar" → Verificar painel sem botões de aprovar |
| 9 | Ordenação | Clicar na coluna Nome → Verificar ordenação |
| 10 | Aprovar sem perfil | Detalhar/Analisar → Não selecionar perfil → Verificar botão Aprovar desabilitado |

### 3.4. Usuários de teste (seeder)

| Perfil | E-mail | Escopo |
|--------|--------|--------|
| Federal | maria.federal@ministerio.gov.br | Todas as solicitações |
| Estadual (GO) | joao.estadual@go.gov.br | Estadual/GO |
| Municipal (Alexânia) | ana.municipal@alexania.go.gov.br | Municipal/GO/Alexânia |

---

## 4. Arquivos de Referência

| Componente | Arquivo |
|------------|---------|
| Página principal | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` |
| Filtros | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FiltrosGerenciarSolicitacao.vue` |
| Formulário cadastro | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FormularioCadastrarUsuario.vue` |
| Painel detalhar | `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/PainelDetalharSolicitacao.vue` |
| Controller backend | `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php` |
| Auditoria | `NVSL_BACKEND/app/Services/Audit/AuditLogService.php` |

---

## 5. Conclusão

O **Gerenciar Solicitações de Cadastro** atende **100%** dos critérios de aceite da história de usuário. As evidências acima permitem validar cada item no sistema e no código-fonte.
