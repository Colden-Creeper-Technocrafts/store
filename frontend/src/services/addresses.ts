import api from './api'

export type UserAddress = {
  id: number
  label: string | null
  name: string
  phone: string | null
  email: string | null
  address: string
  city: string
  state: string | null
  postal_code: string
  country: string
  is_default: boolean
  updated_at: string
}

export const fetchAddresses = async (): Promise<UserAddress[]> => {
  const res = await api.get('/addresses')
  return res.data.addresses ?? []
}

export const setDefaultAddress = async (id: number): Promise<void> => {
  await api.patch(`/addresses/${id}/default`, {})
}

export const deleteAddress = async (id: number): Promise<void> => {
  await api.delete(`/addresses/${id}`)
}
