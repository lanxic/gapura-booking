// ─── Activity Booking Domain (PRD v4.0) ──────────────────────────────────────

export type ActivityMedia = {
  id: number
  url: string
  type: 'image' | 'video'
  is_primary: boolean
}

export type ActivityAddon = {
  id: number
  name: string
  price: number
  unit: string
  max_qty: number
  is_active: boolean
}

export type Activity = {
  id: number
  name: string
  slug: string
  category: string
  description: string
  duration_minutes: number
  min_pax: number
  max_pax: number
  level: 'beginner' | 'intermediate' | 'advanced' | 'all'
  min_age: number | null
  base_price: number
  status: 'active' | 'inactive' | 'archived'
  meta: Record<string, unknown>
  media: ActivityMedia[]
  addons: ActivityAddon[]
}

export type ActivitySlot = {
  id: number
  date: string
  start_time: string
  end_time: string
  capacity: number
  booked_count: number
  price: number
  status: 'available' | 'full' | 'cancelled'
  remaining_capacity: number
}

export type Invoice = {
  invoice_code: string
  status: 'draft' | 'pending' | 'paid' | 'expired' | 'failed' | 'refunded'
  total_amount: number
  due_now: number
  due_later: number
  payment_plan: 'FULL' | 'DP30' | 'DP50'
  due_at: string
  paid_at: string | null
  payment_url: string | null
  booking_code: string | null
}

export type Booking = {
  booking_code: string
  status: 'confirmed' | 'attended' | 'cancelled' | 'no_show'
  pax: number
  guest_name: string
  guest_email: string
  guest_phone: string
  notes: string | null
  created_at: string
  activity: {
    id: number
    name: string
    slug: string
    category: string
    image: string | null
  } | null
  slot: {
    date: string
    start_time: string
    end_time: string
  } | null
  invoice: {
    invoice_code: string
    total_amount: number
    payment_plan: string
    paid_at: string | null
  } | null
  addons: { name: string; qty: number; price: number }[]
}

export type Offer = {
  id: number
  title: string
  slug: string
  image: string | null
  description: string | null
  start_date: string
  end_date: string
  discount_type: 'percent' | 'fixed'
  discount_value: number
  badge: string | null
  is_active: boolean
  activities: { id: number; name: string; slug: string }[]
}

export type PromoValidationResult = {
  valid: boolean
  code: string
  discount_type: 'percent' | 'fixed'
  discount_value: number
  discount_amount: number
}

// ─── Pagination ───────────────────────────────────────────────────────────────

export type PaginatedResponse<T> = {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: {
    first: string | null
    last: string | null
    prev: string | null
    next: string | null
  }
}

export type ApiResponse<T> = {
  data: T
  message?: string
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

export type AuthUser = {
  id: number
  name: string
  email: string
  role: 'customer'
  permissions: string[]
  avatarUrl: string | null
}
