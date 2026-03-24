# NVSL — Docker Infrastructure

Repositório de infraestrutura Docker do projeto **NVSL**.

## Serviços

| Serviço | Imagem | Porta | Descrição |
|---|---|---|---|
| `frontend` | `nvsl-frontend:latest` | `80` | SPA Vue 3 servida com Nginx |
| `backend-fpm` | `nvsl-backend-fpm:latest` | `-` | API Laravel 12 (Core) |
| `backend-web` | `nvsl-backend-web:latest` | `8000` | Nginx Proxy para o Backend |
| `postgres` | `postgres:16-alpine` | `5432` | Banco de Dados relacional |
| `maincache` | `redis:7-alpine` | `6379` | Cache central compartilhado (Externo) |

## Quick Start

```bash
# Build e start dos containers
docker compose up --build -d

# Inicializar Banco de Dados (na primeira execução)
docker exec -it nvsl-backend-fpm php artisan migrate

# Acessar a aplicação
# Frontend: http://localhost
# Backend:  http://localhost:8000/api
```

## Variáveis de Ambiente

As variáveis estão definidas no `docker-compose.yml`. Para alterar valores, edite o arquivo e execute `docker compose up --build -d`.

> **⚠️ Atenção**: Variáveis `VITE_*` são embutidas no bundle durante o build. Alterações requerem rebuild da imagem.

## Documentação

Consulte [DOCS/frontend/docker.md](../DOCS/frontend/docker.md) para instruções detalhadas.