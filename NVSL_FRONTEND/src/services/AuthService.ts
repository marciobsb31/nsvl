import api from '@/services/ApiService'

export interface AuthTokenResponse {
    token: string
    user: Record<string, unknown>
}

/**
 * AuthService — encapsula todas as chamadas de autenticação GOV.BR.
 *
 * Fluxo OAuth2/OIDC com PKCE:
 *   1. getRedirectUrl()  → chama GET /api/auth/url → recebe URL do GOV.BR
 *   2. (browser redireciona ao GOV.BR, usuário autentica)
 *   3. GOV.BR chama de volta GET /api/auth/redirect?code=X&state=Y (backend)
 *   4. Backend processa o callback e redireciona para FRONTEND_URL/login#govbr_login_code=ABC
 *   5. exchangeCode(code) → chama POST /api/auth/exchange → recebe { token, user }
 */
const AuthService = {
    /**
     * Solicita ao backend a URL de autenticação do GOV.BR.
     * O backend gera state/nonce/PKCE, armazena em cache e retorna a URL completa.
     */
    async getRedirectUrl(): Promise<string> {
        const { data } = await api.get<{ url?: string } | string>('/auth/url')
        const url = typeof data === 'string' ? data : data?.url
        if (!url) {
            throw new Error('URL de autenticação GOV.BR não disponível.')
        }
        return url
    },

    /**
     * Troca o login_code temporário (recebido no hash da URL) por um token Sanctum.
     * O código é de uso único e expira em 120 segundos (configurável via GOVBR_LOGIN_CODE_TTL_SECONDS).
     *
     * @param code  Valor de `govbr_login_code` extraído do hash da URL após o callback
     */
    async exchangeCode(code: string): Promise<AuthTokenResponse> {
        const { data } = await api.post<AuthTokenResponse>('/auth/exchange', { code })
        return data
    },

    /**
     * Encerra a sessão do usuário no backend (revoga o token Sanctum).
     * Não lança exceção em caso de falha — a sessão local é sempre limpa.
     */
    async logout(): Promise<void> {
        await api.post('/auth/logout')
    },
}

export default AuthService
