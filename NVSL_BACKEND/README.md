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
cd NVSL_DOCKER
docker compose up -d --build
```

O container inicializa automaticamente:
- aguarda o PostgreSQL
- executa as migrations
- importa **todas as UFs e municípios** via API do IBGE (`php artisan localidades:importar-ibge`; requer rede)
- executa seed de exemplo (usuários de teste)
- gera a documentação Swagger
- inicia PHP-FPM + Nginx via Supervisord

Se a importação IBGE falhar (sem internet), rode manualmente no container:  
`docker exec nvsl-backend php artisan localidades:importar-ibge`

## URLs

| Serviço | URL |
|---|---|
| API (raiz) | `http://localhost:8081` |
| API REST | `http://localhost:8081/api` |
| Health Check | `http://localhost:8081/api/health` |
| Swagger UI | `http://localhost:8081/api/docs` |
| OAuth GOV.BR (callback) | `http://localhost:8081/redirect-gov` |
| Logout web (redireciona ao frontend) | `http://localhost:8081/logout` |

## Endpoints

| Método | Rota | Auth | Descrição |
|---|---|---|---|
| `GET` | `/api/auth/redirect` | Público | Retorna URL de login GOV.BR |
| `GET` | `/redirect-gov` | Público | Callback OAuth (web); redireciona ao frontend com fragmento |
| `GET` | `/logout` | Público | Redireciona ao login do frontend (`?from=logout`) |
| `POST` | `/api/auth/logout` | Bearer | Revoga o token Sanctum |
| `GET` | `/api/user` | Bearer | Dados do usuário autenticado |
| `GET` | `/api/health` | Público | Status do banco e cache |

## Estrutura

```
app/
  DTOs/Auth/GovBrUserDTO.php
  Http/Controllers/                    # Auth, cadastros, localidades, health, …
  Http/Middleware/AnonymizeResponseMiddleware.php
  Models/Usuario.php, AuditLog.php, …
  Services/Auth/, Services/Audit/
  Providers/AppServiceProvider.php
config/
  govbr.php, audit.php, l5-swagger.php, view.php
routes/api.php, routes/web.php         # API + /redirect-gov, /logout
database/migrations/
docker/
  entrypoint.sh, nginx.conf, supervisord.conf, php.ini
tests/
```

## Segurança

- **CPF**: armazenado em `usuarios.cpf` (modelo atual); omitido nas respostas JSON da API (`toSafeArray` + middleware)
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

Consulte `.env.example` para referência. As variáveis de ambiente local costumam estar no `NVSL_DOCKER/docker-compose.yml`.

> **Atenção:** Preencha `GOVBR_CLIENT_ID` e `GOVBR_CLIENT_SECRET` com os valores reais do GOV.BR.

## Documentação Completa

Disponível em [`docs/backend/`](../docs/backend/):

- [Arquitetura](../docs/backend/architecture.md)
- [Autenticação GOV.BR](../docs/backend/authentication.md)
- [Anonimização de Dados](../docs/backend/anonymization.md)
- [Logs de Auditoria](../docs/backend/audit-logs.md)
- [Segurança](../docs/backend/security.md)
- [Swagger / OpenAPI](../docs/backend/swagger.md)
- [Testes](../docs/backend/testing.md)
- [Docker](../docs/backend/docker.md)
- [Setup](../docs/backend/setup.md)
- [SonarQube](../docs/backend/sonarqube.md)
- [Contribuição](../docs/backend/contributing.md)