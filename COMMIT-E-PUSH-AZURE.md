# Como commitar e enviar a branch feature/24140 para o Azure DevOps

## Visão geral

A branch `feature/24140-backend-solicitacao-cadastro` contém:
- **Backend**: API de solicitação de cadastro, validação de login, migrations (perfis, perfil_usuario, solicitacoes_cadastro)
- **Banco de dados**: As migrations rodam automaticamente no entrypoint do container Docker
- **Docker**: O backend usa o `Dockerfile-backend` do próprio repositório (o pipeline Azure faz build a partir dele)

---

## Passo 1: Commitar e enviar o Backend (NVSL_BACKEND)

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_BACKEND

# Ver o que será commitado
git status

# Adicionar todos os arquivos
git add .

# Commitar com mensagem descritiva
git commit -m "feat(24140): Backend solicitação de cadastro e validação de login

- Migrations: perfis, perfil_usuario, solicitacoes_cadastro (português)
- API POST /solicitacoes-cadastro
- Validação de login: CPF, perfil vigente, solicitação em análise
- AuthValidationService e mensagens de erro conforme RN
- Correção entrypoint (CRLF) no Dockerfile"

# Enviar para o Azure DevOps
git push -u origin feature/24140-backend-solicitacao-cadastro
```

---

## Passo 2: NVSL_DOCKER (opcional)

O `docker-compose.yml` foi ajustado para os paths locais (`../NVSL_BACKEND`, `../NVSL_FRONTEND`). Se o Azure usar outro fluxo (ex.: pipeline que clona os 3 repos), pode ser necessário criar uma branch no DOCKER também:

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER

# Criar branch e commitar (se fizer sentido no seu fluxo)
git checkout -b feature/24140-docker-ajustes
git add docker-compose.yml
git commit -m "fix: Ajuste paths para NVSL_BACKEND e NVSL_FRONTEND"
git push -u origin feature/24140-docker-ajustes
```

> **Nota**: O pipeline do backend (`azure-pipelines-dev.yml`) faz build direto do `Dockerfile-backend` no repositório do backend. O NVSL_DOCKER é usado mais para ambiente local.

---

## Passo 3: Criar Pull Request no Azure DevOps

1. Acesse: https://dev.azure.com/mmfdh-ti/Novo%20Viver%20Sem%20Limite/_git/NVSL_BACKEND
2. Vá em **Pull Requests** → **New pull request**
3. **Source**: `feature/24140-backend-solicitacao-cadastro`
4. **Target**: `dev` (ou `master`, conforme o fluxo do projeto)
5. Preencha título e descrição
6. Crie o PR e aguarde aprovação/merge

---

## O que acontece após o merge

1. O pipeline é disparado na branch `dev` (conforme `azure-pipelines-dev.yml`)
2. A imagem Docker é construída com o novo código (incluindo migrations)
3. O deploy é feito no AWS ECS
4. Ao subir o container, o **entrypoint** executa `php artisan migrate --force` automaticamente
5. As tabelas `perfis`, `perfil_usuario` e `solicitacoes_cadastro` são criadas no banco

---

## Variáveis no Azure (DEV-ENV-BACKEND / DEV-APP-BACKEND)

Confirme que os grupos de variáveis têm:
- `DB_*` (host, database, user, password) para PostgreSQL
- `REDIS_*` para Redis
- `GOVBR_*` para autenticação gov.br

---

## Testar localmente antes de enviar

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER
docker compose down -v
docker compose build --no-cache backend
docker compose up -d
# Aguardar ~30s e testar: http://localhost:8081/api/health
```
