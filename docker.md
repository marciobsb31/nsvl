# Docker — Build e Execução

## Estrutura

O repositório **DOCKER** centraliza a infraestrutura de containers do projeto NVSL. Ele referencia o código-fonte do repositório **FRONTEND** via `build.context`.

```
DOCKER/
└── docker-compose.yml   # Definição dos serviços
FRONTEND/
├── Dockerfile           # Build multi-stage do frontend
└── nginx.conf           # Configuração do servidor Nginx
```

## Pré-requisitos

| Ferramenta | Versão mínima |
|---|---|
| Docker | 24.x |
| Docker Compose | v2.x (plugin) |

## Executar com Docker Compose

```bash
# A partir do repositório DOCKER
cd DOCKER

# Build e start dos containers
docker compose up --build -d

# Acessar a aplicação
# http://localhost
```

## Comandos Úteis

```bash
# Ver logs do frontend
docker compose logs -f frontend

# Parar os containers
docker compose down

# Rebuild sem cache
docker compose build --no-cache frontend

# Verificar status dos containers
docker compose ps
```

## Build Manual da Imagem Frontend

```bash
cd FRONTEND

# Build da imagem
docker build -t nvsl-frontend:latest .

# Executar isoladamente
docker run -p 80:80 nvsl-frontend:latest
```

## Estrutura do Dockerfile (Multi-Stage)

```dockerfile
# Stage 1: Build
FROM node:20-alpine AS build
# npm ci + vite build → gera /dist

# Stage 2: Serve
FROM nginx:stable-alpine AS serve
# Copia /dist + nginx.conf
# Expõe porta 80
```

## Variáveis de Ambiente

> **Atenção**: As variáveis são definidas no `docker-compose.yml`. Variáveis `VITE_*` são embutidas no bundle durante o build (não em runtime). Para atualizar, é necessário rebuild da imagem.

| Variável | Valor Padrão | Descrição |
|---|---|---|
| `VITE_GOVBR_CLIENT_ID` | `SEU_CLIENT_ID_AQUI` | **Substituir** pelo client_id real |
| `VITE_GOVBR_SSO_URL` | `https://sso.staging.acesso.gov.br` | SSO GOV.BR (staging) |
| `VITE_GOVBR_REDIRECT_URI` | `http://localhost/callback` | URI de callback |

## Health Check

O container inclui health check automático:
```bash
# Verificar saúde do container
docker inspect nvsl-frontend | grep -A 10 Health
```
