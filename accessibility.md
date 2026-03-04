# Acessibilidade — NVSL Frontend

## Conformidade

A aplicação segue as diretrizes **WCAG 2.1 nível AA** e o padrão **eMAG (Modelo de Acessibilidade em Governo Eletrônico)**, obrigatório para sistemas do Governo Federal Brasileiro.

## Práticas Implementadas

### 1. Idioma da Página
```html
<html lang="pt-BR">
```
Define o idioma correto para leitores de tela.

### 2. Skip Link (Pular para o Conteúdo)
Link oculto visível apenas ao navegar por teclado, que permite pular menus e ir direto ao conteúdo principal:
```html
<a href="#main-content" class="skip-link">Ir para o conteúdo principal</a>
```

### 3. Landmark Roles
Uso de elementos semânticos HTML5 e ARIA roles:
- `<header role="banner">` — cabeçalho da página
- `<main id="main-content" tabindex="-1">` — conteúdo principal
- `<footer role="contentinfo">` — rodapé
- `<nav>` — navegação
- `<section aria-labelledby="...">` — seções com títulos associados

### 4. Foco Visível
Foco sempre visível com anel de destaque amarelo (token GOV.BR DS):
```css
:focus-visible {
  outline: 3px solid var(--color-support-05, #ffcd07);
  outline-offset: 2px;
}
```

### 5. Área de Toque Mínima
Botões com altura mínima de **48px** para atender ao requisito de acessibilidade mobile (WCAG 2.5.8).

### 6. Feedback de Estado
- `aria-busy="true"` em botões durante carregamento
- `aria-live="assertive"` em mensagens de erro
- `aria-live="polite"` em mensagens de status
- `role="alert"` para conteúdo urgente

### 7. Textos Alternativos
- `alt` descritivo em todas as imagens
- `aria-hidden="true"` em elementos decorativos (ícones SVG)
- `aria-label` em botões sem texto visível

### 8. Hierarquia de Títulos
Cada página tem exatamente **um `<h1>`**, com `<h2>` para subseções.

### 9. Contraste de Cores
Uso das cores do GOV.BR DS que já passam nos critérios de contraste AA:
- Texto principal sobre fundo: ≥ 4.5:1
- Texto grande sobre fundo: ≥ 3:1

### 10. Formulários e Controles
- `<label>` associado a cada campo
- `aria-label` em controles sem label visível
- `aria-required` em campos obrigatórios

## Ferramentas para Teste de Acessibilidade

| Ferramenta | Tipo | Link |
|---|---|---|
| NVDA | Leitor de tela (Windows) | https://www.nvaccess.org |
| VoiceOver | Leitor de tela (macOS/iOS) | Nativo no macOS |
| axe DevTools | Extensão de navegador | https://www.deque.com/axe/ |
| Lighthouse | Auditoria integrada Chrome | DevTools → Lighthouse |
| ASES | Avaliador do Governo Federal | http://asesweb.governoeletronico.gov.br |

## Checklist de Acessibilidade

- [ ] Navegação completa por teclado (Tab, Shift+Tab, Enter, Esc)
- [ ] Skip link funcional
- [ ] Leitores de tela leem corretamente o nome do usuário autenticado
- [ ] Botão "Entrar com GOV.BR" anunciado corretamente
- [ ] Página de callback anuncia status de carregamento
- [ ] Mensagens de erro anunciadas em tempo real
- [ ] Contraste de cores verificado
- [ ] Auditoria Lighthouse ≥ 90 em acessibilidade
