import api from './ApiService'
import type { GovBrUser } from '@/types/auth'

/**
 * AuthService — Serviço de autenticação NVSL integrado ao Backend Laravel
 *
 * Responsabilidades:
 *  - Iniciar fluxo de login via API do Backend
 *  - Obter usuário autenticado (da base do backend via Sanctum)
 *  - Realizar logout
 */

class AuthService {
    /**
     * Inicia o fluxo de autenticação — redireciona ao backend que por sua vez 
     * redireciona ao SSO GOV.BR
     */
    async login(): Promise<void> {
        try {
            const { data } = await api.get<{ url: string }>('/auth/redirect')
            window.location.href = data.url
        } catch (error) {
            console.error('Erro ao iniciar login:', error)
            throw error
        }
    }

    /**
     * Define o token de autenticação no storage
     */
    setToken(token: string): void {
        sessionStorage.setItem('nvsl_token', token)
    }

    /**
     * Retorna o usuário autenticado ou null
     */
    async getUser(): Promise<GovBrUser | null> {
        try {
            const token = sessionStorage.getItem('nvsl_token')
            if (!token) return null

            const { data } = await api.get<GovBrUser>('/user')
            return data
        } catch (error) {
            console.error('Erro ao obter usuário:', error)
            return null
        }
    }

    /**
     * Verifica se o usuário está autenticado
     */
    async isAuthenticated(): Promise<boolean> {
        return !!sessionStorage.getItem('nvsl_token')
    }

    /**
     * Realiza logout — revoga o token no backend e limpa storage local
     */
    async logout(): Promise<void> {
        try {
            await api.post('/auth/logout')
        } catch (error) {
            console.error('Erro ao deslogar:', error)
        } finally {
            sessionStorage.removeItem('nvsl_token')
            window.location.href = '/login'
        }
    }
}

// Singleton — uma única instância para toda a aplicação
export const authService = new AuthService()
