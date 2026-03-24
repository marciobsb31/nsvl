# NVSL — Guia de Setup para Ambiente de Desenvolvimento Local

Este guia ajuda você a rodar o projeto **NVSL** (Novo Viver Sem Limite) na sua máquina local usando Docker. O sistema está disponível em produção em: https://nvsl.dev.mdh.gov.br/login

---

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

| Ferramenta | Versão mínima | Verificar com |
|------------|---------------|---------------|
| **Docker** | 20.x+ | `docker --version` |
| **Docker Compose** | 2.x+ | `docker compose version` |
| **Git** | 2.x+ | `git --version` |

---

## 📁 Estrutura do Projeto

O projeto é composto por 3 repositórios:

```
NVSLLOCAL/
├── NVSL_DOCKER/     → Orquestração (docker-compose)
├── NVSL_BACKEND/    → API Laravel 12 (PHP)
├── NVSL_FRONTEND/   → SPA Vue 3 (TypeScript)
```

---

## 🚀 Passo a Passo — Rodar com Docker

### 1. Clonar os repositórios (se ainda não fez)

```powershell
cd c:\xampp\htdocs\NVSLLOCAL

git clone "https://mmfdh-ti@dev.azure.com/mmfdh-ti/Novo%20Viver%20Sem%20Limite/_git/NVSL_DOCKER" NVSL_DOCKER
git clone "https://mmfdh-ti@dev.azure.com/mmfdh-ti/Novo%20Viver%20Sem%20Limite/_git/NVSL_BACKEND" NVSL_BACKEND
git clone "https://mmfdh-ti@dev.azure.com/mmfdh-ti/Novo%20Viver%20Sem%20Limite/_git/NVSL_FRONTEND" NVSL_FRONTEND
```

> **Nota:** Você precisará autenticar no Azure DevOps (usuário/senha ou token de acesso pessoal).

### 2. Subir os containers

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER
docker compose up --build -d
```

Isso irá:
- Construir as imagens do frontend e backend
- Subir PostgreSQL, Redis, Backend e Frontend
- Rodar as migrations automaticamente no backend

### 3. Aguardar a inicialização

A primeira execução pode levar alguns minutos (download de imagens, build, etc.). Verifique o status:

```powershell
docker compose ps
```

### 4. Acessar a aplicação

| Serviço | URL | Descrição |
|---------|-----|-----------|
| **Frontend** | http://localhost:5174 | Interface do usuário |
| **Backend API** | http://localhost:8081/api | API REST Laravel |
| **Health Check** | http://localhost:8081/api/health | Status do sistema |
| **Swagger** | http://localhost:8081/api/documentation | Documentação da API |

### Login (testes)

1. Execute o seeder: `php artisan db:seed --class=UsuarioExemploSeeder --force`
2. Acesse http://localhost:5174 → redireciona para /login
3. Selecione o perfil (Federal, Estadual ou Municipal) e clique em Entrar
4. Acesse **Gerenciar Cadastros** para testar a visibilidade por perfil

Ver **PASSO-A-PASSO-TESTE-GERENCIAR-CADASTROS.md** para cenários completos.

### Login GOV.BR (homologação)

O login via GOV.BR usa o ambiente de homologação (`sso.staging.acesso.gov.br`). O `redirect_uri` deve corresponder **exatamente** ao cadastrado no MGI. Para o client_id `h-nvsl.dev.mdh.gov.br`, use:

- **GOVBR_REDIRECT_URI**: `https://h-nvsl.dev.mdh.gov.br/api/auth/callback`
- O backend precisa estar acessível nessa URL (deploy em homologação)

Para criar conta no ambiente de homologação: https://sso.staging.acesso.gov.br/ (Nome da mãe: MAMÃE, Data de nascimento: 01/01/1980). Roteiro técnico: https://acesso.gov.br/roteiro-tecnico

---

## 🛠️ Comandos úteis

```powershell
# Ver logs em tempo real
docker compose logs -f

# Parar todos os containers
docker compose down

# Parar e remover volumes (limpa o banco)
docker compose down -v

# Executar migrations manualmente (se necessário)
docker exec -it nvsl-backend php artisan migrate

# Acessar o container do backend
docker exec -it nvsl-backend sh

# Ver status dos containers
docker compose ps
```

---

## 🔧 Desenvolvimento sem Docker (opcional)

Se quiser rodar frontend/backend diretamente na máquina (sem Docker):

### Backend (Laravel)
- PHP 8.4, Composer, PostgreSQL 16, Redis
- Copie `.env.example` para `.env` e configure
- `composer install && php artisan migrate`
- `php artisan serve` (porta 8000)

### Frontend (Vue 3)
- Node.js 20+, npm
- Copie `.env.example` para `.env`
- `npm install && npm run dev` (porta 5174 — configure em vite.config se necessário)

---

## ❓ Problemas comuns

### "Cannot connect to Docker daemon"
- Verifique se o Docker Desktop está rodando no Windows.

### Erro ao clonar do Azure DevOps
- Use um [Personal Access Token (PAT)](https://learn.microsoft.com/azure/devops/organizations/accounts/use-personal-access-tokens-to-authenticate) em vez de senha.

### Porta já em uso
- Altere as portas no `docker-compose.yml` (ex.: `5174:8080` para o frontend).

### Migrations falham
- Certifique-se de que o PostgreSQL subiu antes do backend. O entrypoint já aguarda o banco.

### "exec /entrypoint.sh: no such file or directory" (Backend)
- Problema de line endings (CRLF no Windows). O Dockerfile já inclui correção. Faça rebuild:
  ```powershell
  cd NVSL_DOCKER
  docker compose down
  docker compose build --no-cache backend
  docker compose up -d
  ```

---

## 📚 Referências

- [NVSL Produção](https://nvsl.dev.mdh.gov.br/login)
- [Laravel Docs](https://laravel.com/docs)
- [Vue 3 Docs](https://vuejs.org/)
- [Docker Docs](https://docs.docker.com/)
