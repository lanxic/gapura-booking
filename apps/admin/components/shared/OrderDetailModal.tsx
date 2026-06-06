'use client'

import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah, formatDate } from '@/lib/utils'
import {
  X, User, Mail, Phone, Calendar, Receipt, CreditCard,
  Package, Tag, FileText, Clock, Loader2,
} from 'lucide-react'

// ── Label maps ────────────────────────────────────────────────────────────────

const ORDER_STATUS_LABEL: Record<string, string> = {
  pending:          'Menunggu',
  awaiting_payment: 'Menunggu Pembayaran',
  dp_paid:          'DP Terbayar',
  paid:             'Lunas',
  confirmed:        'Dikonfirmasi',
  cancelled:        'Dibatalkan',
  refunded:         'Dikembalikan',
  expired:          'Kedaluwarsa',
}

const ORDER_STATUS_CLS: Record<string, string> = {
  pending:          'bg-amber-50 text-amber-700 border-amber-200',
  awaiting_payment: 'bg-orange-50 text-orange-700 border-orange-200',
  dp_paid:          'bg-blue-50 text-blue-700 border-blue-200',
  paid:             'bg-emerald-50 text-emerald-700 border-emerald-200',
  confirmed:        'bg-green-50 text-green-700 border-green-200',
  cancelled:        'bg-red-50 text-red-600 border-red-200',
  refunded:         'bg-gray-50 text-gray-600 border-gray-200',
  expired:          'bg-slate-50 text-slate-500 border-slate-200',
}

const PAYMENT_STATUS_LABEL: Record<string, string> = {
  pending:  'Menunggu',
  success:  'Berhasil',
  failed:   'Gagal',
  expired:  'Kedaluwarsa',
  refunded: 'Dikembalikan',
}

const PAYMENT_STATUS_CLS: Record<string, string> = {
  pending:  'bg-amber-50 text-amber-700',
  success:  'bg-emerald-50 text-emerald-700',
  failed:   'bg-red-50 text-red-600',
  expired:  'bg-slate-50 text-slate-500',
  refunded: 'bg-gray-50 text-gray-500',
}

const PAYMENT_TYPE_LABEL: Record<string, string> = {
  full:      'Pembayaran Penuh',
  dp:        'Uang Muka (DP)',
  remaining: 'Pelunasan',
}

const GATEWAY_LABEL: Record<string, string> = {
  midtrans: 'Midtrans',
  cash:     'Tunai',
}

// ── Section wrapper ────────────────────────────────────────────────────────────

function Section({ title, icon: Icon, children }: {
  title: string
  icon: React.ElementType
  children: React.ReactNode
}) {
  return (
    <div className="space-y-2">
      <div className="flex items-center gap-1.5 pb-1 border-b border-border">
        <Icon size={13} className="text-muted-foreground" />
        <h3 className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">{title}</h3>
      </div>
      {children}
    </div>
  )
}

function Row({ label, value, mono }: { label: string; value: React.ReactNode; mono?: boolean }) {
  return (
    <div className="flex justify-between items-start gap-3 text-sm">
      <span className="text-muted-foreground shrink-0">{label}</span>
      <span className={cn('text-right text-foreground font-medium break-all', mono && 'font-mono')}>{value ?? '—'}</span>
    </div>
  )
}

// ── Modal ─────────────────────────────────────────────────────────────────────

