import api from './api'

export type AdminCustomer = {
  id: number
  name: string
  email: string
  created_at: string
  orders_count: number
  orders_sum_total: string | number | null
}

export type AdminCustomerDetail = AdminCustomer & {
  orders: {
    id: number
    status: string
    payment_status: string
    total: string | number
    created_at: string
  }[]
}

export type CustomerFilters = {
  search?: string
  page?: number
  per_page?: number
}

export type CustomerListMeta = {
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export const fetchAdminCustomers = async (
  filters: CustomerFilters = {}
): Promise<{ customers: AdminCustomer[]; meta: CustomerListMeta }> => {
  const response = await api.get('/admin/customers', { params: filters })
  return response.data
}

export const fetchAdminCustomer = async (id: number): Promise<AdminCustomerDetail> => {
  const response = await api.get(`/admin/customers/${id}`)
  return response.data.customer
}
