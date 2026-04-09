import { expect, test } from '@playwright/test'

/**
 * Evidências em vídeo da tela Gerenciar cadastros (solicitações).
 * Cada teste gera um arquivo .webm em test-results/ (config: video: 'on').
 *
 * Documento de requisitos (raiz do repositório): docs/REQUISITOS-E2E-GERENCIAR-CADASTROS.md
 */
test.describe('Evidências — Gerenciar solicitação de cadastros', () => {
  test.describe.configure({ timeout: 120_000 })

  async function loginPerfilFederal(page: import('@playwright/test').Page) {
    await page.goto('/login')
    await expect(page.getByRole('heading', { name: 'Acesse o sistema' })).toBeVisible()
    await page.selectOption('#perfil', 'federal')
    await page.getByRole('button', { name: /^Entrar$/ }).click()
    await expect(page).toHaveURL(/gerenciar-cadastros/i)
  }

  test('RF-01 a RF-03 — Acesso, cabeçalho e filtros da listagem', async ({ page }) => {
    await test.step('RF-01 — Autenticação de teste e redirecionamento para Gerenciar cadastros', async () => {
      await loginPerfilFederal(page)
    })

    await test.step('RF-02 — Título da página e ação principal Cadastrar usuário', async () => {
      await expect(
        page.getByRole('heading', {
          name: 'Gerenciar solicitação de cadastros no sistema',
        })
      ).toBeVisible()
      await expect(page.getByRole('button', { name: 'Cadastrar usuário' })).toBeVisible()
    })

    await test.step('RF-03 — Bloco de filtros e botões Limpar / Pesquisar', async () => {
      await expect(page.getByLabel('CPF')).toBeVisible()
      await expect(page.getByLabel('Nome completo')).toBeVisible()
      await expect(page.getByRole('button', { name: 'Limpar filtros' })).toBeVisible()
      await expect(page.getByRole('button', { name: 'Pesquisar solicitações' })).toBeVisible()
    })
  })

  test('RF-04 — Pesquisa e área de resultados (lista ou estado vazio)', async ({ page }) => {
    await loginPerfilFederal(page)

    await test.step('RF-04a — Acionar Pesquisar', async () => {
      await page.getByRole('button', { name: 'Pesquisar solicitações' }).click()
    })

    await test.step('RF-04b — Resultado: carregando, tabela ou mensagem orientativa', async () => {
      const tabela = page.locator('table.tabela-solicitacoes')
      const vazio = page.getByText(/Nenhuma solicitação encontrada|Aplique os filtros/i)
      await expect(tabela.or(vazio)).toBeVisible({ timeout: 30_000 })
    })
  })

  test('RF-05 — Abrir painel Cadastrar usuário', async ({ page }) => {
    await loginPerfilFederal(page)

    await test.step('RF-05 — Clique em Cadastrar usuário e painel lateral/formulário', async () => {
      await page.getByRole('button', { name: 'Cadastrar usuário' }).click()
      await expect(page.getByRole('heading', { name: 'Cadastrar usuário' })).toBeVisible({
        timeout: 15_000,
      })
    })
  })
})
