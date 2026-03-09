import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api',
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
