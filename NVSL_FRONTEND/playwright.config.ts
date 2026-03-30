import { defineConfig, devices } from '@playwright/test'

/** Porta padrão do Vite (`vite.config.ts` — strictPort). */
const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:5176'

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [
    ['list'],
    ['html', { open: 'never' }],
  ],
  webServer: {
    /** Use `E2E_VITE_COMMAND` se `npm` não estiver no PATH (ex.: node .../vite.js). */
    command: process.env.E2E_VITE_COMMAND ?? 'npm run dev',
    url: baseURL,
    reuseExistingServer: true,
    timeout: Number(process.env.E2E_WEB_SERVER_TIMEOUT_MS) || 300_000,
    env: { VITE_DISABLE_DEVTOOLS: '1' },
  },
  use: {
    baseURL,
    headless: false,
    viewport: { width: 1366, height: 768 },
    trace: 'on-first-retry',
    video: 'on',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      testIgnore: '**/gerenciar-cadastros-prints-24249.spec.ts',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'chromium-prints-24249',
      testMatch: '**/gerenciar-cadastros-prints-24249.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        headless: true,
        video: 'off',
      },
    },
  ],
})
