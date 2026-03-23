/**
 * Tipos relacionados à autenticação GOV.BR (OIDC)
 */

export interface GovBrUser {
    /** ID do usuário no sistema */
    id?: number
    /** Identificador único do usuário (CPF hash) */
    sub: string
    /** Nome completo */
    name: string
    /** E-mail */
    email?: string
    /** URL da foto de perfil */
    picture?: string
    /** Papel no sistema */
    role?: string
    /** Nível de confiabilidade da conta GOV.BR */
    amr?: string[]
    /** Esfera de atuação (federal, estadual, municipal) */
    esfera_atuacao?: string
    /** UF de lotação */
    uf_lotacao?: string
    /** Município de lotação */
    municipio_lotacao?: string
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
