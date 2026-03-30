import { expect, test } from '@playwright/test'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

/**
 * Gera prints PNG para o pacote de evidências da feature 24249.
 *
 * Pré-requisitos:
 * - Backend Laravel em execução (ex.: :8081) com rota POST /api/auth/token-de-teste.
 * - Playwright inicia o Vite via webServer em playwright.config (porta 5176) ou use servidor já em execução.
 *
 * Comando: npm run e2e:prints-24249
 * Saída: Documentações/24249/prints/*.png (raiz do repositório)
 */

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const PRINTS_DIR = path.resolve(__dirname, '../../../Documentações/24249/prints')

const MOCK_LISTAGEM = {
  data: [
    {
      id: 1,
      nome: 'Maria Silva',
      status: 'em_analise',
      created_at: '2026-03-28T12:00:00.000000Z',
      cpf: '***.***.***-01',
      esfera_atuacao: 'Estadual',
      uf: 'GO',
      municipio: 'Goiânia',
      orgao: 'Secretaria de Educação',
    },
    {
      id: 2,
      nome: 'João Santos',
      status: 'aprovado',
      created_at: '2026-03-27T10:00:00.000000Z',
      cpf: '***.***.***-02',
      esfera_atuacao: 'Federal',
      uf: 'DF',
      municipio: '',
      orgao: 'Órgão Federal X',
    },
    {
      id: 3,
      nome: 'Ana Costa',
      status: 'reprovado',
      created_at: '2026-03-26T15:00:00.000000Z',
      cpf: '***.***.***-03',
      esfera_atuacao: 'Municipal',
      uf: 'GO',
      municipio: 'Alexânia',
      orgao: 'Prefeitura Municipal',
    },
  ],
}

const MOCK_DETALHE = {
  id: 1,
  nome: 'Maria Silva',
  cpf: '***.***.***-01',
  status: 'em_analise',
  created_at: '2026-03-28T12:00:00.000000Z',
  email_institucional: 'maria.silva@org.gov.br',
  telefone_institucional: '6233334444',
  esfera_atuacao: 'estadual',
  uf: 'GO',
  municipio: 'Goiânia',
  orgao: 'Secretaria de Educação',
  cargo: 'Analista',
  perfis_vinculados: [],
}

const MOCK_LOCALIDADES_COMPLETO = {
  data: {
    ufs: [{ value: 'GO', label: 'GO - Goiás', id: 9 }],
    municipios_por_uf: {
      GO: [
        { value: 'Alexânia', label: 'Alexânia' },
        { value: 'Goiânia', label: 'Goiânia' },
      ],
    },
  },
}

/** Payload da listagem pode ser trocado antes dos prints estadual/municipal. */
const mockState = {
  listagem: MOCK_LISTAGEM,
}

function usuarioTokenMock(perfil: string) {
  const base = {
    perfis_vigentes: [
      {
        perfil_usuario_id: 1,
        perfil_id: 1,
        nome: 'Perfil evidências',
        ativo: true,
      },
    ],
  }
  if (perfil === 'estadual') {
    return {
      ...base,
      id: 2,
      name: 'Usuário estadual (GO)',
      esfera_atuacao: 'estadual',
      uf_lotacao: 'GO',
      municipio_lotacao: '',
    }
  }
  if (perfil === 'municipal') {
    return {
      ...base,
      id: 3,
      name: 'Usuário municipal (Alexânia)',
      esfera_atuacao: 'municipal',
      uf_lotacao: 'GO',
      municipio_lotacao: 'Alexânia',
    }
  }
  return {
    ...base,
    id: 1,
    name: 'Usuário federal (teste)',
    esfera_atuacao: 'federal',
    uf_lotacao: '',
    municipio_lotacao: '',
  }
}

async function setupAuthMocks(page: import('@playwright/test').Page) {
  await page.route('**/api/auth/token-de-teste', async (route) => {
    if (route.request().method() !== 'POST') {
      await route.continue()
      return
    }
    let perfil = 'federal'
    try {
      const body = route.request().postDataJSON() as { perfil?: string }
      if (body?.perfil && ['federal', 'estadual', 'municipal'].includes(body.perfil)) {
        perfil = body.perfil
      }
    } catch {
      /* ignore */
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        token: `playwright-mock-${perfil}`,
        user: usuarioTokenMock(perfil),
      }),
    })
  })

  await page.route('**/api/auth/logout', async (route) => {
    await route.fulfill({ status: 204, body: '' })
  })
}

