'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import { BookOpen, Search, Loader2, Download, Eye, Plus, X, Banknote, Building2, CreditCard } from 'lucide-react'

type Booking = {
  id: number
  booking_code: string
  guest_name: string
  guest_email: string
  pax_count: number
  status: 'pending' | 'confirmed' | 'attended' | 'cancelled' | 'no_show'
  total_amount: number
  created_at: string
  slot: { date: string; start_time: string; activity: { name: string } }
}

const STATUS_BADGE: Record<string, string> = {
  pending:   'bg-amber-50 text-amber-700',
  confirmed: 'bg-blue-50 text-blue-700',
  attended:  'bg-emerald-50 text-emerald-700',
  cancelled: 'bg-red-50 text-red-600',
  no_show:   'bg-gray-100 text-gray-500',
}

function formatRp(n: number) { return 'Rp ' + n.toLocaleString('id-ID') }
function formatDate(iso: string) { return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) }

type WalkInForm = {
  slot_id: string
  guest_name: string
  guest_email: string
  guest_phone: string
  pax_count: string
  payment_method: 'cash' | 'bank_transfer'
  notes: string
}

const PAYMENT_METHOD_OPTIONS = [
  { value: 'cash',          label: 'Tunai (Cash)',   Icon: Banknote,  color: 'text-emerald-600' },
  { value: 'bank_transfer', label: 'Transfer Bank',  Icon: Building2, color: 'text-blue-600' },
] as const

