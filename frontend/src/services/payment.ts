import api from './api'

export type RazorpayOrderOptions = {
  key: string
  razorpay_order_id: string
  amount: number
  currency: string
  order_id: number
  name: string
  description: string
  prefill: { name: string; email: string; contact: string }
}

export type RazorpayPaymentResult = {
  razorpay_payment_id: string
  razorpay_order_id: string
  razorpay_signature: string
}

export class PaymentCancelledError extends Error {
  constructor() {
    super('Payment was cancelled.')
    this.name = 'PaymentCancelledError'
  }
}

export class PaymentFailedError extends Error {
  constructor(description: string) {
    super(description)
    this.name = 'PaymentFailedError'
  }
}

// Lazily inject the Razorpay checkout.js script (cached after first load)
const loadScript = (): Promise<void> =>
  new Promise((resolve, reject) => {
    if ((window as any).Razorpay) { resolve(); return }
    const s = document.createElement('script')
    s.src = 'https://checkout.razorpay.com/v1/checkout.js'
    s.onload = () => resolve()
    s.onerror = () => reject(new Error('Failed to load Razorpay checkout script.'))
    document.head.appendChild(s)
  })

export const createRazorpayOrder = async (orderId: number): Promise<RazorpayOrderOptions> => {
  const res = await api.post('/payments/razorpay/create-order', { order_id: orderId })
  return res.data
}

export const verifyRazorpayPayment = async (params: {
  order_id: number
  razorpay_payment_id: string
  razorpay_order_id: string
  razorpay_signature: string
}): Promise<void> => {
  await api.post('/payments/razorpay/verify', params)
}

export const openRazorpayModal = async (
  options: RazorpayOrderOptions,
): Promise<RazorpayPaymentResult> => {
  await loadScript()

  return new Promise((resolve, reject) => {
    const rzp = new (window as any).Razorpay({
      key: options.key,
      amount: options.amount,
      currency: options.currency,
      name: options.name,
      description: options.description,
      order_id: options.razorpay_order_id,
      prefill: options.prefill,
      theme: { color: '#1c1917' },
      handler: (response: RazorpayPaymentResult) => resolve(response),
      modal: {
        ondismiss: () => reject(new PaymentCancelledError()),
      },
    })

    rzp.on('payment.failed', (response: any) => {
      reject(new PaymentFailedError(response.error?.description ?? 'Payment failed.'))
    })

    rzp.open()
  })
}
