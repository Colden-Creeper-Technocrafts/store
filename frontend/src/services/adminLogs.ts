import api from './api'

export type LogLine = {
  timestamp: string | null
  env: string | null
  level: string | null
  message: string
  raw: string
}

export const fetchLogFiles = async (): Promise<string[]> => {
  const res = await api.get('/admin/logs/files')
  return res.data.files
}

export const fetchLogContent = async (file: string): Promise<LogLine[]> => {
  const res = await api.get('/admin/logs/content', { params: { file } })
  return res.data.lines
}
