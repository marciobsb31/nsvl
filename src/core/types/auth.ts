/**
 * Tipos relacionados à autenticação GOV.BR (OIDC)
 */

export interface GovBrUser {
    /** Identificador único do usuário (CPF hash) */
    sub: string
    /** Nome completo */
    name: string
    /** E-mail */
    email?: string
    /** URL da foto de perfil */
    picture?: string
    /** Nível de confiabilidade da conta GOV.BR */
    amr?: string[]
}

export interface AuthState {
    user: GovBrUser | null
    isAuthenticated: boolean
    isLoading: boolean
    error: string | null
}

export interface OidcConfig {
    authority: string
    clientId: string
    redirectUri: string
    postLogoutRedirectUri: string
    scope: string
}
