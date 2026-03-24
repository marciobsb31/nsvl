# Debug — Painel Detalhar não abre

## Como identificar o erro

1. **Abra o Console do navegador** (F12 → aba Console)
2. Faça login como **Federal** e acesse **Gerenciar Cadastros**
3. Clique em **Listar** (obrigatório para carregar as solicitações)
4. Clique em **Detalhar** em Carlos Souza ou Roberto Alves
5. Observe o Console:
   - **Sucesso:** `[Detalhar] Iniciando` → `[Detalhar] Resposta da API` → `[Detalhar] Painel aberto com sucesso.`
   - **Erro:** `[Detalhar] Erro ao abrir painel — status: XXX`

## Erros comuns

| Erro no Console | Causa | Solução |
|-----------------|-------|---------|
| `status: 401` | Token inválido ou expirado | Faça login novamente. O erro "Sessão expirada" aparece antes do redirect. |
| `status: 403` | Acesso negado à solicitação | **Entre como Federal** — Carlos Souza (DF) e Roberto Alves (GO) só são visíveis para Federal |
| `status: 404` | Solicitação não encontrada | Execute o seeder: `docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force` |
| `status: 500` | Erro no backend | Verifique os logs: `docker logs nvsl-backend` |
| `Network Error` ou `ERR_CONNECTION_REFUSED` | Backend não está rodando | Execute `.\iniciar-nvsl.ps1` ou `docker compose up -d` |
| `ID inválido` | Lista vazia ou item sem id | Clique em **Listar** antes de Detalhar |

## Verificar requisição na aba Network

1. F12 → aba **Network** (Rede)
2. Clique em Detalhar
3. Procure a requisição `solicitacoes-cadastro/{id}`
4. Clique nela e veja:
   - **Status**: 200 = OK, 401 = não autenticado, 403 = acesso negado, 404 = não encontrado
   - **Response**: conteúdo retornado pela API

## Reconstruir containers (se alterou código)

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER
docker compose build --no-cache frontend backend
docker compose up -d
docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force
```

## Testar API manualmente

Com o token em mãos (após login, em sessionStorage):

```powershell
# Obter token: no Console do navegador após login:
# sessionStorage.getItem('nvsl_token')

# Testar (substitua TOKEN e ID):
curl -H "Authorization: Bearer TOKEN" -H "Accept: application/json" http://localhost:5174/api/solicitacoes-cadastro/1
```
