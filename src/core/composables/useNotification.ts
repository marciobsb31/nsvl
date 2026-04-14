import { ref } from 'vue'

export type NotificationType = 'success' | 'danger' | 'warning' | 'info'

export interface Notification {
  id: string
  type: NotificationType
  message: string
  title?: string
}

const notificationsState = ref<Notification[]>([])

function generateNotificationId(): string {
  const c = globalThis.crypto as Crypto | undefined
  if (c && typeof c.randomUUID === 'function') return c.randomUUID()
  return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`
}

function autoDismissMs(type: NotificationType, message: string): number {
  const trimmed = String(message ?? '').trim()
  const isLong = trimmed.length >= 140
  if (type === 'danger') return isLong ? 15000 : 12000
  if (type === 'warning') return isLong ? 12000 : 10000
  return isLong ? 10000 : 8000
}

/**
 * useNotification — Composable para exibição de notificações/alertas
 * Compatível com o componente br-message do GOV.BR DS
 */
export function useNotification() {
  function add(type: NotificationType, message: string, title?: string): void {
    const id = generateNotificationId()
    notificationsState.value.push({ id, type, message, title })
    // Remove automaticamente após alguns segundos
    setTimeout(() => remove(id), autoDismissMs(type, message))
  }

  function remove(id: string): void {
    notificationsState.value = notificationsState.value.filter((n) => n.id !== id)
  }

  const success = (message: string, title?: string) => add('success', message, title)
  const error = (message: string, title?: string) => add('danger', message, title)
  const warning = (message: string, title?: string) => add('warning', message, title)
  const info = (message: string, title?: string) => add('info', message, title)

  return { notifications: notificationsState, add, remove, success, error, warning, info }
}
