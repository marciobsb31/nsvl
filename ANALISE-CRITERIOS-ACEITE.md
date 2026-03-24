# Análise dos Critérios de Aceite — Gerenciar Cadastros

## Resumo

| # | Critério | Status | Observação |
|---|----------|--------|------------|
| 1 | Federal visualiza todos os registros | ✅ | Backend aplica sem filtro de visibilidade |
| 2 | Estadual visualiza apenas sua UF (não município) | ✅ | Filtra por esfera=estadual + UF do usuário |
| 3 | Municipal visualiza apenas seu Município | ✅ | Filtra por esfera=municipal + UF + município |
| 4 | Filtros comportando perfeitamente | ✅ | CPF, nome, UF, município, órgão, esfera, status |
| 5 | Combos com autocomplete | ✅ | UF, Esfera, Status, Município com SelectAutocomplete; Órgão é texto livre |
| 6 | Botão Cadastrar abre tela de criação | ✅ | "Cadastrar usuário" abre FormularioCadastrarUsuario |
| 7 | Botão Pesquisar realiza pesquisa com filtros | ✅ | Emite @pesquisar com filtros aplicados |
| 8 | Botão Limpar Filtro limpa filtros | ✅ | Limpa todos e emite @limpar |
| 9 | Botão Detalhar/Analisar conforme status | ✅ | Em análise → "Detalhar/Analisar"; demais → "Detalhar" |
| 10 | Logs de auditoria na listagem | ✅ | AuditLogService registra gerenciar_cadastros.listagem |
| 11 | Tela impactada: Gerenciar Cadastros | ✅ | GerenciarSolicitacaoCadastroPage.vue |

---

## Detalhamento

### 1. Visibilidade por perfil (Backend)

**Arquivo:** `NVSL_BACKEND/app/Http/Controllers/SolicitacaoCadastroController.php`

- **Federal:** `aplicarVisibilidadePorPerfil` retorna sem aplicar filtro → vê todas as solicitações.
- **Estadual:** Filtra `esfera_atuacao=estadual` e `uf=user->uf_lotacao`. Não filtra por município (vê todos os municípios da UF).
- **Municipal:** Filtra `esfera_atuacao=municipal`, `uf` e `municipio` do usuário.

### 2. Filtros

**Arquivo:** `FiltrosGerenciarSolicitacao.vue`

- CPF, Nome, UF, Município, Órgão, Esfera, Situação.
- Pesquisar envia filtros; Limpar Filtro zera todos e dispara nova pesquisa.

### 3. Autocomplete

**Implementado:** UF, Esfera, Situação e Município usam `SelectAutocomplete`.

Órgão permanece como input de texto (não é combo). O critério exige autocomplete em “campos de combos”. Município tem lista em `OPCOES_MUNICIPIOS`; Órgão costuma ser texto livre.

### 4. Botões

- **Cadastrar usuário:** Abre painel lateral com `FormularioCadastrarUsuario`.
- **Pesquisar:** Chama `aplicarFiltros` com os filtros preenchidos.
- **Limpar Filtro:** Chama `limparEpesquisar` e recarrega a listagem.

### 5. Detalhar/Analisar

**Arquivo:** `GerenciarSolicitacaoCadastroPage.vue`

```ts
function rotuloBotaoDetalhar(status: string) {
  return status === 'em_analise' ? 'Detalhar/Analisar' : 'Detalhar'
}
```

### 6. Auditoria

**Arquivo:** `SolicitacaoCadastroController.php`

```php
$this->auditLogService->log('gerenciar_cadastros.listagem', $user->id, [
    'total_registros' => $itens->count(),
    'filtros'         => $request->only([...]),
]);
```

---

## Ajuste realizado

- **Município (filtro):** Passou a usar `SelectAutocomplete` com `OPCOES_MUNICIPIOS`.
- **Órgão:** Mantido como input de texto (não é combo, não possui lista fixa).
