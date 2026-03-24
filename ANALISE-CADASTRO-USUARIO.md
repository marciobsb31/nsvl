# Análise e Correções — Cadastro de Usuário

## Resumo

Este documento descreve a análise da lógica de cadastro de usuário (solicitação de cadastro) e as correções aplicadas para garantir o funcionamento correto, seguindo regras de negócio e segurança da informação.

---

## Fluxo de Cadastro

### 1. Cadastro pelo gestor (Gerenciar Cadastros)

- **Onde:** Tela Gerenciar Cadastros → Botão "Cadastrar usuário" → Painel lateral com `FormularioCadastrarUsuario`
- **Autenticação:** Usuário deve estar logado (Federal, Estadual ou Municipal)
- **API:** `POST /api/solicitacoes-cadastro` (rota pública, mas token enviado quando logado)
- **Regras:** Perfil e vigência obrigatórios quando autenticado; hierarquia (Estadual só cadastra estadual/UF; Municipal só municipal/UF/município)

### 2. Solicitação pública (cidadão)

- **Onde:** Tela de login → "Solicitar cadastro" → Formulário de solicitação
- **Autenticação:** Não exige login
- **API:** `POST /api/solicitacoes-cadastro`
- **Regras:** CPF obrigatório; perfil e vigência opcionais

---

## Problemas Identificados e Correções

### 1. Validação de e-mail .gov.br no frontend

**Problema:** O frontend validava apenas formato de e-mail, sem exigir domínio .gov.br. O backend exige. O usuário podia preencher `email@teste.com`, passar na validação do frontend e receber erro genérico do backend.

**Correção:** Adicionada validação `.test('govbr', 'O e-mail institucional deve terminar com .gov.br', ...)` no schema do `FormularioCadastrarUsuario.vue`.

### 2. Telefone institucional — 10 vs 11 dígitos

**Problema:** O frontend exigia exatamente 10 dígitos. O backend aceita 10 ou 11. Órgãos que usam celular institucional (11 dígitos) eram rejeitados no frontend.

**Correção:** Ajustada validação para aceitar 10 ou 11 dígitos: `digitos.length >= 10 && digitos.length <= 11`.

### 3. perfilId no payload de cadastro

**Problema:** Uso de `Number(values.perfil ?? 0) || undefined` podia gerar `undefined` em casos limítrofes (ex.: valor 0 ou string vazia), fazendo o backend rejeitar por "perfil id obrigatório".

**Correção:** Lógica explícita para garantir envio correto:
```ts
const perfilId = values.perfil != null && values.perfil !== ''
  ? Number(values.perfil)
  : undefined
perfilId: !Number.isNaN(perfilId) && perfilId > 0 ? perfilId : undefined
```

### 4. Botão Aprovar sem perfil selecionado

**Problema:** No `PainelDetalharSolicitacao`, o usuário podia clicar em "Aprovar" sem selecionar perfil, gerando erro 422 no backend.

**Correção:** Botão desabilitado quando `!perfilSelecionado`: `:disabled="avaliando || !perfilSelecionado"`.

### 5. E-mail .gov.br em ambiente local (backend)

**Problema:** Em desenvolvimento local, testes com e-mails não .gov.br falhavam.

**Correção:** Validação `ends_with:.gov.br` aplicada apenas quando `APP_ENV !== 'local'`. Em produção, a regra permanece obrigatória.

---

## Segurança da Informação

- **CPF:** Armazenado apenas como hash (HMAC-SHA256); nunca em texto plano.
- **cpf_exibicao:** Usado em dev/teste para exibição mascarada; em produção pode ser omitido.
- **Token:** Enviado automaticamente pelo `ApiService` quando o usuário está logado.
- **Hierarquia:** Federal vê tudo; Estadual apenas sua UF; Municipal apenas seu município.

---

## Regras de Negócio Respeitadas

1. **Visibilidade por perfil:** Federal, Estadual e Municipal com escopos corretos.
2. **E-mail institucional:** Obrigatório .gov.br em produção.
3. **Perfil obrigatório:** Quando o gestor cadastra (usuário autenticado).
4. **Vigência:** Início obrigatório quando autenticado; fim opcional.
5. **CPF:** Sempre obrigatório; validado por algoritmo oficial.
6. **Hierarquia de cadastro:** Estadual/Municipal só cadastram dentro do seu escopo.

---

## Como Testar

1. Subir o ambiente: `.\iniciar-nvsl.ps1` ou `docker compose up -d` em NVSL_DOCKER
2. Executar seeder: `docker exec nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force`
3. Acessar http://localhost:5174
4. Fazer login como **Federal**
5. Clicar em **Gerenciar Cadastros** → **Cadastrar usuário**
6. Preencher o formulário com:
   - Nome, CPF válido, e-mail terminando em .gov.br
   - Telefone 10 ou 11 dígitos
   - Esfera, UF, Município, Órgão, Cargo
   - Perfil e vigência inicial
7. Clicar em **Confirmar**
8. Verificar se a solicitação aparece na listagem com status "Em análise"
9. Clicar em **Detalhar/Analisar** → Selecionar perfil → **Aprovar**

---

## Arquivos Alterados

- `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/FormularioCadastrarUsuario.vue` — validações de e-mail e telefone
- `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` — montagem do payload perfilId
- `NVSL_FRONTEND/src/features/gerenciar-solicitacao-cadastro/components/PainelDetalharSolicitacao.vue` — desabilitar Aprovar sem perfil
- `NVSL_BACKEND/app/Http/Requests/SolicitacaoCadastroRequest.php` — relaxar .gov.br em local
