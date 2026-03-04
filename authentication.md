# Autenticação GOV.BR — Fluxo OIDC com PKCE

## Visão Geral

O sistema utiliza o protocolo **OpenID Connect (OIDC)** sobre OAuth 2.0 com o fluxo **Authorization Code + PKCE** para autenticar usuários via GOV.BR SSO.

PKCE (Proof Key for Code Exchange) é obrigatório para aplicações frontend (clientes públicos), pois elimina a necessidade de um `client_secret` sem sacrificar a segurança.

## Endpoints GOV.BR

| Ambiente | URL Base |
|---|---|
| Staging | `https://sso.staging.acesso.gov.br` |
| Produção | `https://sso.acesso.gov.br` |

| Endpoint | Path |
|---|---|
| Authorization | `/authorize` |
| Token | `/token` |
| UserInfo | `/userinfo` |
| Logout | `/logout` |
| Discovery (OIDC) | `/.well-known/openid-configuration` |

## Fluxo de Autenticação

```
Usuário clica "Entrar com GOV.BR"
        │
        ▼
[1] Frontend gera code_verifier + code_challenge (S256)
        │
        ▼
[2] Redireciona para:
    https://sso.staging.acesso.gov.br/authorize?
      client_id=SEU_CLIENT_ID
      &response_type=code
      &redirect_uri=http://localhost/callback
      &scope=openid email profile
      &state=<random>
      &nonce=<random>
      &code_challenge=<S256 hash>
      &code_challenge_method=S256
        │
        ▼
[3] Usuário autentica no GOV.BR
        │
        ▼
[4] GOV.BR redireciona para /callback?code=<auth_code>&state=<state>
        │
        ▼
[5] Frontend troca code por tokens:
    POST https://sso.staging.acesso.gov.br/token
    Body: grant_type=authorization_code
          &code=<auth_code>
          &redirect_uri=<redirect_uri>
          &client_id=<client_id>
          &code_verifier=<verifier>
        │
        ▼
[6] GOV.BR retorna: access_token, id_token, refresh_token
        │
        ▼
[7] Frontend extrai dados do id_token (sub, name, email, amr)
        │
        ▼
[8] Redireciona para / (home)
```

## Escopos (Scopes)

| Scope | Dados retornados |
|---|---|
| `openid` | `sub` (identificador único) — **obrigatório** |
| `email` | `email`, `email_verified` |
| `profile` | `name`, `picture`, `phone_number` |

## Nível de Confiabilidade (amr)

O campo `amr` no `id_token` indica o nível da conta GOV.BR:

| Nível | Valor amr | Descrição |
|---|---|---|
| Bronze | `passwd` | Senha cadastrada |
| Prata | `x509` | Certificado digital |
| Ouro | `hmac` | Reconhecimento facial ou certificado ICP-Brasil |

## Implementação no Projeto

### Arquivos relevantes

| Arquivo | Responsabilidade |
|---|---|
| `src/services/AuthService.ts` | Gerencia UserManager (oidc-client-ts) |
| `src/stores/authStore.ts` | Estado global de autenticação (Pinia) |
| `src/composables/useAuth.ts` | API de auth para componentes |
| `src/pages/LoginPage.vue` | Interface de login com botão GOV.BR |
| `src/pages/CallbackPage.vue` | Processa retorno do SSO |
| `src/router/index.ts` | Navigation guard para rotas protegidas |

### Armazenamento de Tokens

Os tokens são armazenados em **`sessionStorage`** (não `localStorage`) para:
- Serem limpos ao fechar a aba
- Não serem compartilhados entre abas (isolamento de sessão)

## Registro no GOV.BR

Para registrar sua aplicação e obter um `client_id`:
1. Acesse: https://manual-roteiro-integracao-login-govbr.servicos.gov.br/
2. Cadastre os `redirect_uri` permitidos
3. Configure os escopos necessários

## Logout

O logout redireciona ao endpoint GOV.BR e limpa a sessão OIDC local:

```
POST /logout → SSO GOV.BR → redireciona para /login
```
