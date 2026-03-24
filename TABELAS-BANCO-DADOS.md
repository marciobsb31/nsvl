# Tabelas do Banco de Dados NVSL

O projeto usa **PostgreSQL**. Banco: `nvsl`, usuário: `nvsl_user`.

---

## Tabelas

| Tabela | Descrição |
|--------|-----------|
| `ufs` | Unidades Federativas (estados) — estrutura IBGE |
| `municipios` | Municípios brasileiros — estrutura IBGE |
| `esferas` | Esferas de atuação (Federal, Estadual, Municipal) |
| `users` | Usuários do sistema (SSO GOV.BR) |
| `perfis` | Perfis de acesso (Gestor, Analista, etc.) |
| `perfil_usuario` | Vínculo usuário–perfil com vigência |
| `solicitacoes_cadastro` | Solicitações de cadastro no sistema |
| `audit_logs` | Logs de auditoria |
| `personal_access_tokens` | Tokens Sanctum (autenticação) |

---

## Estrutura das tabelas

### 1. `ufs`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint | PK |
| id_ibge | int | ID do estado na API IBGE |
| sigla | char(2) | Sigla da UF (SP, MG, etc.) |
| nome | string | Nome do estado |
| regiao_sigla | char(2) | Sigla da região (N, NE, SE, S, CO) |
| regiao_nome | string | Nome da região |
| created_at, updated_at | timestamp | |

### 2. `municipios`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint | PK |
| id_ibge | int | ID do município na API IBGE |
| nome | string | Nome do município |
| uf_id | FK → ufs | Estado ao qual pertence |
| created_at, updated_at | timestamp | |

### 3. `users`
| Coluna | Tipo | Descrição |
|--------|------|-----------|
| id | bigint | PK |
| govbr_sub | string | Identificador GOV.BR (único) |
| cpf_hash | string | CPF anonimizado (HMAC) |
| name | string | Nome |
| email | string | E-mail |
| picture | string | URL da foto |
| role | string | Papel (user, admin) |
| esfera_atuacao | string | federal, estadual, municipal |
| uf_lotacao | string | UF de lotação |
| municipio_lotacao | string | Município de lotação |
| created_at, updated_at | timestamp | |

### 4. `perfis`
| Coluna | Tipo |
|--------|------|
| id | bigint |
| nome | string |
| descricao | string |
| created_at, updated_at | timestamp |

### 5. `perfil_usuario`
| Coluna | Tipo |
|--------|------|
| id | bigint |
| usuario_id | FK → users |
| perfil_id | FK → perfis |
| data_inicio_vigencia | date |
| data_fim_vigencia | date |
| created_at, updated_at | timestamp |

### 6. `solicitacoes_cadastro`
| Coluna | Tipo |
|--------|------|
| id | bigint |
| cpf_hash | string |
| nome | string |
| email_institucional | string |
| telefone_institucional | string |
| telefone_pessoal | string |
| esfera_atuacao | string |
| uf | string |
| municipio | string |
| orgao | string |
| cargo | string |
| status | string (em_analise, aprovado, reprovado) |
| created_at, updated_at | timestamp |

### 7. `audit_logs`
| Coluna | Tipo |
|--------|------|
| id | bigint |
| user_id | FK → users |
| action | string |
| ip_address | string |
| user_agent | string |
| context | json |
| created_at | timestamp |

---

## Como listar os dados

### Opção 1: Laravel Tinker (recomendado)

```bash
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_BACKEND
php artisan tinker
```

Dentro do Tinker:

```php
// UFs
\App\Models\Uf::orderBy('nome')->get();

// Municípios de uma UF
\App\Models\Municipio::whereHas('uf', fn($q) => $q->where('sigla', 'SP'))->orderBy('nome')->get();

// Usuários
\App\Models\User::all();

// Perfis
\App\Models\Perfil::all();

// Solicitações de cadastro
\App\Models\SolicitacaoCadastro::all();

// Perfil-Usuário
\App\Models\PerfilUsuario::all();

// Logs de auditoria
\App\Models\AuditLog::orderBy('created_at', 'desc')->limit(20)->get();
```

### Opção 2: Docker (se estiver usando containers)

```powershell
docker exec -it nvsl-backend php artisan tinker
```

### Opção 3: SQL direto (psql ou cliente PostgreSQL)

```bash
# Conectar ao PostgreSQL
psql -h localhost -p 5432 -U nvsl_user -d nvsl
```

```sql
-- Listar tabelas
\dt

-- Usuários
SELECT id, govbr_sub, name, email, esfera_atuacao, uf_lotacao FROM users;

-- Perfis
SELECT * FROM perfis;

-- Solicitações de cadastro
SELECT id, nome, esfera_atuacao, uf, municipio, status, created_at FROM solicitacoes_cadastro;

-- Perfil-Usuário
SELECT pu.*, u.name, p.nome as perfil_nome
FROM perfil_usuario pu
JOIN users u ON u.id = pu.usuario_id
JOIN perfis p ON p.id = pu.perfil_id;

-- Logs de auditoria (últimos 20)
SELECT id, user_id, action, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 20;
```

### Opção 4: Comando Artisan customizado

```bash
php artisan db:show          # Info do banco
php artisan migrate:status  # Status das migrations
```

---

## UFs e Municípios (IBGE)

As tabelas `ufs` e `municipios` replicam a estrutura do IBGE. **Não consumimos o serviço do IBGE em tempo de execução** — os dados ficam no banco. A volatilidade é baixa.

### Importar dados (uma vez)

Após criar as tabelas com `php artisan migrate`, execute:

```bash
php artisan localidades:importar-ibge
```

Isso busca UFs e municípios do IBGE e popula o banco. Pode ser executado novamente para atualizar (usa `updateOrCreate`).

### Estrutura de referência IBGE

- Estados: https://servicodados.ibge.gov.br/api/v1/localidades/estados
- Municípios: https://servicodados.ibge.gov.br/api/v1/localidades/estados/{id}/municipios
