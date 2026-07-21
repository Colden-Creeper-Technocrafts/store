import api from './api'

export type SocialLink = {
  id: number
  name: string
  url: string
  icon: string
  sort_order: number
}

export type SocialLinkPayload = {
  name: string
  url: string
  icon: string
  sort_order?: number
}

export const SOCIAL_PLATFORMS = [
  { key: 'instagram', label: 'Instagram' },
  { key: 'facebook',  label: 'Facebook' },
  { key: 'twitter',   label: 'X (Twitter)' },
  { key: 'youtube',   label: 'YouTube' },
  { key: 'linkedin',  label: 'LinkedIn' },
  { key: 'whatsapp',  label: 'WhatsApp' },
  { key: 'tiktok',    label: 'TikTok' },
  { key: 'telegram',  label: 'Telegram' },
  { key: 'pinterest', label: 'Pinterest' },
  { key: 'snapchat',  label: 'Snapchat' },
  { key: 'link',      label: 'Other / Custom' },
]

export const fetchSocialLinks = async (): Promise<SocialLink[]> => {
  const res = await api.get('/admin/social-links')
  return res.data.links
}

export const createSocialLink = async (payload: SocialLinkPayload): Promise<SocialLink> => {
  const res = await api.post('/admin/social-links', payload)
  return res.data.link
}

export const updateSocialLink = async (id: number, payload: SocialLinkPayload): Promise<SocialLink> => {
  const res = await api.put(`/admin/social-links/${id}`, payload)
  return res.data.link
}

export const deleteSocialLink = async (id: number): Promise<void> => {
  await api.delete(`/admin/social-links/${id}`)
}
