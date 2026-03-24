# Funcionalidade: Gerenciar Perfis (HU #22938)

## 1. Resumo

Implementacao completa da tela **Gerenciar Perfis** conforme prototipo, com painel lateral deslizante para **Cadastrar**, **Editar**, **Visualizar** e **Historico** de perfis de acesso. Permissoes granulares com tabela Selecionar/Funcionalidade/Acao.

**Branch:** `feature/22938-cadastrar-perfil`
**Repositorios:** NVSL_BACKEND, NVSL_FRONTEND

---

## 2. Historia de Usuario

> Como administrador do NVSL,
> Quero gerenciar perfis de acesso (cadastrar, editar, visualizar e consultar historico),
> Para definir permissoes e operacionalizar a gestao de usuarios no sistema.

---

## 3. Arquivos Criados/Modificados

### 3.1 Backend (NVSL_BACKEND)

| Arquivo | Tipo | Descricao |
|---------|------|-----------|
| `database/migrations/2026_03_23_000016_add_status_esfera_to_perfis_table.php` | Novo | Colunas `esfera` e `status` na tabela `perfis` |
| `database/migrations/2026_03_23_000017_create_permissoes_table.php` | Novo | Tabela `permissoes` (modulo, acao, descricao) |
| `database/migrations/2026_03_23_000018_create_perfil_permissao_table.php` | Novo | Tabela pivot `perfil_permissao` |
| `app/Models/Perfil.php` | Modificado | Campos `esfera`, `status` e relacao `permissoes()` |
| `app/Models/Permissao.php` | Novo | Model de permissoes do sistema |
| `app/Http/Controllers/GerenciarPerfilController.php` | Novo | Controller com `index`, `show`, `store`, `update`, `historico`, `permissoes` |
| `app/Http/Requests/CadastrarPerfilRequest.php` | Novo | FormRequest de validacao (unicidade, esfera, status) |
| `database/seeders/PermissaoSeeder.php` | Novo | 12 permissoes em 4 modulos |
| `routes/api.php` | Modificado | 6 rotas: GET/POST/PUT/GET(id)/GET(historico)/GET(permissoes) |

### 3.2 Frontend (NVSL_FRONTEND)

| Arquivo | Tipo | Descricao |
|---------|------|-----------|
| `src/features/gerenciar-perfis/pages/GerenciarPerfisPage.vue` | Reescrito | Listagem com tabela (Nome, Tipo, Situacao, Acoes) + painel lateral |
| `src/features/gerenciar-perfis/components/PainelFormularioPerfil.vue` | Novo | Painel para Cadastrar/Editar/Visualizar |
| `src/features/gerenciar-perfis/components/PainelHistoricoPerfil.vue` | Novo | Painel com tabela de historico de alteracoes |
| `src/features/gerenciar-perfis/components/SeletorPermissoes.vue` | Reescrito | Tabela Selecionar/Funcionalidade/Nome da acao |
| `src/features/gerenciar-perfis/gerenciarPerfisRoutes.ts` | Simplificado | Rota unica (painel lateral, sem paginas separadas) |
| `src/services/GerenciarPerfilService.ts` | Reescrito | Service com listar, obter, cadastrar, atualizar, historico, permissoes |
| `src/router/index.ts` | Mantido | Integrado rotas de gerenciar perfis |

---

## 4. Endpoints da API

| Metodo | Rota | Descricao | Auth |
|--------|------|-----------|------|
| `GET` | `/api/gerenciar-perfis` | Listar perfis (filtros: nome, esfera, status) | Sanctum |
| `GET` | `/api/gerenciar-perfis/{id}` | Detalhar perfil | Sanctum |
| `POST` | `/api/gerenciar-perfis` | Cadastrar novo perfil | Sanctum |
| `PUT` | `/api/gerenciar-perfis/{id}` | Atualizar perfil existente | Sanctum |
| `GET` | `/api/gerenciar-perfis/{id}/historico` | Historico de alteracoes do perfil | Sanctum |
| `GET` | `/api/gerenciar-perfis/permissoes` | Listar permissoes disponiveis | Sanctum |

### 4.1 POST /api/gerenciar-perfis — Payload

```json
{
  "nome": "Gestor Estadual",
  "descricao": "Perfil para gestao estadual",
  "esfera": "estadual",
  "status": "ativo",
  "permissoes": [1, 2, 5, 6]
}
```

