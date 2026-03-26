/**
 * Gera docs/MODELAGEM-DADOS-NVSL.pdf a partir do HTML na raiz do repositório.
 *
 * Uso (a partir de NVSL_FRONTEND):
 *   node scripts/generate-modelagem-pdf.mjs
 */
import { chromium } from '@playwright/test'
import { fileURLToPath } from 'node:url'
import path from 'node:path'
import fs from 'node:fs'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const frontendRoot = path.resolve(__dirname, '..')
const repoRoot = path.resolve(frontendRoot, '..')
const htmlPath = path.join(repoRoot, 'docs', 'MODELAGEM-DADOS-NVSL.html')
const outPath = path.join(repoRoot, 'docs', 'MODELAGEM-DADOS-NVSL.pdf')

if (!fs.existsSync(htmlPath)) {
  console.error('Arquivo não encontrado:', htmlPath)
  process.exit(1)
}

const fileUrl = 'file:///' + htmlPath.replace(/\\/g, '/')

const browser = await chromium.launch()
try {
  const page = await browser.newPage()
  await page.goto(fileUrl, { waitUntil: 'load' })
  await page.pdf({
    path: outPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '16mm', right: '14mm', bottom: '16mm', left: '14mm' },
  })
  console.log('PDF gerado:', outPath)
} finally {
  await browser.close()
}
