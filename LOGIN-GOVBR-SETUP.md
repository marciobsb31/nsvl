# Login GOV.BR — Configuração e Testes

## Funcionalidade implementada

A tela de login do NVSL utiliza autenticação via gov.br, conforme História de Usuário:

- **Entrar com GOV.BR**: inicia o fluxo OAuth2/OIDC do gov.br
- **Perfil de acesso**: mantido para testes locais (token-de-teste)
- **Solicitar Cadastro**: redireciona para a tela de solicitação

## Credenciais de homologação (MGI)

| Parâmetro | Valor |
|-----------|-------|
| **Client ID** | `h-nvsl.dev.mdh.gov.br` |
| **Client Secret** | `qhdLnmHdKbNDdPYf52JOfk_N4L1RR1nrhO1H-Y2R9SMWQvnPGB4-iW74qTblMeqiYfrcfWniJ8rI1JKK31FYkg` |
| **SSO URL** | `https://sso.staging.acesso.gov.br` |

## Testes em localhost

Para permitir testes em localhost, é necessário que o **redirect_uri** esteja cadastrado no MGI:

```
http://localhost:8081/api/auth/callback
```

**Solicite ao MGI** a inclusão deste URI na lista de redirect URIs permitidos do client `h-nvsl.dev.mdh.gov.br`.

## Criar conta no ambiente de homologação

1. Acesse: https://sso.staging.acesso.gov.br/
2. Crie sua conta com os dados padrão:
   - **Nome da mãe:** MAMÃE
   - **Data de nascimento:** 01/01/1980

## Fluxo de autenticação

1. Usuário clica em **Entrar com GOV.BR**
2. Sistema redireciona para `sso.staging.acesso.gov.br`
3. Usuário autentica no gov.br
4. Gov.br redireciona para `{backend}/api/auth/callback?code=...&state=...`
5. Backend valida CPF, perfil vigente e solicitações
6. Backend redireciona para `{frontend}/login#govbr_login_code=...` ou `govbr_error=...`
7. Frontend troca o código pelo token via `POST /auth/exchange`

## Validações (regras de negócio)

| Situação | Mensagem | Ação |
|----------|----------|------|
| Usuário sem perfil vigente | "Seu usuário não possui perfil ativo no sistema." | Volta à tela de login |
| Usuário não cadastrado | "Solicitar acesso e aguardar avaliação" | Volta à tela de login |
| Solicitação em análise | "Solicitação de acesso em análise." | Volta à tela de login |
| Solicitação reprovada | "Sua solicitação de cadastro foi reprovada." | Volta à tela de login |

## Roteiro técnico

- https://acesso.gov.br/roteiro-tecnico
- https://www.gov.br/governodigital/pt-br/identidade/identidade-digital-para-gestores-publicos