### 4.2 PUT /api/gerenciar-perfis/{id} — Payload

```json
{
  "nome": "Gestor Estadual Editado",
  "descricao": "Descricao atualizada",
  "esfera": "estadual",
  "status": "ativo",
  "permissoes": [1, 2, 5]
}
```

### 4.3 GET /api/gerenciar-perfis/{id}/historico — Resposta

```json
{
  "data": [
    {
      "id": 1187,
      "data_hora": "23/03/2026 20:15:48",
      "usuario": "Maria Silva Federal",
      "perfil": "Gestor Estadual Teste",
      "atualizacao": "Atualizacao de permissoes (Gerenciar Cadastros: Visualizar, Criar; Gerenciar Perfis: Visualizar)",
      "action": "gerenciar_perfis.editar"
    }
  ]
}
```

### 4.4 GET /api/gerenciar-perfis/permissoes — Resposta

```json
{
  "data": [
    { "id": 1, "funcionalidade": "Gerenciar Cadastros", "nome_acao": "Visualizar", "descricao": "..." },
    { "id": 2, "funcionalidade": "Gerenciar Cadastros", "nome_acao": "Criar", "descricao": "..." }
  ]
}
```

### 4.5 Respostas de Erro

| Status | Cenario | Corpo |
|--------|---------|-------|
| 201 | Cadastro OK | `{ "message": "Perfil cadastrado com sucesso.", "data": {...} }` |
| 200 | Edicao OK | `{ "message": "Perfil atualizado com sucesso.", "data": {...} }` |
| 422 | Nome duplicado | `{ "errors": { "nome": ["Ja existe um perfil com este nome."] } }` |
| 403 | Sem permissao / hierarquia | `{ "message": "Acesso nao permitido." }` |
| 404 | Perfil nao encontrado | `{ "message": "Perfil nao encontrado." }` |
| 401 | Nao autenticado | `{ "message": "Nao autenticado." }` |

---

## 5. Modelo de Dados

### 5.1 Tabela `perfis` (alterada)

| Coluna | Tipo | Default | Descricao |
|--------|------|---------|-----------|
| `esfera` | varchar(20) | 'federal' | federal/estadual/municipal |
| `status` | varchar(10) | 'ativo' | ativo/inativo |

### 5.2 Tabela `permissoes` (nova)

| Coluna | Tipo | Descricao |
|--------|------|-----------|
| `id` | bigint PK | ID |
| `modulo` | varchar(100) | Funcionalidade (ex: "Gerenciar Cadastros") |
| `acao` | varchar(100) | Nome da acao (ex: "Visualizar", "Criar") |
| `descricao` | varchar(255) | Descricao da permissao |

UNIQUE(modulo, acao)

### 5.3 Tabela `perfil_permissao` (nova — pivot)

| Coluna | Tipo | Descricao |
|--------|------|-----------|
| `perfil_id` | FK -> perfis | Perfil |
| `permissao_id` | FK -> permissoes | Permissao |

UNIQUE(perfil_id, permissao_id)

### 5.4 Permissoes Pre-cadastradas (12)

| Funcionalidade | Acoes |
|----------------|-------|
| Gerenciar Cadastros | Visualizar, Criar, Aprovar, Reprovar |
| Gerenciar Perfis | Visualizar, Criar, Editar |
| Relatorios | Visualizar, Exportar |
| Plano de Acao | Visualizar, Criar, Enviar |

---

## 6. Regras de Hierarquia

| Esfera do Usuario | Pode cadastrar/editar perfis |
|--------------------|------------------------------|
| Federal (Nacional) | Federal, Estadual, Municipal |
| Estadual | Apenas Estadual |
| Municipal | Apenas Municipal |

---

## 7. Comportamento da Interface (conforme prototipo)

### 7.1 Tela Gerenciar Perfis (Listagem)
- Tabela com colunas: **Nome do Perfil** | **Tipo de Perfil** | **Situacao** | **Acoes**
- "Tipo de Perfil" exibe: Nacional/Estadual/Municipal (mapeado de federal/estadual/municipal)
- "Situacao" exibe tag: **Vigente** (verde) ou **Nao Vigente** (vermelho)
- Coluna **Acoes** com 3 botoes: **Visualizar** | **Editar** | **Historico**
- Botao **"+ Novo Perfil"** no topo direito

