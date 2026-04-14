import { vi } from 'vitest'

vi.mock('@govbr-ds/webcomponents-vue', () => {
  const stubComponent = {
    name: 'GovBrStub',
    template: '<div><slot /></div>',
  }

  const base = {
    BrButton: stubComponent,
    BrBreadcrumb: stubComponent,
  }

  return new Proxy(base, {
    get: (target, prop) => {
      if (prop in target) return (target as Record<PropertyKey, unknown>)[prop]
      return stubComponent
    },
  })
})
