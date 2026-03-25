import { expect, test } from '@playwright/test'

const DELAY_ENTRE_TESTES_MS = 10_000

test.describe('Demo para apresentacao ao cliente', () => {
  test.describe.configure({ timeout: 90_000 })

  test.afterEach(async ({ page }) => {
    if (!page.isClosed()) {
      await page.waitForTimeout(DELAY_ENTRE_TESTES_MS)
    }
  })

  test('deve logar e preencher formulario de novo perfil', async ({ page }) => {
    await test.step('Acessa tela de login', async () => {
      await page.goto('/login')
      await expect(page.getByRole('heading', { name: 'Acesse o sistema' })).toBeVisible()
    })

    await test.step('Realiza login de teste', async () => {
      await page.selectOption('#perfil', 'federal')
      await page.getByRole('button', { name: /^Entrar$/ }).click()
      await expect(page).toHaveURL(/gerenciar-cadastros/i)
    })

    await test.step('Abre a tela de gerenciar perfis', async () => {
      await page.goto('/gerenciar-perfis')
      await expect(page.getByRole('heading', { name: 'Gerenciar Perfis' })).toBeVisible()
    })

    await test.step('Abre painel de cadastro de perfil', async () => {
      await page.getByRole('button', { name: 'Novo perfil' }).click()
      await expect(page.getByRole('heading', { name: 'Cadastrar perfil' })).toBeVisible()
    })

    await test.step('Preenche dados principais do formulario', async () => {
      const nomePerfil = `Perfil Demo ${Date.now()}`
      await page.fill('#pf-nome', nomePerfil)
      await page.selectOption('#pf-esfera', 'estadual')
      await page.selectOption('#pf-status', 'ativo')
      await page.fill(
        '#pf-descricao',
        'Perfil preenchido automaticamente em demonstracao E2E com Playwright.'
      )

      await expect(page.locator('#pf-nome')).toHaveValue(nomePerfil)
      await expect(page.locator('#pf-esfera')).toHaveValue('estadual')
      await expect(page.locator('#pf-status')).toHaveValue('ativo')
    })
  })

  test('deve preencher tela de cadastrar usuario', async ({ page }) => {
    await test.step('Acessa pagina de solicitacao de cadastro com dados GOV.BR', async () => {
      await page.goto('/solicitacao-cadastro?nome=Usuario%20Demo&cpf=12345678901')
      await expect(page.getByRole('heading', { name: 'Solicitação de cadastro' })).toBeVisible()
      await expect(page.locator('#input-nome')).toHaveValue('Usuario Demo')
      await expect(page.locator('#input-cpf')).toHaveValue('123.456.789-01')
    })

    await test.step('Preenche dados obrigatorios do formulario', async () => {
      await page.fill('#input-email', 'usuario.demo@orgao.gov.br')
      await page.fill('#input-tel-inst', '61999990000')
      await page.fill('#input-orgao', 'Ministerio de Testes')
      await page.fill('#input-cargo', 'Analista de Validacao')
    })

    await test.step('Valida componentes principais da tela', async () => {
      await expect(page.getByRole('textbox', { name: /Esfera de atuação/i })).toBeVisible()
      await expect(page.getByRole('textbox', { name: /Estado \(UF\)/i })).toBeVisible()
      await expect(page.getByRole('textbox', { name: /Município/i })).toBeVisible()
      await expect(
        page.getByRole('button', { name: 'Confirmar e enviar solicitação' })
      ).toBeVisible()
    })
  })
})
