export type { AdminRole, AdminUser, Permission } from '../store/auth'

export type OrderStatus =
  | 'pending'
  | 'awaiting_payment'
  | 'dp_paid'
  | 'paid'
  | 'confirmed'
  | 'cancelled'
  | 'refunded'
  | 'expired'

export type TicketStatus = 'unused' | 'used' | 'expired' | 'cancelled'
export type CorrectionStatus = 'pending' | 'approved' | 'rejected'
export type CorrectionTarget = 'ticket' | 'payment' | 'order'
export type PaymentGateway = 'midtrans' | 'cash'
export type PricingRuleType = 'weekday' | 'weekend' | 'holiday'

export type Product = {
  id: string
  name: string
  slug: string
  description: string
  cloudinaryImageId: string
  cloudinaryImageUrl: string
  cloudinaryThumbnailId: string
  cloudinaryThumbnailUrl: string
  cloudinaryGalleryIds: string[]
  cloudinaryGalleryUrls: string[]
  isActive: boolean
  sortOrder: number
}

export type ProductVariant = {
  id: string
  productId: string
  label: string
  priceAdult: number
  priceChild: number
  minQty: number
  maxQty: number
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
}

export type Order = {
  id: string
  bookingCode: string
  userId: string | null
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
  notes: string | null
  expiresAt: string
  createdAt: string
}

export type CorrectionRequest = {
  id: string
  requestedBy: string
  targetType: CorrectionTarget
  targetId: string
  reason: string
  oldValue: Record<string, unknown>
  requestedValue: Record<string, unknown>
  status: CorrectionStatus
  reviewedBy: string | null
  reviewedAt: string | null
  reviewNotes: string | null
  createdAt: string
}

export type ActivityLog = {
  id: string
  userId: string
  role: string
  action: string
  subjectType: string
  subjectId: string
  oldValue: Record<string, unknown> | null
  newValue: Record<string, unknown> | null
  ipAddress: string
  userAgent: string
  createdAt: string
}

export type User = {
  id: string
  name: string
  email: string
  role: string
  isActive: boolean
  createdBy: string | null
  cloudinaryAvatarUrl: string | null
  createdAt: string
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
