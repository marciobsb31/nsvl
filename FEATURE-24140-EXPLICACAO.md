# Feature 24140 — Backend Solicitação de Cadastro e Validação de Login

## Resumo

Esta feature implementa o backend da **Solicitação de Cadastro** e a **validação de login** do sistema NVSL (Novo Viver Sem Limite), garantindo que apenas usuários com cadastro ativo e perfil vigente possam acessar o sistema após autenticação via gov.br.

---

## 1. Contexto e Objetivo

O sistema NVSL utiliza autenticação exclusiva via gov.br. Esta feature garante que:

- Novos usuários possam solicitar cadastro por meio de um formulário
- Apenas usuários cadastrados e com perfil vigente possam acessar o sistema
- As regras de negócio sejam aplicadas no backend antes de liberar o acesso

---

## 2. O que foi implementado

### 2.1 Banco de Dados (Migrations)

Foram criadas três novas tabelas, seguindo padrões Laravel e nomenclatura em português:

| Tabela | Descrição |
|--------|-----------|
| **perfis** | Cadastro de perfis do sistema (ex.: Administrador, Gestor, Usuário) |
| **perfil_usuario** | Associação entre usuários e perfis, com datas de vigência (início e fim) |
| **solicitacoes_cadastro** | Registro das solicitações de acesso ao sistema |

**Campos principais:**

- **perfis**: `id`, `nome`, `descricao`, `created_at`, `updated_at`
- **perfil_usuario**: `id`, `usuario_id`, `perfil_id`, `data_inicio_vigencia`, `data_fim_vigencia`, `created_at`, `updated_at`
- **solicitacoes_cadastro**: `id`, `cpf_hash`, `nome`, `email_institucional`, `telefone_institucional`, `telefone_pessoal`, `esfera_atuacao`, `uf`, `municipio`, `orgao`, `cargo`, `status`, `created_at`, `updated_at`

O CPF é armazenado apenas como hash (HMAC-SHA256) por questões de segurança e LGPD.

---

### 2.2 API de Solicitação de Cadastro

**Endpoint:** `POST /api/solicitacoes-cadastro`

- **Público** (não requer autenticação)
- Recebe os dados do formulário de solicitação
- Valida CPF, e-mail institucional (.gov.br), telefones etc.
- Verifica se já existe solicitação em análise para o mesmo CPF
- Verifica se o CPF já está cadastrado como usuário
- Persiste a solicitação com status `em_analise`

**Payload esperado:**

```json
{
  "nome": "string",
  "CPF": "string",
  "emailInstitucional": "string",
  "telefoneInstitucional": "string",
  "telefonePessoal": "string (opcional)",
  "esferaAtuacao": "federal|estadual|municipal",
  "uf": "string (2 caracteres)",
  "municipio": "string",
  "orgao": "string",
  "cargo": "string"
}
```

---

### 2.3 Validação de Login (Regras de Negócio)

Após a autenticação no gov.br, o sistema aplica as seguintes validações antes de liberar o acesso:

| Situação | Ação | Mensagem |
|----------|------|----------|
| Usuário não cadastrado | Bloqueia acesso | "Solicitar acesso e aguardar avaliação" |
| Solicitação em análise | Bloqueia acesso | "Solicitação de acesso em análise." |
| Usuário sem perfil vigente | Bloqueia acesso | "Seu usuário não possui perfil ativo no sistema." |
| Usuário com perfil vigente | Libera acesso | Token Sanctum emitido |

**Perfil vigente:** usuário possui registro em `perfil_usuario` com `data_inicio_vigencia` ≤ hoje e (`data_fim_vigencia` é nula ou ≥ hoje).

---

### 2.4 Arquivos Criados/Modificados

**Novos arquivos:**
- `app/Models/Perfil.php` — Model de perfis
- `app/Models/SolicitacaoCadastro.php` — Model de solicitações
- `app/Services/Auth/AuthValidationService.php` — Serviço de validação de acesso
- `app/Http/Controllers/SolicitacaoCadastroController.php` — Controller da API
- `app/Http/Requests/SolicitacaoCadastroRequest.php` — Validação da requisição
- `database/migrations/2024_01_01_000004_create_perfis_table.php`
- `database/migrations/2024_01_01_000005_create_perfil_usuario_table.php`
- `database/migrations/2024_01_01_000006_create_solicitacoes_cadastro_table.php`
- `database/seeders/PerfilSeeder.php` — Perfis iniciais (Administrador, Gestor, Usuário)

**Arquivos modificados:**
- `app/Models/User.php` — Relação com perfis e método `possuiPerfilVigente()`
- `app/Http/Controllers/Auth/GovBrAuthController.php` — Integração com `AuthValidationService` no callback
- `routes/api.php` — Rota `POST /solicitacoes-cadastro`
- `Dockerfile-backend` — Correção de line endings (CRLF) no entrypoint para Windows

---

## 3. Fluxo de Uso

### Solicitação de Cadastro
1. Usuário acessa a tela de Solicitação de Cadastro
2. Preenche o formulário (nome, CPF, e-mail .gov.br, telefones, esfera, UF, município, órgão, cargo)
3. Envia a solicitação
4. Sistema valida e grava com status `em_analise`
5. Usuário aguarda análise e aprovação (processo futuro)

### Login
1. Usuário clica em "Entrar com GOV.BR"
2. Autentica no gov.br
3. Backend recebe o callback com dados do usuário (CPF via `sub`)
4. `AuthValidationService` verifica: usuário cadastrado? perfil vigente? solicitação em análise?
5. Se aprovado: emite token Sanctum e retorna dados do usuário
6. Se bloqueado: retorna mensagem de erro (422) conforme a regra

---

## 4. Segurança

- CPF nunca armazenado em texto claro (apenas hash irreversível)
- E-mail institucional obrigatoriamente com domínio .gov.br
- Validação de CPF (dígitos verificadores) no frontend
- Auditoria de tentativas de acesso negado via `AuditLogService`

---

## 5. Branches

- **Backend:** `feature/24140-backend-solicitacao-cadastro`
- **Frontend:** `feature/24140-frontend-solicitacao-cadastro`

---

## 6. Deploy

As migrations são executadas automaticamente no **entrypoint** do container Docker ao iniciar. Não é necessário rodar `php artisan migrate` manualmente em ambiente containerizado.