### 7.2 Painel Lateral — Cadastrar Perfil
- Abre deslizando da direita (overlay escuro)
- Titulo: "Cadastrar Perfil"
- Campos: Nome do Perfil, Tipo de Perfil (dropdown), Situacao (dropdown Vigente/Nao Vigente)
- Descricao: textarea
- Permissoes: tabela com Selecionar (checkbox) | Funcionalidade | Nome da acao
- Botoes: Fechar | Salvar

### 7.3 Painel Lateral — Editar Perfil
- Mesmo layout do cadastro, preenchido com dados do perfil
- Titulo: "Editar Perfil" + subtitulo com nome do perfil
- Campos editaveis, checkboxes de permissoes marcados conforme perfil
- Botoes: Fechar | Salvar

### 7.4 Painel Lateral — Visualizar Perfil
- Mesmo layout, todos os campos desabilitados (somente leitura)
- Titulo: "Visualizar Perfil"
- Checkboxes desabilitados
- Apenas botao "Fechar"

### 7.5 Painel Lateral — Historico do Perfil
- Titulo: "Historico do Perfil" + subtitulo com nome
- Tabela: **Data/Hora** | **Usuario** | **Perfil** | **Atualizacao** | **Acoes**
- Mostra registros de auditoria com descricao das alteracoes
- Botoes: Voltar (centro) e Fechar (rodape)

---

## 8. Auditoria

| Operacao | Action | Tipo | Tabela |
|----------|--------|------|--------|
| Cadastrar | `gerenciar_perfis.cadastrar` | `insert` | `perfis` |
| Editar | `gerenciar_perfis.editar` | `update` | `perfis` |
| Listar | `gerenciar_perfis.listagem` | `view` | `perfis` |

O historico de alteracoes descreve automaticamente:
- Alteracao de nome
- Alteracao de tipo de perfil
- Alteracao de situacao
- Atualizacao de permissoes (lista detalhada)

---

## 9. Criterios de Aceite — Verificacao

| Criterio | Status |
|----------|--------|
| Cadastro segue hierarquia (federal/estadual/municipal) | OK |
| Campo Nome obrigatorio | OK |
| Nome do perfil unico (bloquear duplicidade) | OK |
| Status obrigatorio com default "Ativo" (Vigente) | OK |
| Permissoes sao persistidas conforme selecao | OK |
| Salvar registra log de auditoria | OK |
| Editar atualiza dados e registra log com alteracoes | OK |
| Visualizar exibe dados somente leitura | OK |
| Historico exibe registros de auditoria ordenados | OK |
| Interface conforme prototipo (painel lateral) | OK |
| Tabela de permissoes: Selecionar/Funcionalidade/Nome da acao | OK |
| Tags "Vigente"/"Nao Vigente" na listagem | OK |
| Botoes Visualizar/Editar/Historico na coluna Acoes | OK |

---

## 10. Cenarios Testados via API

| Cenario | Resultado |
|---------|-----------|
| Criar perfil com todos os campos | 201 - "Perfil cadastrado com sucesso." |
| Atualizar perfil | 200 - "Perfil atualizado com sucesso." |
| Consultar historico | 200 - Registros de auditoria com descricao |
| Listar permissoes (formato tabela) | 200 - funcionalidade + nome_acao |
| Nome duplicado | 422 - "Ja existe um perfil com este nome." |
| Frontend build (type-check + vite) | Sucesso, sem erros TS |

---

## 11. Como Testar Localmente

```bash
# 1. Branch
git checkout feature/22938-cadastrar-perfil

# 2. Subir containers
cd NVSL_DOCKER
docker compose up --build -d

# 3. Migrations + seeder
docker exec nvsl-backend php artisan migrate --force
docker exec nvsl-backend php artisan db:seed --class=PermissaoSeeder --force

# 4. Acessar
# Frontend: http://localhost:5174
# API: http://localhost:8081/api/gerenciar-perfis
```

---

## 12. Funcionalidades Existentes Preservadas

Nenhuma funcionalidade existente foi alterada ou removida:
- Gerenciar Solicitacoes de Cadastro: intacto
- Solicitacao de Cadastro (publica): intacta
- Login GOV.BR: intacto
- PerfilController (GET /perfis): intacto — rota separada de /gerenciar-perfis
- Sidebar: link "Gerenciar Perfis" aponta para listagem funcional
