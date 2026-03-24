# NVSL Backend

API REST do Sistema NVSL — autenticação GOV.BR, Laravel 12, PHP 8.4, PostgreSQL 16.

## Stack

| Tecnologia | Versão | Papel |
|---|---|---|
| PHP | 8.4 | Runtime |
| Laravel | ^12.0 | Framework |
| Laravel Sanctum | ^4.0 | Autenticação Bearer token |
| darkaonline/l5-swagger | ^8.6 | Swagger / OpenAPI 3.0 |
| predis/predis | ^2.0 | Cliente Redis (sem extensão nativa) |
| PostgreSQL | 16 | Banco de dados |
| Redis | 7 | Cache e sessões |
| Nginx | Alpine | Web server (porta 81 interna) |

## Iniciar (Docker)

```bash
cd ../DOCKER
docker compose up -d --build
```

O container inicializa automaticamente:
- aguarda o PostgreSQL
- executa as migrations
- gera a documentação Swagger
- inicia PHP-FPM + Nginx via Supervisord

## URLs

| Serviço | URL |
|---|---|
| API REST | `http://localhost:8081/api` |
| Health Check | `http://localhost:8081/api/health` |
| Swagger UI | `http://localhost:8081/api/docs` |

## Endpoints

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/auth/redirect` | Público | Retorna URL de login GOV.BR |
| `GET` | `/api/auth/callback` | Público | Processa callback e emite token |
| `POST` | `/api/auth/logout` | 🔒 Bearer | Revoga o token Sanctum |
| `GET` | `/api/user` | 🔒 Bearer | Dados do usuário autenticado |
| `GET` | `/api/health` | Público | Status do banco e cache |

## Estrutura

```
app/
  DTOs/Auth/GovBrUserDTO.php           ← DTO imutável (readonly)
  Http/Controllers/Auth/               ← GovBrAuth + User controllers
  Http/Middleware/                     ← AnonymizeResponseMiddleware
  Models/                              ← User (cpf_hash, toSafeArray) + AuditLog
  Services/Auth/GovBrService.php       ← OAuth2 PKCE + UserInfo
  Services/Audit/AuditLogService.php   ← Audit append-only
  Providers/AppServiceProvider.php     ← DI + anotações OpenAPI globais
config/
  govbr.php, audit.php, l5-swagger.php, view.php
routes/api.php                         ← 5 rotas documentadas
database/migrations/                   ← users, audit_logs, personal_access_tokens
docker/
  entrypoint.sh, nginx.conf, supervisord.conf, php.ini
tests/
  Feature/Auth/GovBrAuthControllerTest.php  ← 8 testes
  Unit/Auth/GovBrServiceTest.php            ← 5 testes
```

## Segurança

- **CPF**: HMAC-SHA256 irreversível antes de qualquer persistência
- **Access Token GOV.BR**: nunca persistido — descartado após `/userinfo`
- **PKCE**: `code_verifier` + `code_challenge` SHA-256 protege o fluxo OAuth2
- **CSRF**: `state` aleatório validado em `hash_equals` no callback
- **Resposta**: `AnonymizeResponseMiddleware` remove campos sensíveis recursivamente
- **Auditoria**: toda ação de auth registrada com IP + user-agent

## Testes

```bash
# Via Docker
docker exec nvsl-backend php artisan test --testdox

# Com cobertura (gera coverage.xml para SonarQube)
docker exec nvsl-backend php artisan test --coverage-clover=coverage.xml
```

## Swagger

```bash
# Regenerar manualmente
docker exec nvsl-backend php artisan l5-swagger:generate
```

Acesse `http://localhost:8081/api/docs`, clique em **Authorize** e insira o token Sanctum.

## Variáveis de Ambiente

Consulte `.env.example` para referência. As variáveis de produção são definidas no `DOCKER/docker-compose.yml`.

> ⚠️ Preencha `GOVBR_CLIENT_ID` e `GOVBR_CLIENT_SECRET` com os valores reais do GOV.BR.

## Documentação Completa

Disponível em [`DOCS/backend/`](../DOCS/backend/):

- [Arquitetura](../DOCS/backend/architecture.md)
- [Autenticação GOV.BR](../DOCS/backend/authentication.md)
- [Anonimização de Dados](../DOCS/backend/anonymization.md)
- [Logs de Auditoria](../DOCS/backend/audit-logs.md)
- [Segurança](../DOCS/backend/security.md)
- [Swagger / OpenAPI](../DOCS/backend/swagger.md)
- [Testes](../DOCS/backend/testing.md)
- [Docker](../DOCS/backend/docker.md)
- [Setup](../DOCS/backend/setup.md)
- [SonarQube](../DOCS/backend/sonarqube.md)
- [Contribuição](../DOCS/backend/contributing.md)