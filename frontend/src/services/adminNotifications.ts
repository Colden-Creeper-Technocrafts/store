import api from './api'

export type NotificationEvents = {
  order_placed: boolean
  status_changed: boolean
  return_updated: boolean
}

export type EmailConfig = {
  enabled: boolean
  events: NotificationEvents
}

export type SmsConfig = {
  enabled: boolean
  account_sid: string
  auth_token: string
  auth_token_masked?: string
  from_number: string
  events: NotificationEvents
}

export type WhatsAppConfig = {
  enabled: boolean
  from_number: string
  events: NotificationEvents
}

export type NotificationConfig = {
  email: EmailConfig
  sms: SmsConfig
  whatsapp: WhatsAppConfig
}

export type NotificationLog = {
  id: number
  order_id: number | null
  channel: 'email' | 'sms' | 'whatsapp'
  event: string
  recipient: string
  status: 'sent' | 'failed'
  error_message: string | null
  created_at: string
}

export type LogsMeta = {
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export async function fetchNotificationSettings(): Promise<NotificationConfig> {
  const res = await api.get('/admin/notifications/settings')
  return res.data.config
}

export async function updateNotificationSettings(
  config: Partial<NotificationConfig>
): Promise<NotificationConfig> {
  const res = await api.put('/admin/notifications/settings', config)
  return res.data.config
}

export async function fetchNotificationLogs(params?: {
  channel?: string
  event?: string
  status?: string
  per_page?: number
  page?: number
}): Promise<{ logs: NotificationLog[]; meta: LogsMeta }> {
  const res = await api.get('/admin/notifications/logs', { params })
  return res.data
}
