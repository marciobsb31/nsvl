import { describe, it, expect, vi } from 'vitest'
import { solicitacaoRoutes } from '../solicitacaoCadastroRoutes'

describe('solicitacaoCadastroRoutes', () => {
  it('define rota /solicitacao-cadastro', () => {
    expect(solicitacaoRoutes).toHaveLength(1)
    expect(solicitacaoRoutes[0]?.path).toBe('/solicitacao-cadastro')
    expect(solicitacaoRoutes[0]?.name).toBe('solicitacao-cadastro')
    expect(solicitacaoRoutes[0]?.meta).toEqual(
      expect.objectContaining({ title: 'Solicitação de cadastro — NVSL', public: true }),
    )
  })

  it('beforeEnter redireciona para login quando não há nome/cpf na query', () => {
    const next = vi.fn()
    const route = solicitacaoRoutes[0]!

    const guard = route.beforeEnter as unknown as (
      to: any,
      from: any,
      next: (v?: any) => void,
    ) => void

    guard({ query: {} } as any, {} as any, next)

    expect(next).toHaveBeenCalledWith({ name: 'login' })
  })

  it('beforeEnter permite entrada quando há nome e cpf na query', () => {
    const next = vi.fn()
    const route = solicitacaoRoutes[0]!

    const guard = route.beforeEnter as unknown as (
      to: any,
      from: any,
      next: (v?: any) => void,
    ) => void

    guard({ query: { nome: 'Maria', cpf: '123' } } as any, {} as any, next)

    expect(next).toHaveBeenCalledWith()
  })
})
