'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import {
  Users2, Search, Download, X, Mail, Phone, Calendar, BookOpen,
  CheckCircle, XCircle, Eye, Pencil, Trash2, RotateCcw, Plus,
  PowerOff, Power, AlertTriangle,
} from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'

// ── Types ─────────────────────────────────────────────────────────────────────

type Customer = {
  id: number
  name: string
  email: string
  phone: string | null
  is_active: boolean
  email_verified_at: string | null
  deleted_at: string | null
  created_at: string
  bookings?: BookingSummary[]
  invoices?: InvoiceSummary[]
}

type BookingSummary = {
  id: number
  booking_code: string
  status: string
  pax_count: number
  total_amount: number
  created_at: string
  slot?: { date: string; start_time: string; activity?: { name: string } }
}

type InvoiceSummary = {
  id: number
  invoice_code: string
  status: string
  total_amount: number
  created_at: string
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatRp(n: number) { return 'Rp ' + n.toLocaleString('id-ID') }
function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const BOOKING_BADGE: Record<string, string> = {
  pending:   'bg-amber-50 text-amber-700',
  confirmed: 'bg-blue-50 text-blue-700',
  attended:  'bg-emerald-50 text-emerald-700',
  cancelled: 'bg-red-50 text-red-600',
  no_show:   'bg-gray-100 text-gray-500',
}
const INVOICE_BADGE: Record<string, string> = {
  pending:   'bg-amber-50 text-amber-700',
  paid:      'bg-emerald-50 text-emerald-700',
  failed:    'bg-red-50 text-red-600',
  expired:   'bg-gray-100 text-gray-500',
  cancelled: 'bg-red-50 text-red-600',
}

function Avatar({ name, deleted }: { name: string; deleted?: boolean }) {
  return (
    <div className={cn(
      'h-8 w-8 shrink-0 rounded-full text-white text-xs font-bold flex items-center justify-center',
      deleted ? 'bg-gray-400' : 'bg-indigo-500',
    )}>
      {name?.slice(0, 2)?.toUpperCase() || 'CX'}
    </div>
  )
}

// ── Detail Modal ──────────────────────────────────────────────────────────────

function CustomerDetailModal({ customerId, token, onClose }: {
  customerId: number
  token: string
  onClose: () => void
}) {
  const [tab, setTab] = useState<'bookings' | 'invoices'>('bookings')
  const { data, isLoading } = useQuery({
    queryKey: ['admin-customer-detail', customerId],
    queryFn: () => api.get<{ data: Customer }>(`/admin/customers/${customerId}`, { token }),
  })
  const customer = data?.data

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-2xl rounded-2xl border border-border bg-card shadow-2xl flex flex-col max-h-[90vh]">

        <div className="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
          <h2 className="text-base font-semibold">Detail Pelanggan</h2>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X size={18} />
          </button>
        </div>

        {isLoading || !customer ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground text-sm">Memuat...</div>
        ) : (
          <div className="flex flex-col min-h-0 overflow-hidden">
            <div className="px-6 py-5 border-b border-border shrink-0">
              <div className="flex items-center gap-4 mb-4">
                <div className={cn(
                  'h-14 w-14 shrink-0 rounded-full text-white text-lg font-bold flex items-center justify-center',
                  customer.deleted_at ? 'bg-gray-400' : 'bg-indigo-500',
                )}>
                  {customer.name?.slice(0, 2)?.toUpperCase()}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-lg font-semibold text-foreground">{customer.name}</p>
                  <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                    {customer.email_verified_at ? (
                      <span className="flex items-center gap-1 text-xs text-emerald-600 font-medium">
                        <CheckCircle size={12} /> Email Verified
                      </span>
                    ) : (
                      <span className="flex items-center gap-1 text-xs text-amber-600 font-medium">
                        <XCircle size={12} /> Belum Verifikasi
                      </span>
                    )}
                    {customer.deleted_at ? (
                      <span className="flex items-center gap-1 text-xs text-red-500 font-medium">
                        <Trash2 size={12} /> Dihapus
                      </span>
                    ) : customer.is_active ? (
                      <span className="flex items-center gap-1 text-xs text-emerald-600 font-medium">
                        <Power size={12} /> Aktif
                      </span>
                    ) : (
                      <span className="flex items-center gap-1 text-xs text-gray-400 font-medium">
                        <PowerOff size={12} /> Nonaktif
                      </span>
                    )}
                  </div>
                </div>
                <span className="ml-auto shrink-0 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                  {customer.bookings?.length ?? 0} booking
                </span>
              </div>
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Mail size={14} className="shrink-0" />
                  <span className="truncate">{customer.email}</span>
                </div>
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Phone size={14} className="shrink-0" />
                  <span>{customer.phone ?? '—'}</span>
                </div>
                <div className="flex items-center gap-2 text-muted-foreground">
                  <Calendar size={14} className="shrink-0" />
                  <span>Daftar {formatDate(customer.created_at)}</span>
                </div>
              </div>
            </div>

            <div className="px-6 pt-4 border-b border-border shrink-0">
              <div className="flex gap-4">
                {(['bookings', 'invoices'] as const).map(t => (
                  <button key={t} onClick={() => setTab(t)}
                    className={cn(
                      'pb-2 text-sm font-medium border-b-2 transition-colors',
                      tab === t ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
                    )}
                  >
                    {t === 'bookings' ? 'Riwayat Booking' : 'Invoice'}
                  </button>
                ))}
              </div>
            </div>

            <div className="flex-1 overflow-y-auto px-6 py-4">
              {tab === 'bookings' && (
                <div className="space-y-2">
                  {!customer.bookings?.length ? (
                    <p className="text-sm text-muted-foreground text-center py-8">Belum ada booking.</p>
                  ) : customer.bookings.map(b => (
                    <div key={b.id} className="flex items-center gap-3 p-3 rounded-xl border border-border bg-muted/20">
                      <BookOpen size={15} className="text-muted-foreground shrink-0" />
                      <div className="flex-1 min-w-0">
                        <p className="text-xs font-mono font-bold text-foreground">{b.booking_code}</p>
                        <p className="text-xs text-muted-foreground truncate">
                          {b.slot?.activity?.name ?? '—'} · {b.slot?.date ?? ''} {b.slot?.start_time?.slice(0, 5) ?? ''}
                        </p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-xs font-semibold text-foreground">{formatRp(b.total_amount)}</p>
                        <span className={cn('text-[10px] font-medium px-1.5 py-0.5 rounded-full', BOOKING_BADGE[b.status] ?? '')}>
                          {b.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
              {tab === 'invoices' && (
                <div className="space-y-2">
                  {!customer.invoices?.length ? (
                    <p className="text-sm text-muted-foreground text-center py-8">Belum ada invoice.</p>
                  ) : customer.invoices.map(inv => (
                    <div key={inv.id} className="flex items-center gap-3 p-3 rounded-xl border border-border bg-muted/20">
                      <div className="flex-1 min-w-0">
                        <p className="text-xs font-mono font-bold text-foreground">{inv.invoice_code}</p>
                        <p className="text-xs text-muted-foreground">{formatDate(inv.created_at)}</p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-xs font-semibold text-foreground">{formatRp(inv.total_amount)}</p>
                        <span className={cn('text-[10px] font-medium px-1.5 py-0.5 rounded-full', INVOICE_BADGE[inv.status] ?? '')}>
                          {inv.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}

        <div className="flex justify-end px-6 py-4 border-t border-border shrink-0">
          <button onClick={onClose} className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">
            Tutup
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Form Modal (Create / Edit) ─────────────────────────────────────────────────

type FormMode = { kind: 'create' } | { kind: 'edit'; customer: Customer }

function CustomerFormModal({ mode, token, onClose, onSuccess }: {
  mode: FormMode
  token: string
  onClose: () => void
  onSuccess: () => void
}) {
  const isEdit = mode.kind === 'edit'
  const initial = isEdit ? mode.customer : null

  const [name, setName]         = useState(initial?.name ?? '')
  const [email, setEmail]       = useState(initial?.email ?? '')
  const [phone, setPhone]       = useState(initial?.phone ?? '')
  const [password, setPassword] = useState('')
  const [isActive, setIsActive] = useState(initial?.is_active ?? true)
  const [error, setError]       = useState('')

  const mutation = useMutation({
    mutationFn: () => {
      const body: Record<string, unknown> = { name, phone: phone || null, is_active: isActive }
      if (!isEdit) { body.email = email; body.password = password }
      else if (password) { body.password = password }

      return isEdit
        ? api.put<{ message: string }>(`/admin/customers/${initial!.id}`, body, { token })
        : api.post<{ message: string }>('/admin/customers', body, { token })
    },
    onSuccess: () => { onSuccess(); onClose() },
    onError: (e: any) => setError(e.message ?? 'Terjadi kesalahan.'),
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-md rounded-2xl border border-border bg-card shadow-2xl">

        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h2 className="text-base font-semibold">{isEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan'}</h2>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground">
            <X size={18} />
          </button>
        </div>

        <div className="px-6 py-5 space-y-4">
          <div>
            <label className="block text-sm font-medium mb-1">Nama Lengkap</label>
            <input value={name} onChange={e => setName(e.target.value)}
              className="w-full px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Nama pelanggan" />
          </div>

          {!isEdit && (
            <div>
              <label className="block text-sm font-medium mb-1">Email</label>
              <input value={email} onChange={e => setEmail(e.target.value)} type="email"
                className="w-full px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
                placeholder="email@contoh.com" />
            </div>
          )}

          <div>
            <label className="block text-sm font-medium mb-1">No. HP</label>
            <input value={phone} onChange={e => setPhone(e.target.value)} type="tel"
              className="w-full px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="08xxxxxxxxxx" />
          </div>

          <div>
            <label className="block text-sm font-medium mb-1">
              Password{isEdit && <span className="text-muted-foreground font-normal"> (kosongkan jika tidak diubah)</span>}
            </label>
            <input value={password} onChange={e => setPassword(e.target.value)} type="password"
              className="w-full px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder={isEdit ? '••••••••' : 'Min. 8 karakter'} />
          </div>

          <div className="flex items-center justify-between py-2 px-3 rounded-lg border border-input bg-muted/30">
            <span className="text-sm font-medium">Status Aktif</span>
            <button
              type="button"
              onClick={() => setIsActive(v => !v)}
              className={cn(
                'relative w-10 h-5 rounded-full transition-colors',
                isActive ? 'bg-emerald-500' : 'bg-gray-300',
              )}
            >
              <span className={cn(
                'absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform',
                isActive ? 'translate-x-5' : 'translate-x-0',
              )} />
            </button>
          </div>

          {error && (
            <p className="text-xs text-red-500 flex items-center gap-1">
              <AlertTriangle size={12} /> {error}
            </p>
          )}
        </div>

        <div className="flex justify-end gap-3 px-6 py-4 border-t border-border">
          <button onClick={onClose}
            className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">
            Batal
          </button>
          <button
            onClick={() => mutation.mutate()}
            disabled={mutation.isPending}
            className="px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
          >
            {mutation.isPending ? 'Menyimpan...' : isEdit ? 'Simpan' : 'Buat Pelanggan'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Confirm Delete Modal ──────────────────────────────────────────────────────

function ConfirmDeleteModal({ customer, token, onClose, onSuccess }: {
  customer: Customer
  token: string
  onClose: () => void
  onSuccess: () => void
}) {
  const mutation = useMutation({
    mutationFn: () => api.delete<{ message: string }>(`/admin/customers/${customer.id}`, { token }),
    onSuccess: () => { onSuccess(); onClose() },
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-sm rounded-2xl border border-border bg-card shadow-2xl p-6">
        <div className="flex items-start gap-3 mb-5">
          <div className="h-10 w-10 shrink-0 rounded-full bg-red-50 flex items-center justify-center">
            <Trash2 size={18} className="text-red-500" />
          </div>
          <div>
            <h2 className="text-base font-semibold">Hapus Pelanggan</h2>
            <p className="text-sm text-muted-foreground mt-1">
              Hapus <strong>{customer.name}</strong>? Data dapat dipulihkan kembali.
            </p>
          </div>
        </div>
        <div className="flex gap-3">
          <button onClick={onClose}
            className="flex-1 px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">
            Batal
          </button>
          <button
            onClick={() => mutation.mutate()}
            disabled={mutation.isPending}
            className="flex-1 px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium disabled:opacity-50 transition-colors"
          >
            {mutation.isPending ? 'Menghapus...' : 'Ya, Hapus'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function CustomersPage() {
  const token = useAdminAuthStore(s => s.token)!
  const qc    = useQueryClient()

  const [search, setSearch]         = useState('')
  const [verified, setVerified]     = useState('')
  const [activeFilter, setActive]   = useState('')
  const [trashedFilter, setTrashed] = useState('')
  const [page, setPage]             = useState(1)

  const [detailId, setDetailId]                         = useState<number | null>(null)
  const [formMode, setFormMode]                         = useState<FormMode | null>(null)
  const [deleteTarget, setDeleteTarget]                 = useState<Customer | null>(null)

  const queryKey = ['admin-customers', { search, verified, activeFilter, trashedFilter, page }]

  const { data, isLoading } = useQuery({
    queryKey,
    queryFn: () => {
      const params = new URLSearchParams()
      if (search)        params.set('search', search)
      if (verified)      params.set('verified', verified)
      if (activeFilter)  params.set('active', activeFilter)
      if (trashedFilter === 'only') params.set('only_trashed', '1')
      else if (trashedFilter === 'with') params.set('trashed', '1')
      params.set('page', String(page))
      return api.get<{ data: Customer[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/customers?${params}`, { token }
      )
    },
  })

  const refetch = () => qc.invalidateQueries({ queryKey: ['admin-customers'] })

  const toggleActiveMutation = useMutation({
    mutationFn: (id: number) =>
      api.patch<{ message: string; data: Customer }>(`/admin/customers/${id}/toggle-active`, {}, { token }),
    onSuccess: refetch,
  })

  const restoreMutation = useMutation({
    mutationFn: (id: number) =>
      api.post<{ message: string }>(`/admin/customers/${id}/restore`, {}, { token }),
    onSuccess: refetch,
  })

  const handleExport = () => {
    const params = new URLSearchParams()
    if (search)       params.set('search', search)
    if (verified)     params.set('verified', verified)
    if (activeFilter) params.set('active', activeFilter)
    const base = process.env.NEXT_PUBLIC_ADMIN_API_URL ?? ''
    window.open(`${base}/admin/customers/export?${params}`)
  }

  const customers = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      <PageHeader
        icon={Users2}
        title="Pelanggan"
        description="Daftar akun pelanggan yang terdaftar"
        action={
          <div className="flex items-center gap-2">
            <button
              onClick={handleExport}
              className="flex items-center gap-2 px-3 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors"
            >
              <Download size={14} /> Export CSV
            </button>
            <button
              onClick={() => setFormMode({ kind: 'create' })}
              className="flex items-center gap-2 px-3 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors"
            >
              <Plus size={14} /> Tambah
            </button>
          </div>
        }
      />

      {/* Filters */}
      <div className="flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-48">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari nama / email / no. HP..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <select value={verified} onChange={e => { setVerified(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Semua Verifikasi</option>
          <option value="yes">Email Verified</option>
          <option value="no">Belum Verifikasi</option>
        </select>
        <select value={activeFilter} onChange={e => { setActive(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Semua Status</option>
          <option value="yes">Aktif</option>
          <option value="no">Nonaktif</option>
        </select>
        <select value={trashedFilter} onChange={e => { setTrashed(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring">
          <option value="">Tidak Termasuk Terhapus</option>
          <option value="with">Termasuk Terhapus</option>
          <option value="only">Hanya Terhapus</option>
        </select>
      </div>

      {/* Table */}
      <TableCard
        columns={['Pelanggan', 'No. HP', 'Verified', 'Status', 'Terdaftar', '']}
        isLoading={isLoading}
        isEmpty={customers.length === 0}
        emptyMessage="Belum ada pelanggan terdaftar."
      >
        {customers.map(c => (
          <tr key={c.id} className={cn(
            'border-b border-border last:border-0 hover:bg-muted/20 transition-colors',
            c.deleted_at && 'opacity-60',
          )}>
            <td className="px-4 py-3">
              <div className="flex items-center gap-2.5">
                <Avatar name={c.name} deleted={!!c.deleted_at} />
                <div className="min-w-0">
                  <p className="font-medium text-foreground truncate">{c.name}</p>
                  <p className="text-xs text-muted-foreground truncate">{c.email}</p>
                </div>
              </div>
            </td>
            <td className="px-4 py-3 text-sm text-muted-foreground">{c.phone ?? '—'}</td>
            <td className="px-4 py-3">
              {c.email_verified_at ? (
                <span className="flex items-center gap-1 text-xs font-medium text-emerald-600">
                  <CheckCircle size={12} /> Verified
                </span>
              ) : (
                <span className="flex items-center gap-1 text-xs font-medium text-amber-600">
                  <XCircle size={12} /> Belum
                </span>
              )}
            </td>
            <td className="px-4 py-3">
              {c.deleted_at ? (
                <span className="flex items-center gap-1 text-xs font-medium text-red-500">
                  <Trash2 size={12} /> Dihapus
                </span>
              ) : c.is_active ? (
                <span className="flex items-center gap-1 text-xs font-medium text-emerald-600">
                  <Power size={12} /> Aktif
                </span>
              ) : (
                <span className="flex items-center gap-1 text-xs font-medium text-gray-400">
                  <PowerOff size={12} /> Nonaktif
                </span>
              )}
            </td>
            <td className="px-4 py-3 text-xs text-muted-foreground">{formatDate(c.created_at)}</td>
            <td className="px-4 py-3">
              <div className="flex items-center gap-1 justify-end">
                {c.deleted_at ? (
                  <button
                    onClick={() => restoreMutation.mutate(c.id)}
                    title="Pulihkan"
                    className="p-1.5 rounded-lg hover:bg-emerald-50 text-emerald-600 transition-colors"
                  >
                    <RotateCcw size={14} />
                  </button>
                ) : (
                  <>
                    <button
                      onClick={() => setDetailId(c.id)}
                      title="Detail"
                      className="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                    >
                      <Eye size={14} />
                    </button>
                    <button
                      onClick={() => setFormMode({ kind: 'edit', customer: c })}
                      title="Edit"
                      className="p-1.5 rounded-lg hover:bg-muted text-muted-foreground hover:text-foreground transition-colors"
                    >
                      <Pencil size={14} />
                    </button>
                    <button
                      onClick={() => toggleActiveMutation.mutate(c.id)}
                      title={c.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                      className={cn(
                        'p-1.5 rounded-lg transition-colors',
                        c.is_active
                          ? 'hover:bg-amber-50 text-amber-500'
                          : 'hover:bg-emerald-50 text-emerald-500',
                      )}
                    >
                      {c.is_active ? <PowerOff size={14} /> : <Power size={14} />}
                    </button>
                    <button
                      onClick={() => setDeleteTarget(c)}
                      title="Hapus"
                      className="p-1.5 rounded-lg hover:bg-red-50 text-red-400 hover:text-red-600 transition-colors"
                    >
                      <Trash2 size={14} />
                    </button>
                  </>
                )}
              </div>
            </td>
          </tr>
        ))}
      </TableCard>

      {meta && (
        <Pagination
          page={page}
          lastPage={meta.last_page}
          total={meta.total}
          label="pelanggan"
          onChange={setPage}
        />
      )}

      {/* Modals */}
      {detailId !== null && (
        <CustomerDetailModal customerId={detailId} token={token} onClose={() => setDetailId(null)} />
      )}
      {formMode !== null && (
        <CustomerFormModal
          mode={formMode}
          token={token}
          onClose={() => setFormMode(null)}
          onSuccess={refetch}
        />
      )}
      {deleteTarget !== null && (
        <ConfirmDeleteModal
          customer={deleteTarget}
          token={token}
          onClose={() => setDeleteTarget(null)}
          onSuccess={refetch}
        />
      )}
    </div>
  )
}
