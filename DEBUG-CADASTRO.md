# Debug — Cadastro de Usuário

## Como debugar

### 1. Abra o Console do navegador (F12 → Console)

Ao clicar em **Confirmar** no formulário de Cadastrar usuário, verifique:

- `[Cadastro] onConfirmarCadastro chamado, values:` — dados do formulário
- `[Cadastro] Enviando payload:` — payload enviado à API
- `[Cadastro] Token presente:` — true = token enviado, false = sem autenticação
- `[Cadastro] Sucesso:` — resposta da API (cadastro OK)
- `[Cadastro] Erro na requisição:` — erro (status, mensagem)

### 2. Aba Network (Rede)

- Filtre por `solicitacoes-cadastro`
- Clique na requisição POST
- Verifique: **Headers** (Authorization: Bearer ...), **Payload**, **Response**

### 3. Logs do backend (Laravel)

```powershell
docker exec nvsl-backend tail -f storage/logs/laravel.log
```

Ao enviar o formulário, verifique:
- `[Cadastro] store chamado` — request chegou ao controller
- `[Cadastro] user autenticado` — usuário identificado pelo token

---

## Teste via API (PowerShell)

Obter token e testar cadastro:

```powershell
# 1. Obter token
$tokenRes = Invoke-RestMethod -Uri "http://localhost:8081/api/auth/token-de-teste" -Method Post -Body '{"perfil":"federal"}' -ContentType "application/json"
$token = $tokenRes.token

# 2. Cadastrar (use CPF válido NÃO cadastrado - ex: 55566677720)
# CPFs JÁ no seeder (evite): 11144477735, 52998224725, 98765432100, 11122233344, 12345678909
$body = @{
  nome = "Usuario Debug Teste"
  CPF = "55566677720"
  emailInstitucional = "debug@email.com"
  telefoneInstitucional = "6198765432"
  esferaAtuacao = "federal"
  uf = "DF"
  municipio = "Brasilia"
  orgao = "Ministerio"
  cargo = "Analista"
  perfilId = 1
  vigenciaInicio = "2025-01-01"
} | ConvertTo-Json

$headers = @{ Authorization = "Bearer $token" }
Invoke-RestMethod -Uri "http://localhost:8081/api/solicitacoes-cadastro" -Method Post -Body $body -ContentType "application/json" -Headers $headers
```

Se retornar `solicitacao_id`, o backend está OK. Se 422, veja a mensagem de erro.

---

## Causa raiz (debug concluído)

A API está funcionando corretamente. O cadastro falha quando:

1. **CPF já cadastrado** — CPFs do seeder (111.444.777-35, 529.982.247-25, 987.654.321-00, 111.222.333-44, 123.456.789-09) já estão em `users` ou `solicitacoes_cadastro`.
2. **CPF inválido** — Dígitos verificadores incorretos.
3. **Solicitação em análise** — CPF já tem solicitação com status `em_analise`.

**CPF válido para teste:** `555.666.777-20` (ou 55566677720 sem formatação).

---

## Erros comuns

| Erro | Causa |
|------|-------|
| "CPF é obrigatório" | CPF não enviado ou vazio |
| "O CPF informado é inválido" | Dígitos verificadores incorretos |
| "Selecione o perfil" | perfilId ausente (usuário logado) |
| "Este CPF já possui cadastro ativo" | CPF já está na tabela users |
| "Já existe solicitação em análise" | CPF já tem solicitação em_analise |
| 401 / redirect login | Token inválido ou expirado |
| Network Error | Backend inacessível ou CORS |
