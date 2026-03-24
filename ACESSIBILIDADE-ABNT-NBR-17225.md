# Acessibilidade — ABNT NBR 17225:2025

## Sobre a norma

A **ABNT NBR 17225:2025** é a norma brasileira de acessibilidade digital publicada em março de 2025. Estabelece requisitos e recomendações para tornar sites e aplicações web acessíveis a pessoas com deficiência, necessidades temporárias ou limitações situacionais.

A norma está alinhada às **WCAG 2.2** do W3C e cumpre o artigo 63º da Lei Brasileira de Inclusão (LBI — Lei nº 13.146/2015).

### Tipos de conformidade

| Tipo | Descrição |
|------|-----------|
| **Conformidade Regular** | Atendimento aos requisitos obrigatórios (níveis A e AA da WCAG 2.2) |
| **Conformidade Plena** | Incorpora todas as recomendações adicionais (nível AAA) |

O sistema NVSL busca a **Conformidade Regular** como meta mínima.

---

## Checklist de implementação

### 1. Estrutura e semântica (WCAG 1.3.1)

| Item | Status | Implementação |
|------|--------|----------------|
| Cabeçalhos hierárquicos (h1 → h2 → h3) | ✅ | Uso de `<h1>` único por página; hierarquia lógica nos componentes |
| Landmarks (main, nav, header, footer) | ✅ | `DefaultLayout`, `PublicLayout` com `<main id="main-content">`, Header, Footer |
| Listas corretamente declaradas | ✅ | Uso de `<ul>`, `<ol>`, `<li>` quando aplicável |
| Tabelas com scope e cabeçalhos | ✅ | Tabelas em `PainelDetalharSolicitacao`, `GerenciarSolicitacaoCadastroPage` com `<th scope="col">` |

### 2. Codificação e marcação semântica

| Item | Status | Implementação |
|------|--------|----------------|
| Idioma da página (lang) | ✅ | `<html lang="pt-BR">` em `index.html` |
| Título descritivo por página | ✅ | `document.title` definido por rota em `router.beforeEach` |
| Nome acessível em elementos interativos | ✅ | `aria-label` em botões de ícone; labels associados a inputs |
| Mensagens de status anunciadas | ✅ | `role="status"` e `aria-live="polite"` em mensagens de erro/sucesso |
| Zoom até 200% sem perda | ✅ | Unidades relativas (rem, em); sem `max-width` fixo que impeça zoom |

### 3. Tamanho de fonte e espaçamento (WCAG 1.4.12)

| Item | Status | Implementação |
|------|--------|----------------|
| Unidades relativas (rem, em) | ✅ | `font-size: 1rem`; uso de rem/em no CSS |
| line-height ≥ 1.5 | ✅ | `body { line-height: 1.5 }` em `main.css` |
| Espaçamento entre parágrafos | ✅ | `margin-bottom` em parágrafos e blocos de texto |
| letter-spacing e word-spacing ajustáveis | ✅ | Valores em `em` para permitir herança |

### 4. Contraste e cores (WCAG 1.4.3, 1.4.11)

| Item | Status | Implementação |
|------|--------|----------------|
| Contraste ≥ 4.5:1 para texto normal | ✅ | Tokens GOV.BR DS; cores contrastantes |
| Contraste ≥ 3:1 para elementos gráficos | ✅ | Botões e ícones com cores contrastantes |
| Cor não é único indicativo | ✅ | Mensagens de erro com texto + ícone; não apenas cor |

### 5. Interação por teclado

| Item | Status | Implementação |
|------|--------|----------------|
| Navegação por teclado completa | ✅ | Todos os elementos interativos focáveis |
| Skip link | ✅ | "Ir para o conteúdo principal" em `App.vue` |
| Indicador de foco visível | ✅ | `:focus-visible { outline: 3px solid }` em `main.css` |
| Ordem de tabulação lógica | ✅ | Ordem natural do DOM; sem `tabindex` positivo |