async function setupApiMocks(page: import('@playwright/test').Page) {
  await setupAuthMocks(page)

  await page.route('**/api/solicitacoes-cadastro**', async (route) => {
    const url = route.request().url()
    const method = route.request().method()

    if (url.includes('verificar-cpf')) {
      await route.continue()
      return
    }

    if (method !== 'GET') {
      await route.continue()
      return
    }

    try {
      const u = new URL(url)
      const segs = u.pathname.split('/').filter(Boolean)
      const idx = segs.indexOf('solicitacoes-cadastro')
      if (idx === -1) {
        await route.continue()
        return
      }
      const idSeg = segs[idx + 1]
      if (idSeg && /^\d+$/.test(idSeg)) {
        await route.fulfill({
          status: 200,
          contentType: 'application/json',
          body: JSON.stringify({ ...MOCK_DETALHE, id: Number(idSeg) }),
        })
        return
      }
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify(mockState.listagem),
      })
    } catch {
      await route.continue()
    }
  })

  await page.route('**/api/localidades/completo**', async (route) => {
    if (route.request().method() !== 'GET') {
      await route.continue()
      return
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify(MOCK_LOCALIDADES_COMPLETO),
    })
  })

  await page.route('**/api/localidades/ufs**', async (route) => {
    if (route.request().method() !== 'GET') {
      await route.continue()
      return
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: MOCK_LOCALIDADES_COMPLETO.data.ufs }),
    })
  })

  await page.route('**/api/localidades/municipios**', async (route) => {
    if (route.request().method() !== 'GET') {
      await route.continue()
      return
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: MOCK_LOCALIDADES_COMPLETO.data.municipios_por_uf.GO }),
    })
  })
}

async function loginPerfil(
  page: import('@playwright/test').Page,
  perfil: 'federal' | 'estadual' | 'municipal'
) {
  await page.goto('/login')
  await expect(page.getByRole('heading', { name: 'Acesse o sistema' })).toBeVisible()
  await page.selectOption('#perfil', perfil)
  await page.getByRole('button', { name: /^Entrar$/ }).click()
  await expect(page).toHaveURL(/gerenciar-cadastros/i, { timeout: 30_000 })
}

