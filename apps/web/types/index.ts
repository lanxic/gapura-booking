export type Product = {
  id: string
  name: string
  slug: string
  description: string
  location: string | null
  openingHours: string | null
  meetingPoint: string | null
  instantConfirmation: boolean
  highlights: string[]
  usageInstructions: string | null
  cancellationPolicy: string | null
  termsConditions: string | null
  cloudinaryImageUrl: string | null
  cloudinaryThumbnailUrl: string | null
  cloudinaryGalleryUrls: string[]
  isActive: boolean
  sortOrder: number
  variants: ProductVariant[]
  addons: Addon[]
}

export type ProductVariant = {
  id: string
  productId: string
  label: string
  description: string | null
  priceAdult: number
  priceChild: number
  minQty: number
  maxQty: number
  adultMinAge: number
  adultMaxAge: number
  childMinAge: number
  childMaxAge: number
  isActive: boolean
}

export type Addon = {
  id: string
  name: string
  description: string
  price: number
  maxQty: number
  isActive: boolean
}

export type AvailabilitySlot = {
  id: string
  productId: string
  date: string
  timeSlot: string | null
  totalQuota: number
  bookedQty: number
  isBlocked: boolean
  remaining: number
  status: 'available' | 'limited' | 'full' | 'blocked'
}

export type Order = {
  id: string
  bookingCode: string
  customerName: string
  customerEmail: string
  customerPhone: string
  paymentType: 'full' | 'down_payment'
  dpPercent: number | null
  dpAmount: number | null
  remainingAmount: number
  status: OrderStatus
  subtotal: number
  discount: number
  total: number
  expiresAt: string
  items: OrderItem[]
  payments: Payment[]
  tickets: Ticket[]
}

export type OrderStatus =
  | 'pending'
  | 'awaiting_payment'
  | 'dp_paid'
  | 'paid'
  | 'confirmed'
  | 'cancelled'
  | 'refunded'
  | 'expired'

export type OrderItem = {
  id: string
  orderId: string
  variantId: string
  slotId: string
  qtyAdult: number
  qtyChild: number
  unitPriceAdult: number
  unitPriceChild: number
  subtotal: number
}

export type Payment = {
  id: string
  orderId: string
  gateway: 'midtrans' | 'cash'
  snapToken: string | null
  refId: string | null
  paymentType: 'dp' | 'full' | 'remaining'
  amount: number
  status: 'pending' | 'success' | 'failed' | 'expired' | 'refunded'
  paidAt: string | null
}

export type Ticket = {
  id: string
  orderItemId: string
  qrCode: string
  cloudinaryPdfUrl: string
  status: 'unused' | 'used' | 'expired' | 'cancelled'
  usedAt: string | null
}

export type ApiResponse<T> = {
  data: T
  message?: string
}

export type PaginatedResponse<T> = {
  data: T[]
  meta: {
    currentPage: number
    lastPage: number
    perPage: number
    total: number
  }
}
