import { ref } from 'vue'

export type NotificationType = 'success' | 'danger' | 'warning' | 'info'

export interface Notification {
    id: string
    type: NotificationType
    message: string
    title?: string
}

/**
 * useNotification — Composable para exibição de notificações/alertas
 * Compatível com o componente br-message do GOV.BR DS
 */
export function useNotification() {
    const notifications = ref<Notification[]>([])

    function add(type: NotificationType, message: string, title?: string): void {
        const id = crypto.randomUUID()
        notifications.value.push({ id, type, message, title })
        // Remove automaticamente após 5 segundos
        setTimeout(() => remove(id), 5000)
    }

    function remove(id: string): void {
        notifications.value = notifications.value.filter((n) => n.id !== id)
    }

    const success = (message: string, title?: string) => add('success', message, title)
    const error = (message: string, title?: string) => add('danger', message, title)
    const warning = (message: string, title?: string) => add('warning', message, title)
    const info = (message: string, title?: string) => add('info', message, title)

    return { notifications, add, remove, success, error, warning, info }
}
