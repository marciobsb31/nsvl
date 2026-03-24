# Passo a Passo — Testar Gerenciar Solicitações de Cadastros

## Pré-requisitos

1. **Ambiente rodando** (Docker ou local)
2. **Usuários de teste criados** no banco
3. **Solicitações de exemplo** (criadas pelo seeder)

---

## 1. Preparar o ambiente

### 1.1 Subir os containers

```powershell
cd c:\xampp\htdocs\NVSLLOCAL
.\iniciar-nvsl.ps1
```

Ou manualmente:

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_DOCKER
docker compose up -d
```

### 1.2 Criar usuários e solicitações de exemplo

```powershell
cd c:\xampp\htdocs\NVSLLOCAL\NVSL_BACKEND
php artisan db:seed --class=UsuarioExemploSeeder --force
```

**Com Docker:**

```powershell
docker exec -it nvsl-backend php artisan db:seed --class=UsuarioExemploSeeder --force
```

Isso cria:
- **5 usuários:** Federal, Estadual (GO), Municipal (Alexânia/GO), Carlos Souza (4 perfis), Roberto Alves (3 perfis)
- **6 solicitações** em diferentes esferas, UFs e municípios
- **Carlos Souza** e **Roberto Alves** com múltiplos perfis vinculados (vigente e não vigente)

---

## 2. Acessar a aplicação

1. Abra o navegador em: **http://localhost:5174**
2. Você será redirecionado para **/login** (tela de entrada)

---

## 3. Cenário 1 — Acesso Federal (todas as solicitações)

1. Na tela de login, selecione **"Federal — acesso a todas as solicitações"**
2. Clique em **"Entrar"**
3. Você será redirecionado para **Gerenciar Cadastros**
4. **Resultado esperado:** A listagem exibe **todas as 6 solicitações** (Federal, Estadual GO, Estadual SP, Municipal Alexânia)

---

## 4. Cenário 2 — Acesso Estadual (apenas UF GO)

1. Clique em **"Sair"** no header (ou acesse /login)
2. Selecione **"Estadual (GO) — apenas solicitações da UF GO"**
3. Clique em **"Entrar"**
4. Acesse **Gerenciar Cadastros**
5. **Resultado esperado:** A listagem exibe **apenas 2 solicitações** (Fernanda Lima e Roberto Alves — esfera estadual, UF GO). Não exibe solicitações municipais nem de outras UFs (SP, DF).

---

## 5. Cenário 3 — Acesso Municipal (apenas Alexânia)

1. Clique em **"Sair"**
2. Selecione **"Municipal (Alexânia/GO) — apenas Alexânia"**
3. Clique em **"Entrar"**
4. Acesse **Gerenciar Cadastros**
5. **Resultado esperado:** A listagem exibe **apenas 2 solicitações** (Patrícia Mendes e Lucas Ferreira — esfera municipal, município Alexânia). Não exibe solicitações estadual ou de outros municípios.

---

## 6. Cenário — Perfis vinculados (múltiplos perfis por usuário)

1. Entre com perfil **Federal** (obrigatório — Carlos e Roberto só aparecem para Federal)
2. Clique em **"Listar"** para carregar as solicitações
3. Na listagem, localize **Carlos Souza** ou **Roberto Alves** (status Aprovada)
4. Clique em **"Detalhar"**
5. **Resultado esperado:** O painel abre com a seção **"Perfis vinculados"**:
   - Título: "Perfis vinculados"
   - Subtítulo: "Um usuário pode ter vários perfis. Cada vínculo possui vigência, status e contexto de atuação."
   - Tabela com colunas: Perfil, Vig. início, Vig. fim, Status, Esfera, UF, Município, Órgão, Cargo/Função, Ações
   - **Carlos Souza:** 4 perfis (Gestor, Analista, Visualizador, Administrador) — alguns com badge "Vigente" (verde), outros "Não vigente" (cinza)
   - Botão **"Adicionar perfil"** abaixo da tabela
   - Botão **"Reprovar"** em cada linha de perfil

---

## 7. Cenário 4 — Botão "Detalhar/Analisar"

1. Entre com qualquer perfil
2. Na listagem, localize uma solicitação com status **"Em análise"**
3. **Resultado esperado:** O botão na coluna Ações exibe **"Detalhar/Analisar"**
4. Clique no botão
5. O modal abre com os detalhes e os botões **"Reprovar"** e **"Aprovar"**

---

## 8. Cenário 5 — Botão "Detalhar"

1. Na listagem, localize uma solicitação com status **"Aprovada"** ou **"Reprovada"**
2. **Resultado esperado:** O botão na coluna Ações exibe **"Detalhar"**
3. Clique no botão
4. O modal abre apenas com os detalhes (sem botões Aprovar/Reprovar)

---

## 9. Cenário 6 — Filtros e botões

### 9.1 Pesquisar

1. Preencha um ou mais filtros (CPF, Nome, UF, Município, Órgão, Esfera, Situação)
2. Clique em **"Pesquisar"**
3. **Resultado esperado:** A listagem é atualizada conforme os filtros

### 9.2 Limpar Filtro

1. Com filtros preenchidos, clique em **"Limpar Filtro"**
2. **Resultado esperado:** Todos os filtros são limpos e a listagem é recarregada

### 9.3 Autocomplete

1. Nos campos **Estado (UF)**, **Esfera de atuação** e **Situação da solicitação**
2. Digite ou selecione uma opção
3. **Resultado esperado:** O combo permite busca e seleção

---

## 10. Cenário 7 — Cadastrar usuário

1. Na tela Gerenciar Cadastros, clique em **"Cadastrar usuário"**
2. **Resultado esperado:** Um painel lateral abre com o formulário de cadastro
3. Preencha os dados e clique em **"Confirmar"**
4. **Resultado esperado:** A solicitação é criada e a listagem é atualizada

---

## 11. Cenário 8 — Aprovar/Reprovar

1. Entre com perfil Federal (para ver todas)
2. Clique em **"Detalhar/Analisar"** em uma solicitação "Em análise"
3. Clique em **"Aprovar"**
4. **Resultado esperado:** Mensagem de sucesso, modal fecha, listagem atualizada com status "Aprovada"
5. Repita com outra solicitação e clique em **"Reprovar"**
6. **Resultado esperado:** Status alterado para "Reprovada"

---

## 11. Auditoria

Os acessos à listagem são registrados em `audit_logs`. Para verificar:

```sql
SELECT * FROM audit_logs WHERE action = 'gerenciar_cadastros.listagem' ORDER BY created_at DESC;
```

---

## Resumo das regras de visibilidade

| Perfil   | O que vê                                                                 |
|----------|---------------------------------------------------------------------------|
| Federal  | Todas as solicitações (qualquer esfera, UF, município)                   |
| Estadual | Apenas esfera **estadual** e **mesma UF** de lotação do usuário          |
| Municipal| Apenas esfera **municipal**, **mesma UF** e **mesmo município**          |

---

## Solução de problemas

- **"Usuários de teste não encontrados"** → Execute o seeder (passo 1.2)
- **401 ao acessar Gerenciar Cadastros** → Faça login novamente em /login
- **Listagem vazia com Estadual/Municipal** → Verifique se há solicitações com esfera/UF/município compatíveis no seeder
- **Painel Detalhar não abre** → Consulte [DEBUG-PAINEL-DETALHAR.md](./DEBUG-PAINEL-DETALHAR.md) — abra o Console (F12) e verifique os logs `[Detalhar]`
