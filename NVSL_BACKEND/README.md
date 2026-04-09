# NVSL Backend

Backend ativo do NVSL, reconstruido em Laravel 13.

## Visao geral

Este repositorio concentra:

- API HTTP do NVSL
- autenticacao GOV.BR
- regras de negocio de solicitacoes, perfis e contexto
- auditoria
- Swagger da API

Premissas atuais:

- o Compose oficial do projeto e somente `../NVSL_DOCKER/docker-compose.yml`
- local e homologacao usam cache e sessao em arquivo
- Redis nao e dependencia obrigatoria desses ambientes
- o banco do ambiente de integracao e separado do banco principal
- o backend legado em `../NVSL_BACKEND_LEGADO` permanece apenas como referencia de migracao

## Como subir

Ambiente oficial:

```bash
cd ../NVSL_DOCKER
docker compose up -d --build postgres postgres-test backend frontend
```

Pontos de validacao:

- healthcheck: `http://localhost:8081/api/health`
- Swagger: `http://localhost:8081/api/docs`

Comandos uteis:

```bash
cd ../NVSL_DOCKER
docker compose exec -T backend php artisan route:list
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan db:seed --force
docker compose exec -T backend php artisan l5-swagger:generate
```

## Arquitetura

Stack principal:

- Laravel 13
- Sanctum
- PostgreSQL
- cache e sessao em arquivo
- `darkaonline/l5-swagger`
- `spatie/laravel-activitylog`

Estrutura principal:

- `app/Http/Controllers`: endpoints da API
- `app/Http/Requests`: validacoes
- `app/Services`: regras de negocio
- `app/Models`: modelos Eloquent
- `app/Policies`: autorizacao
- `app/OpenApi`: metadados do Swagger
- `app/Console/Commands`: comandos operacionais
- `database/migrations`: schema novo do zero
- `database/seeders`: seeds controlados por perfil
- `tests/Unitario`: testes sem banco
- `tests/Integracao`: testes com banco dedicado

Fluxo geral:

1. o frontend chama endpoints publicos ou autenticados
2. controllers delegam para requests, policies e services
3. services aplicam regras de negocio, auditoria e persistencia
4. a API responde em JSON e o contrato precisa permanecer alinhado ao Swagger

## Banco e migrations

O backend parte de schema limpo, sem compromisso com retrocompatibilidade do historico antigo.

Migrations centrais:

- `create_usuarios_table`
- `create_catalogos_nvsl_table`
- `create_auditoria_log_table`
- `create_perfil_usuario_table`
- `create_solicitacoes_cadastro_table`

Regras importantes ja refletidas no schema:

- `usuarios.cpf` unico
- `usuarios.govbr_sub` unico
- `perfis.nome` unico
- `esferas.nome` unico
- `perfil_usuario (usuario_id, perfil_id)` unico
- indice parcial em PostgreSQL para impedir mais de um perfil ativo por usuario
- indices de auditoria por `tabela_afetada` + `registro_id`

Comandos usuais:

```bash
cd ../NVSL_DOCKER
docker compose exec -T backend php artisan migrate:fresh --seed
docker compose exec -T backend php artisan migrate:status
```

## Seeds

O `db:seed` continua fazendo parte da subida do ambiente, mas com perfis controlados:

- `bootstrap`: catalogos minimos
- `demo`: dados de demonstracao
- `test`: dados controlados para testes automatizados

No `docker compose` local, o backend sobe com `DB_SEED_PROFILE=demo`, entao os usuarios padrao de desenvolvimento sao carregados automaticamente.
Em `APP_ENV=hlog`, o `DatabaseSeeder` tambem respeita o perfil configurado para permitir a subida desses usuarios.

Diretriz:

- nao reintroduzir seed destrutiva como comportamento padrao
- manter os dados de teste isolados do bootstrap do ambiente
- em qualquer ambiente diferente de `local` e `hlog`, o `DatabaseSeeder` força o uso de `bootstrap`, mesmo que `DB_SEED_PROFILE` esteja diferente

## Testes

Estrutura:

- `tests/Unitario`: sem banco
- `tests/Integracao`: com `postgres-test`

Regras:

- testes unitarios nao interagem com banco
- testes de integracao usam somente o banco dedicado
- nomes e cenarios devem ser escritos em portugues
- regras de negocio e constraints relevantes devem ser exercitadas em teste

Como rodar:

```bash
cd ../NVSL_DOCKER
docker compose exec -T backend vendor/bin/phpunit --configuration=phpunit.unitario.xml --testsuite=Unitario
docker compose exec -T backend vendor/bin/phpunit --configuration=phpunit.integracao.xml --testsuite=Integracao
```

Coverage:

```bash
cd ../NVSL_DOCKER
docker compose exec -T backend composer test:coverage:unitario
docker compose exec -T backend composer test:coverage:integracao
docker compose exec -T backend composer test:coverage:combined
```

Cobertura combinada:

- o comando `composer test:coverage:combined` roda unitarios e integracao, faz o merge dos artefatos e gera relatorios combinados
- saida combinada em `build/coverage/combined`
- artefatos principais:
  - `build/coverage/combined/coverage.txt`
  - `build/coverage/combined/clover.xml`
  - `build/coverage/combined/html/index.html`

## Swagger

O Swagger deve permanecer funcional em `http://localhost:8081/api/docs`.

Comando de geracao:

```bash
cd ../NVSL_DOCKER
docker compose exec -T backend php artisan l5-swagger:generate
```

Diretrizes:

- todo endpoint novo precisa ter anotacao OpenAPI correspondente
- o Swagger deve continuar documentando os endpoints implementados
- alteracoes de contrato devem ser validadas junto com os testes de integracao

## Convencoes de desenvolvimento

Organizacao:

- controller fino, service forte
- validacao em `FormRequest`
- autorizacao em `Policy`
- auditoria em acoes administrativas relevantes
- respostas alinhadas ao contrato consumido pelo frontend

Fluxo de versionamento:

1. partir sempre da branch `main`
2. atualizar a `main` local no inicio de cada ciclo e apos cada sprint
3. criar branch de trabalho a partir da `main` atualizada
4. devolver alteracoes por PR

Padroes sugeridos:

- `feature/<tema>`
- `fix/<tema>`
- `chore/<tema>`

## Checklist rapido

- ambiente sobe pelo Compose oficial
- `api/health` responde `ok`
- Swagger abre corretamente
- testes unitarios passam
- testes de integracao passam
- migrations e seeders seguem coerentes com o schema novo
- nao foi reintroduzida dependencia de Redis para local/homologacao