export function OrderDetailModal({ orderId, token, onClose }: {
  orderId: number | null
  token: string
  onClose: () => void
}) {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-order-detail', orderId],
    queryFn:  () => api.get<any>(`/admin/orders/${orderId}`, { token }),
    enabled:  orderId !== null,
  })

  const order = data?.data

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [onClose])

  if (orderId === null) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      onClick={e => { if (e.target === e.currentTarget) onClose() }}
    >
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" />

      {/* Panel */}
      <div className="relative z-10 w-full max-w-xl max-h-[90dvh] overflow-y-auto bg-card border border-border rounded-2xl shadow-2xl flex flex-col">

        {/* Sticky header */}
        <div className="sticky top-0 z-10 flex items-center justify-between gap-3 px-5 py-4 bg-card border-b border-border rounded-t-2xl">
          {isLoading || !order ? (
            <div className="h-5 w-40 rounded bg-muted animate-pulse" />
          ) : (
            <div className="flex items-center gap-2 min-w-0">
              <span className="font-mono text-base font-bold text-foreground tracking-wide truncate">
                {order.booking_code}
              </span>
              <span className={cn(
                'text-[11px] font-semibold px-2 py-0.5 rounded-full border shrink-0',
                ORDER_STATUS_CLS[order.status] ?? 'bg-gray-50 text-gray-600 border-gray-200',
              )}>
                {ORDER_STATUS_LABEL[order.status] ?? order.status}
              </span>
            </div>
          )}
          <button
            onClick={onClose}
            className="shrink-0 p-1.5 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
          >
            <X size={16} />
          </button>
        </div>

        {/* Body */}
        <div className="p-5 space-y-5">
          {isLoading && (
            <div className="flex items-center justify-center py-16 gap-2 text-muted-foreground">
              <Loader2 size={18} className="animate-spin" />
              <span className="text-sm">Memuat detail pesanan…</span>
            </div>
          )}

          {!isLoading && order && (
            <>
              {/* ─ Customer ─ */}
              <Section title="Data Pelanggan" icon={User}>
                <Row label="Nama"   value={order.customer_name} />
                <Row label="Email"  value={order.customer_email} />
                <Row label="Telepon" value={order.customer_phone} />
              </Section>

              {/* ─ Items ─ */}
              <Section title="Item Pesanan" icon={Package}>
                {order.items?.map((item: any, i: number) => (
                  <div key={i} className="rounded-xl border border-border bg-muted/30 p-3 space-y-1.5">
                    <p className="text-sm font-semibold text-foreground">
                      {item.variant?.product?.name ?? '—'}
                    </p>
                    {item.variant?.label && (
                      <p className="text-xs text-muted-foreground">Varian: {item.variant.label}</p>
                    )}
                    {item.availability_slot && (
                      <p className="text-xs text-muted-foreground">
                        Tanggal: {formatDate(item.availability_slot.date)}
                        {item.availability_slot.time_slot && ` — ${item.availability_slot.time_slot}`}
                      </p>
                    )}
                    <div className="flex items-center gap-4 text-xs text-muted-foreground flex-wrap">
                      {item.qty_adult > 0 && (
                        <span>Dewasa: {item.qty_adult} × {formatRupiah(item.unit_price_adult)}</span>
                      )}
                      {item.qty_child > 0 && (
                        <span>Anak: {item.qty_child} × {formatRupiah(item.unit_price_child)}</span>
                      )}
                    </div>
                    <div className="flex justify-between items-center pt-1 border-t border-border/60">
                      <span className="text-xs text-muted-foreground">Subtotal item</span>
                      <span className="text-sm font-semibold text-foreground">{formatRupiah(item.subtotal)}</span>
                    </div>
                  </div>
                ))}
              </Section>

              {/* ─ Financial summary ─ */}
              <Section title="Ringkasan Pembayaran" icon={CreditCard}>
                <Row label="Subtotal" value={formatRupiah(order.subtotal)} />
                {order.discount > 0 && (
                  <Row label="Diskon" value={<span className="text-emerald-600">− {formatRupiah(order.discount)}</span>} />
                )}
                <div className="flex justify-between items-center text-sm font-bold border-t border-border pt-2 mt-1">
                  <span>Total</span>
                  <span>{formatRupiah(order.total)}</span>
                </div>
                {order.payment_type === 'down_payment' && (
                  <div className="rounded-xl border border-blue-200 bg-blue-50 p-3 space-y-1 mt-1">
                    <Row label={`DP (${order.dp_percent}%)`} value={<span className="text-blue-700">{formatRupiah(order.dp_amount)}</span>} />
                    <Row label="Sisa pelunasan" value={formatRupiah(order.remaining_amount)} />
                  </div>
                )}
                {order.vouchers?.length > 0 && (
                  <div className="pt-1 space-y-1">
                    {order.vouchers.map((v: any) => (
                      <div key={v.id} className="flex items-center justify-between text-xs text-emerald-700">
                        <span className="flex items-center gap-1"><Tag size={10} /> {v.code}</span>
                        <span>− {formatRupiah(v.pivot?.discount_amount ?? 0)}</span>
                      </div>
                    ))}
                  </div>
                )}
              </Section>

              {/* ─ Payment history ─ */}
              {order.payments?.length > 0 && (
                <Section title="Riwayat Pembayaran" icon={Receipt}>
                  <div className="space-y-2">
                    {order.payments.map((p: any) => (
                      <div key={p.id} className="rounded-xl border border-border bg-muted/20 p-3 space-y-1.5">
                        <div className="flex items-center justify-between gap-2">
                          <span className="font-mono text-[11px] text-muted-foreground truncate">
                            {p.invoice_number ?? `#${p.id}`}
                          </span>
                          <span className={cn(
                            'text-[10px] font-semibold px-2 py-0.5 rounded-full shrink-0',
                            PAYMENT_STATUS_CLS[p.status] ?? 'bg-gray-50 text-gray-500',
                          )}>
                            {PAYMENT_STATUS_LABEL[p.status] ?? p.status}
                          </span>
                        </div>
                        <div className="flex items-center justify-between text-xs text-muted-foreground gap-2">
                          <span>{PAYMENT_TYPE_LABEL[p.payment_type] ?? p.payment_type} · {GATEWAY_LABEL[p.gateway] ?? p.gateway}</span>
                          <span className="font-semibold text-foreground">{formatRupiah(p.amount)}</span>
                        </div>
                        {p.paid_at && (
                          <p className="text-[10px] text-muted-foreground">
                            Dibayar: {formatDate(p.paid_at)}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>
                </Section>
              )}

              {/* ─ Notes ─ */}
              {order.notes && (
                <Section title="Catatan" icon={FileText}>
                  <p className="text-sm text-foreground">{order.notes}</p>
                </Section>
              )}

              {/* ─ Meta ─ */}
              <Section title="Info Pesanan" icon={Clock}>
                <Row label="Dibuat"      value={formatDate(order.created_at)} />
                {order.expires_at && (
                  <Row label="Batas bayar" value={formatDate(order.expires_at)} />
                )}
              </Section>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
