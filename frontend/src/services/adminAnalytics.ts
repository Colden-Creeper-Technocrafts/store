import api from './api'

export type RevenueKpi = {
  total: number
  period: number
  change_pct: number | null
}

export type OrdersKpi = {
  total: number
  period: number
  change_pct: number | null
}

export type CustomersKpi = {
  total: number
  new_period: number
}

export type AovKpi = {
  total: number
  period: number
}

export type DailyTrendPoint = {
  date: string
  revenue: number
  orders: number
}

export type RecentOrder = {
  id: number
  user_id: number | null
  user: { id: number; name: string; email: string } | null
  status: string
  payment_status: string
  total: number
  shipping_name: string
  created_at: string
}

export type TopProduct = {
  name: string
  units_sold: number
  revenue: number
}

export type LowStockProduct = {
  id: number
  name: string
  sku: string | null
  quantity: number
}

export type AnalyticsSummary = {
  kpis: {
    revenue: RevenueKpi
    orders: OrdersKpi
    customers: CustomersKpi
    aov: AovKpi
  }
  revenue_trend: DailyTrendPoint[]
  recent_orders: RecentOrder[]
  top_products: TopProduct[]
  order_status_breakdown: Record<string, number>
  low_stock: LowStockProduct[]
  out_of_stock_count: number
}

export const fetchAnalyticsSummary = async (): Promise<AnalyticsSummary> => {
  const response = await api.get('/admin/analytics/summary')
  return response.data
}
