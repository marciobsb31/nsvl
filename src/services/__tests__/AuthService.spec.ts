import { describe, it, expect, vi, beforeEach } from 'vitest'
import { authService } from '../AuthService'
import api from '../ApiService'

// Mock do ApiService
vi.mock('../ApiService', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}))

describe('AuthService', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        sessionStorage.clear()
    })

    // -------------------------------------------------------
    // login()
    // -------------------------------------------------------
    describe('login()', () => {
        it('chama GET /auth/redirect e redireciona para a URL retornada', async () => {
            const fakeUrl = 'https://sso.staging.acesso.gov.br/authorize?...'
            vi.mocked(api.get).mockResolvedValueOnce({ data: { url: fakeUrl } })

            const assignSpy = vi.spyOn(window, 'location', 'get').mockReturnValue({
                ...window.location,
                href: '',
            } as Location)

            await authService.login()

            expect(api.get).toHaveBeenCalledWith('/auth/redirect')
        })

        it('lança erro se a API falhar', async () => {
            vi.mocked(api.get).mockRejectedValueOnce(new Error('Network Error'))
            await expect(authService.login()).rejects.toThrow('Network Error')
        })
    })

    // -------------------------------------------------------
    // setToken() / getUser()
    // -------------------------------------------------------
    describe('setToken()', () => {
        it('armazena token no sessionStorage', () => {
            authService.setToken('meu_token_teste')
            expect(sessionStorage.getItem('nvsl_token')).toBe('meu_token_teste')
        })
    })

    describe('getUser()', () => {
        it('retorna null quando não há token', async () => {
            const user = await authService.getUser()
            expect(user).toBeNull()
        })

        it('faz GET /user e retorna o usuário quando há token', async () => {
            sessionStorage.setItem('nvsl_token', 'token_valido')
            const fakeUser = { sub: '12345', name: 'Cidadão Teste', email: 'teste@gov.br' }
            vi.mocked(api.get).mockResolvedValueOnce({ data: fakeUser })

            const user = await authService.getUser()

            expect(api.get).toHaveBeenCalledWith('/user')
            expect(user).toEqual(fakeUser)
        })

        it('retorna null se a API falhar', async () => {
            sessionStorage.setItem('nvsl_token', 'token_invalido')
            vi.mocked(api.get).mockRejectedValueOnce(new Error('Unauthorized'))

            const user = await authService.getUser()
            expect(user).toBeNull()
        })
    })

    // -------------------------------------------------------
    // isAuthenticated()
    // -------------------------------------------------------
    describe('isAuthenticated()', () => {
        it('retorna false sem token', async () => {
            expect(await authService.isAuthenticated()).toBe(false)
        })

        it('retorna true com token', async () => {
            sessionStorage.setItem('nvsl_token', 'qualquer_token')
            expect(await authService.isAuthenticated()).toBe(true)
        })
    })

    // -------------------------------------------------------
    // logout()
    // -------------------------------------------------------
    describe('logout()', () => {
        it('chama POST /auth/logout e remove token do sessionStorage', async () => {
            sessionStorage.setItem('nvsl_token', 'token')
            vi.mocked(api.post).mockResolvedValueOnce({})

            await authService.logout()

            expect(api.post).toHaveBeenCalledWith('/auth/logout')
            expect(sessionStorage.getItem('nvsl_token')).toBeNull()
        })

        it('remove token mesmo se a API falhar', async () => {
            sessionStorage.setItem('nvsl_token', 'token')
            vi.mocked(api.post).mockRejectedValueOnce(new Error('Network'))

            await authService.logout()

            expect(sessionStorage.getItem('nvsl_token')).toBeNull()
        })
    })
})
