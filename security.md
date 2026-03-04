# Segurança — NVSL Frontend

## Visão Geral

A aplicação segue as diretrizes de segurança para aplicações web modernas, com foco em proteção contra as principais ameaças (OWASP Top 10) e conformidade com as recomendações do GOV.BR.

## Autenticação e Sessão

### OAuth2 / OIDC com PKCE
- Fluxo **Authorization Code + PKCE** — sem `client_secret` exposto
- `code_verifier` gerado com `crypto.randomUUID()` (criptograficamente seguro)
- Parâmetro `state` previne ataques CSRF
- Parâmetro `nonce` previne ataques de replay no `id_token`

### Armazenamento de Tokens
- Tokens armazenados em **`sessionStorage`** — não persistem além da aba e sessão
- **Nunca** armazenar tokens em `localStorage` em produção
- Tokens não são enviados ao backend sem TLS

## Cabeçalhos HTTP de Segurança (Nginx)

| Cabeçalho | Valor | Proteção |
|---|---|---|
| `Content-Security-Policy` | Restringe fontes de recursos | XSS, injeção de conteúdo |
| `X-Frame-Options` | `DENY` | Clickjacking |
| `X-Content-Type-Options` | `nosniff` | MIME sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Vazamento de URL |
| `Permissions-Policy` | Desativa câmera, microfone, geolocalização | Abuso de APIs |
| `Strict-Transport-Security` | (ativar com HTTPS) | Downgrade de protocolo |

## Content Security Policy (CSP)

```
default-src 'self';
script-src 'self' 'unsafe-inline';
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src 'self' https://fonts.gstatic.com data:;
img-src 'self' data: https: blob:;
connect-src 'self' https://sso.staging.acesso.gov.br https://sso.acesso.gov.br;
frame-ancestors 'none';
form-action 'self';
```

> **Nota**: `'unsafe-inline'` para scripts pode ser substituído por hashes CSP ou nonces em versões futuras para maior rigor.

## Boas Práticas de Desenvolvimento

- **Variáveis de ambiente**: nunca commitar `.env` — usar `.env.example`
- **Dependências**: manter atualizadas (`npm audit`) para evitar vulnerabilidades conhecidas
- **HTTPS**: obrigatório em produção — ativar HSTS no Nginx
- **Docker**: imagem base mínima (`nginx:stable-alpine`), usuário não-root recomendado
- **Logs**: não registrar tokens, senhas ou dados sensíveis nos logs

## Checklist de Segurança para Deploy

- [ ] `VITE_GOVBR_CLIENT_ID` configurado com valor real
- [ ] `redirect_uri` registrada no portal GOV.BR
- [ ] HTTPS configurado (certificado TLS válido)
- [ ] HSTS ativado no Nginx (`Strict-Transport-Security`)
- [ ] CSP revisada para ambiente de produção
- [ ] `npm audit` executado sem vulnerabilidades críticas
- [ ] Imagem Docker escaneada (ex: `docker scout`, Trivy)
