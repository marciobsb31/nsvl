import axios from 'axios'

// Em dev: usa /api (proxy do Vite redireciona ao backend). Em prod: usa URL completa.
const apiBaseUrl = import.meta.env.VITE_API_BASE_URL
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

// Interceptador de resposta para tratar expiração de sessão
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            sessionStorage.removeItem('nvsl_token')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

export default api