test.describe('Feature 24249 — geração de prints (evidências)', () => {
  test.describe.configure({ timeout: 180_000 })

  test('gera PNGs em Documentações/24249/prints', async ({ page }) => {
    fs.mkdirSync(PRINTS_DIR, { recursive: true })
    mockState.listagem = MOCK_LISTAGEM
    await setupApiMocks(page)

    await test.step('Login federal + menu + listagem', async () => {
      await loginPerfil(page, 'federal')
      await page.waitForTimeout(500)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '01-menu-gerenciar-cadastros.png'),
        fullPage: false,
      })

      await page.getByRole('button', { name: 'Pesquisar solicitações' }).click()
      await expect(page.locator('table.tabela-solicitacoes')).toBeVisible({ timeout: 20_000 })
      await page.waitForTimeout(400)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '02-tela-principal-listagem.png'),
        fullPage: true,
      })
    })

    await test.step('Contexto ativo (federal)', async () => {
      const banner = page.locator('.contexto-banner')
      if (await banner.isVisible()) {
        await banner.screenshot({ path: path.join(PRINTS_DIR, '10-contexto-ativo.png') })
      } else {
        await page.screenshot({
          path: path.join(PRINTS_DIR, '10-contexto-ativo.png'),
          fullPage: false,
        })
      }
    })

    await test.step('Autocompletes UF, Município, Esfera, Situação', async () => {
      await page.getByRole('button', { name: /Exibir lista de Estado \(UF\)/i }).click()
      await page.locator('.br-list--autocomplete').first().waitFor({ state: 'visible', timeout: 5000 })
      await page.waitForTimeout(200)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '03-autocomplete-uf.png'),
        fullPage: false,
      })
      await page.getByText('GO - Goiás', { exact: true }).click()

      await page.waitForTimeout(300)
      await page.getByRole('button', { name: /Exibir lista de Município/i }).click()
      await page.locator('.br-list--autocomplete').first().waitFor({ state: 'visible', timeout: 5000 })
      await page.waitForTimeout(200)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '04-autocomplete-municipio.png'),
        fullPage: false,
      })
      await page.keyboard.press('Escape')

      await page.waitForTimeout(200)
      await page.getByRole('button', { name: /Exibir lista de Esfera de atuação/i }).click()
      await page.locator('.br-list--autocomplete').first().waitFor({ state: 'visible', timeout: 5000 })
      await page.waitForTimeout(200)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '05-autocomplete-esfera-status.png'),
        fullPage: false,
      })
      await page.keyboard.press('Escape')

      await page.waitForTimeout(200)
      await page.getByRole('button', { name: /Exibir lista de Situação da solicitação/i }).click()
      await page.locator('.br-list--autocomplete').first().waitFor({ state: 'visible', timeout: 5000 })
      await page.waitForTimeout(200)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '05b-autocomplete-situacao.png'),
        fullPage: false,
      })
      await page.keyboard.press('Escape')
    })

    await test.step('Botões Limpar Filtro e Pesquisar', async () => {
      const filtros = page.locator('.filtros-gerenciar')
      await filtros.screenshot({ path: path.join(PRINTS_DIR, '08-botoes-pesquisar-limpar.png') })
    })

    await test.step('Linhas com Detalhar/Analisar e Detalhar', async () => {
      const rowMaria = page.locator('table.tabela-solicitacoes tbody tr').filter({ hasText: 'Maria Silva' })
      await rowMaria.screenshot({ path: path.join(PRINTS_DIR, '06-botao-detalhar-analisar.png') })

      const rowJoao = page.locator('table.tabela-solicitacoes tbody tr').filter({ hasText: 'João Santos' })
      await rowJoao.screenshot({ path: path.join(PRINTS_DIR, '07-botao-detalhar.png') })
    })

    await test.step('Filtro + resultado', async () => {
      await page.getByRole('button', { name: 'Limpar filtros' }).click()
      await page.waitForTimeout(500)
      await page.getByLabel('Nome completo').fill('Maria')
      await page.getByRole('button', { name: 'Pesquisar solicitações' }).click()
      await expect(page.locator('table.tabela-solicitacoes')).toBeVisible({ timeout: 15_000 })
      await page.waitForTimeout(400)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '11-filtro-resultado.png'),
        fullPage: true,
      })
    })

    await test.step('Painel detalhar / analisar', async () => {
      await page
        .locator('table.tabela-solicitacoes tbody tr')
        .filter({ hasText: 'Maria Silva' })
        .getByRole('button', { name: 'Detalhar/Analisar' })
        .click()
      await expect(page.locator('.painel-cadastro')).toBeVisible({ timeout: 15_000 })
      await page.waitForTimeout(500)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '09-painel-detalhe-avaliacao.png'),
        fullPage: true,
      })
      await page
        .locator('aside[aria-label="Detalhar solicitação de cadastro"]')
        .getByLabel('Voltar')
        .click()
      await page.waitForTimeout(400)
    })

    await test.step('Painel Cadastrar usuário', async () => {
      await page.getByRole('button', { name: 'Cadastrar usuário' }).click()
      await expect(page.getByRole('heading', { name: 'Cadastrar usuário' })).toBeVisible({
        timeout: 15_000,
      })
      await page.waitForTimeout(400)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '09b-painel-cadastrar-usuario.png'),
        fullPage: true,
      })
      await page
        .locator('aside[aria-label="Formulário cadastrar usuário"]')
        .getByLabel('Voltar')
        .click()
    })

    await test.step('Contexto estadual e municipal', async () => {
      await page.getByRole('button', { name: 'Sair' }).click()
      await page.waitForTimeout(800)
      await page.goto('/login')

      mockState.listagem = { data: MOCK_LISTAGEM.data.filter((r) => r.esfera_atuacao === 'Estadual' && r.uf === 'GO') }
      await loginPerfil(page, 'estadual')
      await page.waitForTimeout(500)
      await page.getByRole('button', { name: 'Pesquisar solicitações' }).click()
      await expect(page.locator('table.tabela-solicitacoes, .contexto-banner')).first().toBeVisible({
        timeout: 20_000,
      })
      await page.waitForTimeout(400)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '13-contexto-estadual-listagem.png'),
        fullPage: true,
      })

      await page.getByRole('button', { name: 'Sair' }).click()
      await page.waitForTimeout(800)
      await page.goto('/login')

      mockState.listagem = {
        data: MOCK_LISTAGEM.data.filter(
          (r) => r.esfera_atuacao === 'Municipal' && r.municipio === 'Alexânia'
        ),
      }
      await loginPerfil(page, 'municipal')
      await page.waitForTimeout(500)
      await page.getByRole('button', { name: 'Pesquisar solicitações' }).click()
      await page.waitForTimeout(600)
      await page.screenshot({
        path: path.join(PRINTS_DIR, '14-contexto-municipal-listagem.png'),
        fullPage: true,
      })
    })
  })
})