export default function BookingsAdminPage() {
  const token = useAdminAuthStore(s => s.token)!
  const qc = useQueryClient()
  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [dateFilter, setDateFilter] = useState('')
  const [page, setPage] = useState(1)
  const [selected, setSelected] = useState<Booking | null>(null)
  const [showWalkIn, setShowWalkIn] = useState(false)
  const [walkInForm, setWalkInForm] = useState<WalkInForm>({
    slot_id: '', guest_name: '', guest_email: '', guest_phone: '',
    pax_count: '1', payment_method: 'cash', notes: '',
  })
  const [walkInError, setWalkInError] = useState<string | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-bookings', { search, statusFilter, dateFilter, page }],
    queryFn: () => {
      const params = new URLSearchParams()
      if (search) params.set('guest', search)
      if (statusFilter) params.set('status', statusFilter)
      if (dateFilter) params.set('date', dateFilter)
      params.set('page', String(page))
      return api.get<{ data: Booking[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/bookings?${params}`, { token }
      )
    },
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put(`/admin/bookings/${id}`, { status }, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-bookings'] })
      setSelected(null)
    },
  })

  const walkInMutation = useMutation({
    mutationFn: () => api.post('/admin/bookings', {
      slot_id:        parseInt(walkInForm.slot_id),
      guest_name:     walkInForm.guest_name,
      guest_email:    walkInForm.guest_email,
      guest_phone:    walkInForm.guest_phone || null,
      pax_count:      parseInt(walkInForm.pax_count),
      payment_method: walkInForm.payment_method,
      notes:          walkInForm.notes || null,
    }, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-bookings'] })
      setShowWalkIn(false)
      setWalkInForm({ slot_id: '', guest_name: '', guest_email: '', guest_phone: '', pax_count: '1', payment_method: 'cash', notes: '' })
      setWalkInError(null)
    },
    onError: (e: any) => setWalkInError(e?.message ?? 'Gagal membuat booking'),
  })

  const handleExport = () => {
    const params = new URLSearchParams()
    if (statusFilter) params.set('status', statusFilter)
    if (dateFilter) params.set('date', dateFilter)
    window.open(`${process.env.NEXT_PUBLIC_ADMIN_API_URL}/admin/bookings/export?${params}`)
  }

  const bookings = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <BookOpen size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Bookings</h1>
            <p className="text-sm text-muted-foreground">Manajemen booking aktivitas</p>
          </div>
        </div>
        <div className="flex gap-2">
          <button
            onClick={() => setShowWalkIn(true)}
            className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors"
          >
            <Plus size={15} /> Walk-in
          </button>
          <button
            onClick={handleExport}
            className="flex items-center gap-2 px-4 py-2 border border-border rounded-lg text-sm font-medium hover:bg-muted transition-colors"
          >
            <Download size={15} /> Export CSV
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-card border border-border rounded-xl p-4 flex flex-wrap gap-3">
        <div className="relative flex-1 min-w-[200px]">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari nama / email..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-4 py-2 text-sm border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <input
          type="date"
          value={dateFilter}
          onChange={e => { setDateFilter(e.target.value); setPage(1) }}
          className="text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
        <select
          value={statusFilter}
          onChange={e => { setStatusFilter(e.target.value); setPage(1) }}
          className="text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="attended">Attended</option>
          <option value="cancelled">Cancelled</option>
          <option value="no_show">No Show</option>
        </select>
      </div>

      {/* Table */}
      <div className="bg-card border border-border rounded-xl overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground">
            <Loader2 className="animate-spin w-6 h-6 mr-2" /> Memuat...
          </div>
        ) : bookings.length === 0 ? (
          <div className="text-center py-16 text-muted-foreground">
            <BookOpen className="w-10 h-10 mx-auto mb-3 opacity-30" />
            <p>Belum ada booking</p>
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-muted/40 border-b border-border">
              <tr>
                <th className="text-left px-5 py-3 font-medium text-muted-foreground">Kode</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground">Tamu</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden md:table-cell">Aktivitas</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden lg:table-cell">Tanggal Slot</th>
                <th className="text-center px-4 py-3 font-medium text-muted-foreground">Pax</th>
                <th className="text-right px-4 py-3 font-medium text-muted-foreground hidden sm:table-cell">Total</th>
                <th className="text-center px-4 py-3 font-medium text-muted-foreground">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {bookings.map(b => (
                <tr key={b.id} className="hover:bg-muted/20 transition-colors">
                  <td className="px-5 py-4">
                    <span className="font-mono text-xs font-bold text-foreground">{b.booking_code}</span>
                  </td>
                  <td className="px-4 py-4">
                    <p className="font-medium text-foreground">{b.guest_name}</p>
                    <p className="text-xs text-muted-foreground">{b.guest_email}</p>
                  </td>
                  <td className="px-4 py-4 hidden md:table-cell text-muted-foreground">
                    {b.slot?.activity?.name ?? '—'}
                  </td>
                  <td className="px-4 py-4 hidden lg:table-cell text-muted-foreground">
                    {b.slot?.date ? formatDate(b.slot.date) : '—'}{b.slot?.start_time ? ` · ${b.slot.start_time.slice(0, 5)}` : ''}
                  </td>
                  <td className="px-4 py-4 text-center text-foreground font-medium">{b.pax_count}</td>
                  <td className="px-4 py-4 text-right font-semibold text-foreground hidden sm:table-cell">
                    {formatRp(b.total_amount)}
                  </td>
                  <td className="px-4 py-4 text-center">
                    <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full capitalize', STATUS_BADGE[b.status] ?? '')}>
                      {b.status.replace('_', ' ')}
                    </span>
                  </td>
                  <td className="px-4 py-4">
                    <button
                      onClick={() => setSelected(b)}
                      className="p-2 rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                    >
                      <Eye size={15} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex justify-center gap-2">
          <button disabled={page === 1} onClick={() => setPage(p => p - 1)} className="px-4 py-2 text-sm border border-border rounded-lg disabled:opacity-40 hover:bg-muted transition-colors">
            Sebelumnya
          </button>
          <span className="px-4 py-2 text-sm text-muted-foreground">{meta.current_page} / {meta.last_page}</span>
          <button disabled={page === meta.last_page} onClick={() => setPage(p => p + 1)} className="px-4 py-2 text-sm border border-border rounded-lg disabled:opacity-40 hover:bg-muted transition-colors">
            Berikutnya
          </button>
        </div>
      )}

      {/* Walk-in Booking Modal */}
      {showWalkIn && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
          <div className="bg-card border border-border rounded-2xl p-6 max-w-lg w-full shadow-xl space-y-5 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="font-bold text-foreground text-lg">Tambah Booking Walk-in</h3>
                <p className="text-xs text-muted-foreground mt-0.5">Invoice dibuat langsung dengan status PAID</p>
              </div>
              <button onClick={() => setShowWalkIn(false)} className="text-muted-foreground hover:text-foreground transition-colors">
                <X size={20} />
              </button>
            </div>

            {walkInError && (
              <div className="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg p-3">{walkInError}</div>
            )}

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="sm:col-span-2">
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">ID Slot <span className="text-red-400">*</span></label>
                <input
                  type="number"
                  value={walkInForm.slot_id}
                  onChange={e => setWalkInForm(p => ({ ...p, slot_id: e.target.value }))}
                  placeholder="ID slot dari halaman kelola slot"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                <p className="text-xs text-muted-foreground mt-1">Lihat ID slot di halaman Aktivitas → Kelola Slot</p>
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Nama Tamu <span className="text-red-400">*</span></label>
                <input type="text" value={walkInForm.guest_name}
                  onChange={e => setWalkInForm(p => ({ ...p, guest_name: e.target.value }))}
                  placeholder="Nama lengkap"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">No. HP</label>
                <input type="text" value={walkInForm.guest_phone}
                  onChange={e => setWalkInForm(p => ({ ...p, guest_phone: e.target.value }))}
                  placeholder="08xxxxxxxxxx"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div className="sm:col-span-2">
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Email <span className="text-red-400">*</span></label>
                <input type="email" value={walkInForm.guest_email}
                  onChange={e => setWalkInForm(p => ({ ...p, guest_email: e.target.value }))}
                  placeholder="email@contoh.com"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Jumlah Peserta <span className="text-red-400">*</span></label>
                <input type="number" min={1} value={walkInForm.pax_count}
                  onChange={e => setWalkInForm(p => ({ ...p, pax_count: e.target.value }))}
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Metode Pembayaran</label>
                <div className="grid grid-cols-2 gap-2">
                  {PAYMENT_METHOD_OPTIONS.map(({ value, label, Icon, color }) => (
                    <button
                      key={value}
                      type="button"
                      onClick={() => setWalkInForm(p => ({ ...p, payment_method: value }))}
                      className={cn(
                        'flex items-center gap-2 px-3 py-2.5 rounded-lg border text-xs font-medium transition-all',
                        walkInForm.payment_method === value
                          ? 'border-emerald-500 bg-emerald-50 text-emerald-700 ring-1 ring-emerald-400'
                          : 'border-border hover:bg-muted'
                      )}
                    >
                      <Icon size={14} className={walkInForm.payment_method === value ? 'text-emerald-600' : color} />
                      {label}
                    </button>
                  ))}
                </div>
              </div>
              <div className="sm:col-span-2">
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Catatan</label>
                <textarea
                  value={walkInForm.notes}
                  onChange={e => setWalkInForm(p => ({ ...p, notes: e.target.value }))}
                  rows={2}
                  placeholder="Catatan internal (opsional)"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500 resize-none"
                />
              </div>
            </div>

            <div className="flex gap-3 justify-end pt-2 border-t border-border">
              <button
                onClick={() => setShowWalkIn(false)}
                className="px-4 py-2 text-sm border border-border rounded-lg hover:bg-muted transition-colors"
              >
                Batal
              </button>
              <button
                onClick={() => walkInMutation.mutate()}
                disabled={walkInMutation.isPending || !walkInForm.slot_id || !walkInForm.guest_name || !walkInForm.guest_email}
                className="px-5 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg hover:bg-emerald-700 transition-colors disabled:opacity-50 flex items-center gap-2"
              >
                {walkInMutation.isPending && <Loader2 size={14} className="animate-spin" />}
                Buat Booking
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Detail/edit modal */}
      {selected && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-4">
          <div className="bg-card border border-border rounded-2xl p-6 max-w-md w-full shadow-xl space-y-5">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-foreground text-lg">Detail Booking</h3>
              <button onClick={() => setSelected(null)} className="text-muted-foreground hover:text-foreground transition-colors text-xl leading-none">×</button>
            </div>

            <div className="space-y-3 text-sm">
              <div className="flex justify-between"><span className="text-muted-foreground">Kode</span><span className="font-mono font-bold">{selected.booking_code}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Tamu</span><span>{selected.guest_name}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Email</span><span>{selected.guest_email}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Aktivitas</span><span>{selected.slot?.activity?.name ?? '—'}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Slot</span><span>{selected.slot?.date} {selected.slot?.start_time?.slice(0, 5)}</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Peserta</span><span>{selected.pax_count} orang</span></div>
              <div className="flex justify-between"><span className="text-muted-foreground">Total</span><span className="font-bold">{formatRp(selected.total_amount)}</span></div>
            </div>

            <div>
              <p className="text-xs font-medium text-muted-foreground mb-2">Update Status</p>
              <div className="grid grid-cols-3 gap-2">
                {(['confirmed', 'attended', 'cancelled', 'no_show'] as const).map(s => (
                  <button
                    key={s}
                    disabled={selected.status === s || updateMutation.isPending}
                    onClick={() => updateMutation.mutate({ id: selected.id, status: s })}
                    className={cn(
                      'py-2 px-3 rounded-lg text-xs font-medium capitalize transition-all',
                      selected.status === s
                        ? 'ring-2 ring-emerald-500 ' + (STATUS_BADGE[s] ?? '')
                        : 'border border-border hover:bg-muted ' + (STATUS_BADGE[s] ?? ''),
                    )}
                  >
                    {s.replace('_', ' ')}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
