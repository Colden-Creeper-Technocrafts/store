// Store configuration management
export interface StoreConfig {
  businessType: string
  storeName: string
  storeDescription: string
  storeEmail: string
  storePhone: string
  currency: string
  timezone: string
  features: {
    reviews: boolean
    wishlist: boolean
    subscriptions: boolean
    loyalty: boolean
  }
}

export interface BusinessType {
  id: string
  name: string
  description: string
  icon: string
  colors: { primary: string; accent: string }
  categories: string[]
  attributes: string[]
}

// Get store config from localStorage or default
export const getStoreConfig = (): StoreConfig => {
  const saved = localStorage.getItem('storeConfig')
  if (saved) {
    return JSON.parse(saved)
  }
  return {
    businessType: '',
    storeName: 'My Store',
    storeDescription: '',
    storeEmail: '',
    storePhone: '',
    currency: 'USD',
    timezone: 'UTC',
    features: {
      reviews: true,
      wishlist: true,
      subscriptions: false,
      loyalty: false
    }
  }
}

// Save store config to localStorage
export const saveStoreConfig = (config: StoreConfig): void => {
  localStorage.setItem('storeConfig', JSON.stringify(config))
}

// Business type templates
export const BUSINESS_TYPES: BusinessType[] = [
  {
    id: 'jewelry',
    name: 'Jewelry Store',
    description: 'For jewelry, watches, and accessories',
    icon: '💎',
    colors: { primary: 'indigo', accent: 'amber' },
    categories: ['Rings', 'Necklaces', 'Bracelets', 'Earrings', 'Watches'],
    attributes: ['Material', 'Carat', 'Size', 'Color']
  },
  {
    id: 'supermarket',
    name: 'Supermarket',
    description: 'For grocery and general retail',
    icon: '🛒',
    colors: { primary: 'green', accent: 'emerald' },
    categories: ['Groceries', 'Dairy', 'Bakery', 'Produce', 'Frozen'],
    attributes: ['Weight', 'Expiry Date', 'Barcode', 'Organic']
  },
  {
    id: 'fashion',
    name: 'Fashion Boutique',
    description: 'For clothing and fashion items',
    icon: '👗',
    colors: { primary: 'rose', accent: 'pink' },
    categories: ['Men', 'Women', 'Kids', 'Accessories', 'Shoes'],
    attributes: ['Size', 'Color', 'Material', 'Style']
  },
  {
    id: 'electronics',
    name: 'Electronics Store',
    description: 'For gadgets and tech products',
    icon: '📱',
    colors: { primary: 'blue', accent: 'cyan' },
    categories: ['Phones', 'Laptops', 'Tablets', 'Accessories', 'Audio'],
    attributes: ['Brand', 'Warranty', 'Storage', 'RAM']
  },
  {
    id: 'pharmacy',
    name: 'Pharmacy',
    description: 'For medicines and health products',
    icon: '💊',
    colors: { primary: 'red', accent: 'orange' },
    categories: ['Medicine', 'Supplements', 'Vitamins', 'First Aid', 'Wellness'],
    attributes: ['Strength', 'Expiry Date', 'Prescription', 'Batch Number']
  },
  {
    id: 'books',
    name: 'Bookstore',
    description: 'For books and reading materials',
    icon: '📚',
    colors: { primary: 'amber', accent: 'orange' },
    categories: ['Fiction', 'Non-Fiction', 'Academic', 'Children', 'Comics'],
    attributes: ['Author', 'Publisher', 'ISBN', 'Edition']
  }
]

// Get business type by ID
export const getBusinessType = (id: string): BusinessType | undefined => {
  return BUSINESS_TYPES.find(type => type.id === id)
}
