import axios from 'axios'

// Em dev: usa /api (proxy do Vite redireciona ao backend). Em prod: usa URL completa.
const env = (window as any)._env_ ?? {}
const apiBaseUrl = env.VITE_API_BASE_URL
    || import.meta.env.VITE_API_BASE_URL           // fallback para dev (build local)
    || (import.meta.env.DEV ? '/api' : 'http://localhost:8081/api')

const api = axios.create({
    baseURL: apiBaseUrl,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
})

// Interceptador para anegar o Token Sanctum
api.interceptors.request.use((config) => {
    const token = sessionStorage.getItem('nvsl_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Interceptador de resposta: 401 → redireciona para login (com delay para exibir mensagem de erro)
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            console.warn('[API] 401 — redirecionando para login em 1.5s')
            sessionStorage.removeItem('nvsl_token')
            setTimeout(() => {
                window.location.href = '/login'
            }, 1500)
        }
        return Promise.reject(error)
    }
)

export default api
