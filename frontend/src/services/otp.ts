import api from './api'

export const sendOrderOtp = async (phone: string, email: string | null, orderId: number): Promise<void> => {
  await api.post('/otp/send', { phone, email, order_id: orderId })
}

export const verifyOrderOtp = async (phone: string, otp: string): Promise<{
  success: boolean
  is_new_user: boolean
}> => {
  const res = await api.post('/otp/verify', { phone, otp })
  return res.data
}

export const sendLoginOtp = async (phone: string): Promise<void> => {
  await api.post('/otp/login/send', { phone })
}

export const verifyLoginOtp = async (phone: string, otp: string): Promise<{
  token: string
  user: { id: number; name: string; email: string | null; phone: string; has_password: boolean; role: string }
}> => {
  const res = await api.post('/otp/login/verify', { phone, otp })
  return res.data
}

export const setPassword = async (password: string, passwordConfirmation: string): Promise<void> => {
  await api.post('/otp/set-password', { password, password_confirmation: passwordConfirmation })
}
