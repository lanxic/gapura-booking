// ─── Activity domain ─────────────────────────────────────────────────────────

export type ActivityCategory = "indoor" | "outdoor";
export type ActivityLevel = "beginner" | "intermediate" | "advanced";
export type ActivityStatus = "active" | "inactive" | "archived";

export interface Activity {
  id: number;
  name: string;
  slug: string;
  category: ActivityCategory;
  description: string;
  duration_minutes: number;
  min_pax: number;
  max_pax: number;
  level: ActivityLevel;
  min_age?: number;
  base_price: number;
  status: ActivityStatus;
  meta?: Record<string, unknown>;
}

export interface ActivitySlot {
  id: number;
  activity_id: number;
  schedule_id?: number;
  date: string; // YYYY-MM-DD
  start_time: string; // HH:MM
  end_time: string;
  capacity: number;
  booked_count: number;
  price: number;
  status: "available" | "full" | "blocked" | "cancelled";
}

// ─── Booking domain ───────────────────────────────────────────────────────────

export type BookingStatus =
  | "pending"
  | "confirmed"
  | "attended"
  | "cancelled"
  | "no_show";

export interface Booking {
  id: number;
  booking_code: string; // ACT-YYYYMMDD-XXXXX
  invoice_id: number;
  slot_id: number;
  customer_id?: number;
  guest_name: string;
  guest_email: string;
  guest_phone: string;
  pax_count: number;
  status: BookingStatus;
  notes?: string;
  total_amount: number;
  paid_amount: number;
  payment_status: "unpaid" | "partial" | "paid" | "refunded";
  qr_code_token: string;
  confirmed_at?: string;
  created_at: string;
}

// ─── Invoice domain (Section 13) ─────────────────────────────────────────────

export type InvoiceStatus =
  | "draft"
  | "pending"
  | "paid"
  | "expired"
  | "failed"
  | "refunded";

export interface Invoice {
  id: number;
  invoice_code: string; // INV-YYYYMMDD-XXXXX
  customer_id?: number;
  guest_name: string;
  guest_email: string;
  guest_phone: string;
  checkout_slot_id: number;
  pax_count: number;
  items: InvoiceItem[];
  subtotal: number;
  discount_amount: number;
  total_amount: number;
  status: InvoiceStatus;
  pdf_path?: string;
  due_at: string;
  paid_at?: string;
  gateway?: string;
  gateway_order_id?: string;
  created_at: string;
}

export interface InvoiceItem {
  type: "activity" | "addon";
  name: string;
  unit_price: number;
  quantity: number;
  subtotal: number;
}

// ─── Payment domain ───────────────────────────────────────────────────────────

export type PaymentAttemptStatus =
  | "pending"
  | "success"
  | "failure"
  | "expired"
  | "challenge";

export interface PaymentAttempt {
  id: number;
  attempt_code: string; // PAY-{invoice_code}-001
  invoice_id: number;
  gateway: string;
  gateway_tx_id?: string;
  payment_method: string;
  amount: number;
  status: PaymentAttemptStatus;
  attempted_at: string;
  settled_at?: string;
}

export type PaymentPlanCode = "FULL" | "DP30" | "DP50" | "DP70";

export interface PaymentPlan {
  id: number;
  code: PaymentPlanCode;
  label: string;
  percentage: number;
  min_amount: number;
  deadline_hours: number;
  is_active: boolean;
}

export interface PaymentGateway {
  id: number;
  name: "midtrans" | "doku";
  is_active: boolean;
  environment: "sandbox" | "production";
}

// ─── Offer / Promo domain ─────────────────────────────────────────────────────

export type DiscountType = "flat" | "percent";

export interface Offer {
  id: number;
  title: string;
  slug: string;
  description: string;
  image?: string;
  start_date: string;
  end_date: string;
  discount_type: DiscountType;
  discount_value: number;
  is_active: boolean;
}

export interface PromoCode {
  id: number;
  code: string;
  offer_id?: number;
  discount_type: DiscountType;
  discount_value: number;
  min_amount: number;
  max_uses: number;
  used_count: number;
  expired_at: string;
}

// ─── Customer domain ──────────────────────────────────────────────────────────

export interface Customer {
  id: number;
  name: string;
  email: string;
  phone: string;
  google_id?: string;
  preferences?: Record<string, unknown>;
  created_at: string;
}

// ─── API response wrapper ─────────────────────────────────────────────────────

export interface ApiResponse<T> {
  data: T;
  message?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
