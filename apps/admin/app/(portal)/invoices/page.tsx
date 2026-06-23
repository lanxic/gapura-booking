'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import {
  FileText, Search, Download, X, Eye,
  User, Mail, CreditCard, Calendar, Hash,
  CheckCircle, Clock, XCircle, AlertCircle,
} from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'

// ── Types ─────────────────────────────────────────────────────────────────────

type InvoiceItem = {
  type: string
  name: string
  unit_price: number
  quantity: number
  subtotal: number
}

type PaymentAttempt = {
  id: number
  gateway: string
  status: string
  amount: number
  created_at: string
}

type Invoice = {
  id: number
  invoice_code: string
  guest_name: string
  guest_email: string
  guest_phone: string | null
  pax_count: number
  items: InvoiceItem[]
  subtotal: number
  discount_amount: number
  total_amount: number
  payment_plan: string
  due_now: number
  due_later: number
  status: 'pending' | 'paid' | 'failed' | 'expired' | 'cancelled'
  gateway: string | null
  gateway_order_id: string | null
  paid_at: string | null
  due_at: string | null
  created_at: string
  slot?: { date: string; start_time: string; activity?: { name: string } }
  customer?: { id: number; name: string; email: string } | null
  booking?: { id: number; booking_code: string; status: string } | null
  payment_attempts?: PaymentAttempt[]
  promo_code?: { code: string; discount_type: string; discount_value: number } | null
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatRp(n: number) { return 'Rp ' + n.toLocaleString('id-ID') }
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}
function formatDateTime(iso: string) {
  return new Date(iso).toLocaleString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

const STATUS_BADGE: Record<string, { cls: string; label: string; icon: React.ElementType }> = {
  pending:   { cls: 'bg-amber-50 text-amber-700',   label: 'Pending',   icon: Clock },
  paid:      { cls: 'bg-emerald-50 text-emerald-700', label: 'Paid',    icon: CheckCircle },
  failed:    { cls: 'bg-red-50 text-red-600',        label: 'Failed',   icon: XCircle },
  expired:   { cls: 'bg-gray-100 text-gray-500',     label: 'Expired',  icon: AlertCircle },
  cancelled: { cls: 'bg-red-50 text-red-600',        label: 'Cancelled', icon: XCircle },
}

const ATTEMPT_STATUS_BADGE: Record<string, string> = {
  pending:   'bg-amber-50 text-amber-700',
  success:   'bg-emerald-50 text-emerald-700',
  failed:    'bg-red-50 text-red-600',
  cancelled: 'bg-gray-100 text-gray-500',
}

const GATEWAY_LABELS: Record<string, string> = {
  midtrans:      'Midtrans',
  doku:          'DOKU',
  cash:          'Tunai',
  bank_transfer: 'Transfer Bank',
  manual:        'Manual',
}

function StatusBadge({ status }: { status: string }) {
  const s = STATUS_BADGE[status] ?? { cls: 'bg-gray-100 text-gray-600', label: status, icon: AlertCircle }
  const Icon = s.icon
  return (
    <span className={cn('inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', s.cls)}>
      <Icon size={11} /> {s.label}
    </span>
  )
}

function InfoRow({ icon: Icon, label, value }: { icon: React.ElementType; label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-start gap-3">
      <Icon size={14} className="text-muted-foreground mt-0.5 shrink-0" />
      <div className="min-w-0">
        <p className="text-[11px] text-muted-foreground">{label}</p>
        <p className="text-sm font-medium text-foreground">{value}</p>
      </div>
    </div>
  )
}

// ── Detail Modal ──────────────────────────────────────────────────────────────

function InvoiceDetailModal({ code, token, onClose }: {
  code: string
  token: string
  onClose: () => void
}) {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-invoice-detail', code],
    queryFn: () => api.get<{ data: Invoice }>(`/admin/invoices/${code}`, { token }),
  })

  const inv = data?.data

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-2xl rounded-2xl border border-border bg-card shadow-2xl flex flex-col max-h-[90vh]">

        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
          <div>
            <h2 className="text-base font-semibold">Detail Invoice</h2>
            {inv && <p className="text-xs text-muted-foreground font-mono mt-0.5">{inv.invoice_code}</p>}
          </div>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X size={18} />
          </button>
        </div>

        {isLoading || !inv ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground text-sm">Memuat...</div>
        ) : (
          <div className="flex-1 overflow-y-auto">

            {/* Status bar */}
            <div className="flex items-center justify-between px-6 py-4 border-b border-border bg-muted/20">
              <StatusBadge status={inv.status} />
              {inv.booking && (
                <span className="text-xs text-muted-foreground">
                  Booking: <span className="font-mono font-bold text-foreground">{inv.booking.booking_code}</span>
                </span>
              )}
            </div>

            {/* Tamu & Aktivitas */}
            <div className="px-6 py-5 border-b border-border grid grid-cols-2 gap-4">
              <InfoRow icon={User}     label="Nama Tamu"  value={inv.guest_name} />
              <InfoRow icon={Mail}     label="Email"      value={inv.guest_email} />
              <InfoRow icon={Hash}     label="No. HP"     value={inv.guest_phone ?? '—'} />
              <InfoRow icon={Calendar} label="Aktivitas"  value={inv.slot?.activity?.name ?? '—'} />
              <InfoRow icon={Calendar} label="Tanggal Slot" value={
                inv.slot?.date
                  ? `${formatDate(inv.slot.date)}${inv.slot.start_time ? ' · ' + inv.slot.start_time.slice(0, 5) : ''}`
                  : '—'
              } />
              <InfoRow icon={User}     label="Jumlah Pax" value={`${inv.pax_count} orang`} />
            </div>

            {/* Items Breakdown */}
            <div className="px-6 py-5 border-b border-border">
              <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Rincian Pesanan</p>
              <div className="space-y-2">
                {(inv.items ?? []).map((item, i) => (
                  <div key={i} className="flex justify-between text-sm">
                    <span className="text-foreground">
                      {item.name}
                      {item.quantity > 1 && <span className="text-muted-foreground ml-1">×{item.quantity}</span>}
                    </span>
                    <span className="font-medium">{formatRp(item.subtotal)}</span>
                  </div>
                ))}
              </div>
              <div className="mt-3 pt-3 border-t border-border space-y-1.5 text-sm">
                <div className="flex justify-between text-muted-foreground">
                  <span>Subtotal</span>
                  <span>{formatRp(inv.subtotal)}</span>
                </div>
                {inv.discount_amount > 0 && (
                  <div className="flex justify-between text-emerald-600">
                    <span>
                      Diskon{inv.promo_code ? ` (${inv.promo_code.code})` : ''}
                    </span>
                    <span>−{formatRp(inv.discount_amount)}</span>
                  </div>
                )}
                <div className="flex justify-between font-bold text-foreground text-base pt-1">
                  <span>Total</span>
                  <span>{formatRp(inv.total_amount)}</span>
                </div>
                {inv.payment_plan !== 'FULL' && (
                  <div className="flex justify-between text-xs text-muted-foreground pt-1">
                    <span>Dibayar sekarang ({inv.payment_plan})</span>
                    <span>{formatRp(inv.due_now)}</span>
                  </div>
                )}
              </div>
            </div>

            {/* Gateway Info (PRD Section 4.4.1a) */}
            <div className="px-6 py-5 border-b border-border">
              <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">Informasi Pembayaran</p>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div>
                  <p className="text-[11px] text-muted-foreground">Gateway</p>
                  <p className="font-medium">{inv.gateway ? (GATEWAY_LABELS[inv.gateway] ?? inv.gateway) : '—'}</p>
                </div>
                <div>
                  <p className="text-[11px] text-muted-foreground">Gateway Order ID</p>
                  <p className="font-mono text-xs">{inv.gateway_order_id ?? '—'}</p>
                </div>
                <div>
                  <p className="text-[11px] text-muted-foreground">Dibuat</p>
                  <p className="font-medium">{formatDateTime(inv.created_at)}</p>
                </div>
                <div>
                  <p className="text-[11px] text-muted-foreground">Batas Bayar</p>
                  <p className="font-medium">{inv.due_at ? formatDateTime(inv.due_at) : '—'}</p>
                </div>
                {inv.paid_at && (
                  <div>
                    <p className="text-[11px] text-muted-foreground">Paid At</p>
                    <p className="font-medium text-emerald-600">{formatDateTime(inv.paid_at)}</p>
                  </div>
                )}
              </div>
            </div>

            {/* Payment Attempts */}
            {inv.payment_attempts && inv.payment_attempts.length > 0 && (
              <div className="px-6 py-5">
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                  Riwayat Percobaan Pembayaran
                </p>
                <div className="space-y-2">
                  {inv.payment_attempts.map(attempt => (
                    <div key={attempt.id} className="flex items-center gap-3 p-3 rounded-lg border border-border bg-muted/20">
                      <CreditCard size={14} className="text-muted-foreground shrink-0" />
                      <div className="flex-1 min-w-0">
                        <p className="text-xs font-medium text-foreground">
                          {GATEWAY_LABELS[attempt.gateway] ?? attempt.gateway}
                        </p>
                        <p className="text-xs text-muted-foreground">{formatDateTime(attempt.created_at)}</p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-xs font-semibold">{formatRp(attempt.amount)}</p>
                        <span className={cn('text-[10px] font-medium px-1.5 py-0.5 rounded-full', ATTEMPT_STATUS_BADGE[attempt.status] ?? 'bg-gray-100 text-gray-500')}>
                          {attempt.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* Footer */}
        <div className="flex justify-end px-6 py-4 border-t border-border shrink-0">
          <button
            onClick={onClose}
            className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function InvoicesPage() {
  const token = useAdminAuthStore(s => s.token)!

  const [search,  setSearch]  = useState('')
  const [status,  setStatus]  = useState('')
  const [gateway, setGateway] = useState('')
  const [date,    setDate]    = useState('')
  const [page,    setPage]    = useState(1)
  const [selectedCode, setSelectedCode] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-invoices', { search, status, gateway, date, page }],
    queryFn: () => {
      const params = new URLSearchParams()
      if (search)  params.set('search', search)
      if (status)  params.set('status', status)
      if (gateway) params.set('gateway', gateway)
      if (date)    params.set('date', date)
      params.set('page', String(page))
      return api.get<{ data: Invoice[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/invoices?${params}`, { token }
      )
    },
  })

  const handleExport = () => {
    const params = new URLSearchParams()
    if (status)  params.set('status', status)
    if (gateway) params.set('gateway', gateway)
    if (date)    params.set('date', date)
    window.open(`${process.env.NEXT_PUBLIC_ADMIN_API_URL}/admin/invoices/export?${params}`)
  }

  const invoices = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <PageHeader
        icon={FileText}
        title="Invoice"
        description="Daftar semua invoice transaksi pelanggan"
        action={
          <button
            onClick={handleExport}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors"
          >
            <Download size={15} /> Export CSV
          </button>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-52">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari kode invoice / nama / email..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <select
          value={status}
          onChange={e => { setStatus(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="failed">Failed</option>
          <option value="expired">Expired</option>
          <option value="cancelled">Cancelled</option>
        </select>
        <select
          value={gateway}
          onChange={e => { setGateway(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Gateway</option>
          <option value="midtrans">Midtrans</option>
          <option value="doku">DOKU</option>
          <option value="cash">Tunai</option>
          <option value="bank_transfer">Transfer Bank</option>
        </select>
        <input
          type="date"
          value={date}
          onChange={e => { setDate(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      {/* Table */}
      <TableCard
        columns={['Kode Invoice', 'Tamu', 'Aktivitas', 'Total', 'Status', 'Gateway', 'Dibuat', '']}
        isLoading={isLoading}
        isEmpty={invoices.length === 0}
        emptyMessage="Belum ada invoice."
      >
        {invoices.map(inv => (
          <tr key={inv.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
            <td className="px-4 py-3">
              <span className="font-mono text-xs font-bold text-foreground">{inv.invoice_code}</span>
            </td>
            <td className="px-4 py-3">
              <p className="font-medium text-foreground text-sm">{inv.guest_name}</p>
              <p className="text-xs text-muted-foreground">{inv.guest_email}</p>
            </td>
            <td className="px-4 py-3 text-sm text-muted-foreground hidden md:table-cell">
              <p>{inv.slot?.activity?.name ?? '—'}</p>
              {inv.slot?.date && (
                <p className="text-xs">{formatDate(inv.slot.date)}</p>
              )}
            </td>
            <td className="px-4 py-3 text-sm font-semibold text-foreground whitespace-nowrap">
              {formatRp(inv.total_amount)}
            </td>
            <td className="px-4 py-3">
              <StatusBadge status={inv.status} />
            </td>
            <td className="px-4 py-3 text-xs text-muted-foreground hidden lg:table-cell">
              {inv.gateway ? (GATEWAY_LABELS[inv.gateway] ?? inv.gateway) : '—'}
            </td>
            <td className="px-4 py-3 text-xs text-muted-foreground hidden sm:table-cell whitespace-nowrap">
              {formatDate(inv.created_at)}
            </td>
            <td className="px-4 py-3">
              <button
                onClick={() => setSelectedCode(inv.invoice_code)}
                className="p-2 rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
              >
                <Eye size={15} />
              </button>
            </td>
          </tr>
        ))}
      </TableCard>

      {meta && (
        <Pagination
          page={page}
          lastPage={meta.last_page}
          total={meta.total}
          label="invoice"
          onChange={setPage}
        />
      )}

      {selectedCode && (
        <InvoiceDetailModal
          code={selectedCode}
          token={token}
          onClose={() => setSelectedCode(null)}
        />
      )}
    </div>
  )
}