### 6. Links e navegação (WCAG 2.4.4, 3.2.3)

| Item | Status | Implementação |
|------|--------|----------------|
| Texto do link descritivo | ✅ | Evitar "Clique aqui"; usar texto que indique destino |
| Navegação consistente | ✅ | Header e Sidebar fixos em todas as páginas autenticadas |
| Links externos sinalizados | ✅ | `aria-label` ou texto quando `target="_blank"` |

### 7. Formulários (WCAG 3.3.2, 4.1.2)

| Item | Status | Implementação |
|------|--------|----------------|
| Label associado a cada campo | ✅ | `<label for="id">` ou `aria-label` em selects |
| Mensagens de erro claras | ✅ | Texto descritivo; próximos ao campo |
| Campos obrigatórios indicados | ✅ | `required` e atributos `aria-required` |

### 8. Imagens e multimídia

| Item | Status | Implementação |
|------|--------|----------------|
| Texto alternativo (alt) | ✅ | Imagens com `alt` descritivo; decorativas com `alt=""` |
| Logos com alt | ✅ | `alt="Novo Viver Sem Limite"`, `alt="Logo Governo"` |

### 9. Tempo e animações (WCAG 2.2.2, 2.3.1)

| Item | Status | Implementação |
|------|--------|----------------|
| Respeitar prefers-reduced-motion | ✅ | `@media (prefers-reduced-motion: reduce)` no CSS |
| Sem flashes > 3 por segundo | ✅ | Sem animações que causem gatilhos epilépticos |

### 10. Tamanho de botões (WCAG 2.5.5, 2.5.8)

| Item | Status | Implementação |
|------|--------|----------------|
| Área mínima 24×24px (AA) | ✅ | Botões e ícones interativos com min-height/min-width |
| Espaçamento entre botões | ✅ | Evitar toques acidentais em mobile |

### 11. Recursos adicionais

| Item | Status | Implementação |
|------|--------|----------------|
| VLibras (Libras) | ✅ | Widget integrado em `index.html` |
| Botão voltar ao topo | ✅ | `ScrollToTop` com `aria-label="Voltar ao topo"` |

---

## Arquivos modificados

| Arquivo | Alterações |
|---------|------------|
| `index.html` | `lang="pt-BR"`, VLibras, viewport sem bloqueio de zoom |
| `src/assets/styles/main.css` | Skip-link, :focus-visible, line-height, espaçamento de parágrafos, área mínima de toque, `prefers-reduced-motion` |
| `src/App.vue` | Skip link "Ir para o conteúdo principal" |
| `src/router/index.ts` | Títulos descritivos por rota |
| `src/core/components/Header/Header.vue` | aria-label dinâmico no botão de tema |
| `src/core/components/Feedback/Feedback.vue` | role="alert" para mensagens de erro |
| `src/core/components/ScrollToTop/ScrollToTop.vue` | aria-label="Voltar ao topo" |
| `src/features/autenticacao/pages/LoginPage.vue` | role="alert" em mensagens de erro |
| `src/features/solicitacao-cadastro/pages/SolicitacaoCadastroIndex.vue` | role/aria-live em notificações |
| `src/features/gerenciar-solicitacao-cadastro/pages/GerenciarSolicitacaoCadastroPage.vue` | Exibição de notificações com role/aria-live |

---

## Referências

- [ABNT NBR 17225:2025](https://www.abnt.org.br/)(norma completa)
- [WCAG 2.2](https://www.w3.org/TR/WCAG22/)(em inglês)
- [WCAG 2.2 em português](https://www.w3c.br/traducoes/wcag/wcag22-pt-BR/)
- [Foco Acessível — ABNT NBR 17225](https://focoacessivel.com.br/blog/abnt-nbr-172252025-a-nova-norma-de-acessibilidade-digital-no-brasil/)
- [Lei Brasileira de Inclusão (LBI)](http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2015/lei/l13146.htm)

---

*Documento atualizado conforme implementação no sistema NVSL.*
