import { UserManager, WebStorageStateStore, type User } from 'oidc-client-ts'
import type { GovBrUser, OidcConfig } from '@/types/auth'

/**
 * AuthService — Serviço de autenticação GOV.BR via OAuth2/OIDC (PKCE)
 *
 * Responsabilidades:
 *  - Iniciar fluxo de login (redireciona ao SSO GOV.BR)
 *  - Processar callback após autenticação
 *  - Obter usuário autenticado
 *  - Realizar logout
 *
 * Tokens são armazenados em sessionStorage (não localStorage)
 * por questões de segurança — são limpados ao fechar a aba.
 */

function buildOidcConfig(): OidcConfig {
    return {
        authority: import.meta.env.VITE_GOVBR_SSO_URL,
        clientId: import.meta.env.VITE_GOVBR_CLIENT_ID,
        redirectUri: import.meta.env.VITE_GOVBR_REDIRECT_URI,
        postLogoutRedirectUri: import.meta.env.VITE_GOVBR_POST_LOGOUT_REDIRECT_URI,
        scope: import.meta.env.VITE_GOVBR_SCOPES,
    }
}

class AuthService {
    private userManager: UserManager

    constructor() {
        const config = buildOidcConfig()

        this.userManager = new UserManager({
            authority: config.authority,
            client_id: config.clientId,
            redirect_uri: config.redirectUri,
            post_logout_redirect_uri: config.postLogoutRedirectUri,
            scope: config.scope,
            response_type: 'code',
            // PKCE é habilitado por padrão no oidc-client-ts
            // Tokens em sessionStorage — não persiste além da aba
            userStore: new WebStorageStateStore({ store: window.sessionStorage }),
            // Carrega dados do usuário a partir do access_token/id_token
            loadUserInfo: true,
        })
    }

    /**
     * Inicia o fluxo de autenticação — redireciona ao SSO GOV.BR
     */
    async login(): Promise<void> {
        await this.userManager.signinRedirect()
    }

    /**
     * Processa o callback após redirecionamento do SSO GOV.BR
     * Deve ser chamado na rota /callback
     */
    async handleCallback(): Promise<User> {
        return await this.userManager.signinRedirectCallback()
    }

    /**
     * Retorna o usuário autenticado ou null
     */
    async getUser(): Promise<GovBrUser | null> {
        const user = await this.userManager.getUser()
        if (!user || user.expired) return null

        return {
            sub: user.profile.sub,
            name: user.profile.name ?? '',
            email: user.profile.email,
            picture: user.profile.picture,
            amr: user.profile.amr as string[] | undefined,
        }
    }

    /**
     * Verifica se o usuário está autenticado (token válido e não expirado)
     */
    async isAuthenticated(): Promise<boolean> {
        const user = await this.userManager.getUser()
        return !!(user && !user.expired)
    }

    /**
     * Realiza logout — redireciona ao endpoint de logout do SSO GOV.BR
     */
    async logout(): Promise<void> {
        await this.userManager.signoutRedirect()
    }

    /**
     * Renova o access token silenciosamente (via iframe)
     */
    async renewToken(): Promise<User | null> {
        return await this.userManager.signinSilent()
    }
}

// Singleton — uma única instância para toda a aplicação
export const authService = new AuthService()
