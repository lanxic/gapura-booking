'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  CalendarDays, ChevronLeft, ChevronRight, Clock,
  Pencil, Plus, Check, X, Loader2, Ban, LockOpen, LayoutGrid, Trash2,
} from 'lucide-react'
import { useState, useMemo } from 'react'
import { cn } from '@/lib/utils'

// ── constants ─────────────────────────────────────────────────────────────────

const DAYS_ID   = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
const MONTHS_ID = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
]

// ── helpers ───────────────────────────────────────────────────────────────────

function toYMD(d: Date): string {
  return d.toISOString().slice(0, 10)
}

function getDaysInMonth(year: number, month: number): Date[] {
  const days: Date[] = []
  const d = new Date(year, month, 1)
  while (d.getMonth() === month) {
    days.push(new Date(d))
    d.setDate(d.getDate() + 1)
  }
  return days
}

function startOffset(year: number, month: number): number {
  const dow = new Date(year, month, 1).getDay()
  return dow === 0 ? 6 : dow - 1
}

function barColor(pct: number, blocked: boolean) {
  if (blocked)    return 'bg-red-400'
  if (pct >= 100) return 'bg-red-500'
  if (pct >= 90)  return 'bg-orange-400'
  if (pct >= 70)  return 'bg-amber-400'
  return 'bg-emerald-500'
}

function cellBg(pct: number, blocked: boolean) {
  if (blocked || pct >= 100) return 'bg-red-50 hover:bg-red-100'
  if (pct >= 90)             return 'bg-orange-50 hover:bg-orange-100'
  if (pct >= 70)             return 'bg-amber-50 hover:bg-amber-100'
  return 'bg-emerald-50/60 hover:bg-emerald-50'
}

function pctColor(pct: number) {
  if (pct >= 100) return 'text-red-600'
  if (pct >= 90)  return 'text-orange-500'
  if (pct >= 70)  return 'text-amber-600'
  return 'text-emerald-600'
}

const INP = 'w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ring transition'

// ── types ─────────────────────────────────────────────────────────────────────

type SlotRow = {
  id: number
  date: string
  time_slot: string | null
  total_quota: number
  booked_qty: number
  is_blocked: boolean
  product?: { name: string }
  product_id?: number
}

type DayAgg = {
  slots: SlotRow[]
  totalQuota: number
  bookedQty: number
  anyBlocked: boolean
  pct: number
}

// ── SlotEditRow ───────────────────────────────────────────────────────────────

function SlotEditRow({ slot, token, onDone }: {
  slot: SlotRow
  token: string
  onDone: () => void
}) {
  const qc = useQueryClient()
  const [form, setForm] = useState({
    time_slot:   slot.time_slot ?? '',
    total_quota: slot.total_quota,
    is_blocked:  slot.is_blocked,
  })

  const mutation = useMutation({
    mutationFn: () => api.put<any>(`/admin/availability/${slot.id}`, form, { token }),
    onSuccess:  () => { qc.invalidateQueries({ queryKey: ['admin-availability-cal'] }); onDone() },
  })

  return (
    <div className="px-4 py-3 space-y-2.5 bg-primary/5">
      <div>
        <label className="block text-[10px] font-medium text-muted-foreground mb-1">Slot Waktu</label>
        <input type="text" value={form.time_slot}
          onChange={e => setForm(p => ({ ...p, time_slot: e.target.value }))}
          placeholder="contoh: 09:00" className={INP} />
      </div>
      <div>
        <label className="block text-[10px] font-medium text-muted-foreground mb-1">Total Kuota</label>
        <input type="number" min={1} value={form.total_quota}
          onChange={e => setForm(p => ({ ...p, total_quota: parseInt(e.target.value) || 1 }))}
          className={INP} />
      </div>
      <div className="flex items-center justify-between pt-0.5">
        <div className="flex items-center gap-2">
          <button type="button"
            onClick={() => setForm(p => ({ ...p, is_blocked: !p.is_blocked }))}
            className={cn(
              'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
              form.is_blocked ? 'bg-red-500' : 'bg-gray-200',
            )}>
            <span className={cn(
              'inline-block h-4 w-4 rounded-full bg-white shadow transition-transform',
              form.is_blocked ? 'translate-x-4' : 'translate-x-0',
            )} />
          </button>
          <span className="text-xs text-muted-foreground">Blokir</span>
        </div>
        <div className="flex items-center gap-1.5">
          <button type="button" onClick={onDone}
            className="px-2.5 py-1 text-xs rounded-md border border-border hover:bg-accent transition-colors">
            Batal
          </button>
          <button type="button" onClick={() => mutation.mutate()} disabled={mutation.isPending}
            className="px-2.5 py-1 text-xs rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 flex items-center gap-1 transition-colors">
            {mutation.isPending ? <Loader2 size={11} className="animate-spin" /> : <Check size={11} />}
            Simpan
          </button>
        </div>
      </div>
      {mutation.isError && (
        <p className="text-[11px] text-destructive">{(mutation.error as Error)?.message}</p>
      )}
    </div>
  )
}

// ── AddSlotForm ───────────────────────────────────────────────────────────────

function AddSlotForm({ date, productId, token, onDone }: {
  date: string
  productId: string
  token: string
  onDone: () => void
}) {
  const qc = useQueryClient()
  const [form, setForm] = useState({
    product_id:  productId,
    time_slot:   '',
    total_quota: 100,
    is_blocked:  false,
  })

  const { data: productsData } = useQuery({
    queryKey: ['admin-products-list'],
    queryFn:  () => api.get<any>('/admin/products?per_page=100', { token }),
    enabled:  !!token && !productId,
  })
  const products: any[] = productsData?.data ?? []

  const mutation = useMutation({
    mutationFn: () =>
      api.post<any>('/admin/availability', { ...form, date }, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-availability-cal'] })
      onDone()
    },
  })

  return (
    <div className="px-4 py-3 space-y-2.5 bg-primary/5">
      {!productId && (
        <div>
          <label className="block text-[10px] font-medium text-muted-foreground mb-1">
            Produk <span className="text-destructive">*</span>
          </label>
          <select value={form.product_id}
            onChange={e => setForm(p => ({ ...p, product_id: e.target.value }))}
            className={INP}>
            <option value="">Pilih produk…</option>
            {products.map((p: any) => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>
        </div>
      )}
      <div>
        <label className="block text-[10px] font-medium text-muted-foreground mb-1">Slot Waktu</label>
        <input type="text" value={form.time_slot}
          onChange={e => setForm(p => ({ ...p, time_slot: e.target.value }))}
          placeholder="contoh: 09:00" className={INP} />
      </div>
      <div>
        <label className="block text-[10px] font-medium text-muted-foreground mb-1">
          Total Kuota <span className="text-destructive">*</span>
        </label>
        <input type="number" min={1} value={form.total_quota}
          onChange={e => setForm(p => ({ ...p, total_quota: parseInt(e.target.value) || 1 }))}
          className={INP} />
      </div>
      <div className="flex items-center justify-between pt-0.5">
        <div className="flex items-center gap-2">
          <button type="button"
            onClick={() => setForm(p => ({ ...p, is_blocked: !p.is_blocked }))}
            className={cn(
              'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
              form.is_blocked ? 'bg-red-500' : 'bg-gray-200',
            )}>
            <span className={cn(
              'inline-block h-4 w-4 rounded-full bg-white shadow transition-transform',
              form.is_blocked ? 'translate-x-4' : 'translate-x-0',
            )} />
          </button>
          <span className="text-xs text-muted-foreground">Blokir</span>
        </div>
        <div className="flex items-center gap-1.5">
          <button type="button" onClick={onDone}
            className="px-2.5 py-1 text-xs rounded-md border border-border hover:bg-accent transition-colors">
            Batal
          </button>
          <button type="button" onClick={() => mutation.mutate()}
            disabled={mutation.isPending || !form.product_id}
            className="px-2.5 py-1 text-xs rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 flex items-center gap-1 transition-colors">
            {mutation.isPending ? <Loader2 size={11} className="animate-spin" /> : <Plus size={11} />}
            Tambah
          </button>
        </div>
      </div>
      {mutation.isError && (
        <p className="text-[11px] text-destructive">{(mutation.error as Error)?.message}</p>
      )}
    </div>
  )
}

// ── BulkModal ─────────────────────────────────────────────────────────────────

type BulkPreset = 'week' | 'month' | 'custom'

function BulkModal({ token, onClose }: {
  token: string
  onClose: () => void
}) {
  const qc = useQueryClient()

  const [preset, setPreset]           = useState<BulkPreset>('week')
  const [hasTimeSlot, setHasTimeSlot] = useState(false)
  const [form, setForm] = useState(() => {
    const base = new Date()
    const end  = new Date(base); end.setDate(end.getDate() + 6)
    return { product_id: '', from: toYMD(base), to: toYMD(end), time_slot: '', total_quota: 100 }
  })
  const [saved, setSaved] = useState(false)

  const applyPreset = (p: BulkPreset) => {
    setPreset(p)
    const base = new Date()
    if (p === 'week') {
      const end = new Date(base); end.setDate(end.getDate() + 6)
      setForm(prev => ({ ...prev, from: toYMD(base), to: toYMD(end) }))
    } else if (p === 'month') {
      const end = new Date(base); end.setDate(end.getDate() + 29)
      setForm(prev => ({ ...prev, from: toYMD(base), to: toYMD(end) }))
    }
  }

  const dayCount = useMemo(() => {
    if (!form.from || !form.to) return 0
    const diff = new Date(form.to).getTime() - new Date(form.from).getTime()
    return Math.max(0, Math.round(diff / 86400000) + 1)
  }, [form.from, form.to])

  const { data: productsData } = useQuery({
    queryKey: ['admin-products-list'],
    queryFn:  () => api.get<any>('/admin/products?per_page=100', { token }),
    enabled:  !!token,
  })
  const products: any[] = productsData?.data ?? []

  const mutation = useMutation({
    mutationFn: () => api.post<any>('/admin/availability/bulk', {
      ...form,
      time_slot: hasTimeSlot ? form.time_slot : null,
    }, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-availability-cal'] })
      setSaved(true)
    },
  })

  const INP_LG   = 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition'
  const canSubmit = !!form.product_id && !!form.from && !!form.to && form.total_quota > 0 &&
    (!hasTimeSlot || !!form.time_slot)

  const PRESETS: { key: BulkPreset; label: string }[] = [
    { key: 'week',   label: 'Seminggu' },
    { key: 'month',  label: 'Sebulan' },
    { key: 'custom', label: 'Custom' },
  ]

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-md rounded-2xl border border-border bg-card shadow-xl overflow-hidden">

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <div className="flex items-center gap-2">
            <LayoutGrid size={16} className="text-muted-foreground" />
            <h3 className="text-sm font-semibold text-foreground">Buat Slot Massal</h3>
          </div>
          <button onClick={onClose}
            className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
            <X size={16} />
          </button>
        </div>

        <div className="px-5 py-5 space-y-4">

          {/* Product */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">
              Produk <span className="text-destructive">*</span>
            </label>
            <select value={form.product_id}
              onChange={e => setForm(p => ({ ...p, product_id: e.target.value }))}
              className={INP_LG}>
              <option value="">Pilih produk…</option>
              {products.map((p: any) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
            <p className="text-xs text-muted-foreground mt-1">
              Kuota dikelola per produk — setiap produk punya slot ketersediaan sendiri.
            </p>
          </div>

          {/* Date range */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-2">
              Rentang Tanggal <span className="text-destructive">*</span>
            </label>
            <div className="flex gap-1 p-1 bg-muted/40 rounded-lg mb-3">
              {PRESETS.map(({ key, label }) => (
                <button key={key} type="button" onClick={() => applyPreset(key)}
                  className={cn(
                    'flex-1 py-1.5 text-xs rounded-md font-medium transition-colors',
                    preset === key
                      ? 'bg-background text-foreground shadow-sm border border-border/60'
                      : 'text-muted-foreground hover:text-foreground',
                  )}>
                  {label}
                </button>
              ))}
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Dari</label>
                <input type="date" value={form.from}
                  onChange={e => { setPreset('custom'); setForm(p => ({ ...p, from: e.target.value })) }}
                  className={INP_LG} />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Sampai</label>
                <input type="date" value={form.to}
                  onChange={e => { setPreset('custom'); setForm(p => ({ ...p, to: e.target.value })) }}
                  className={INP_LG} />
              </div>
            </div>
            {dayCount > 0 && (
              <p className="text-xs text-muted-foreground mt-1.5 tabular-nums">
                {dayCount} hari akan dibuat/diperbarui
              </p>
            )}
          </div>

          {/* Time slot toggle */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-2">Slot Waktu</label>
            <div className="flex gap-1 p-1 bg-muted/40 rounded-lg">
              <button type="button" onClick={() => { setHasTimeSlot(false); setForm(p => ({ ...p, time_slot: '' })) }}
                className={cn(
                  'flex-1 py-1.5 text-xs rounded-md font-medium transition-colors',
                  !hasTimeSlot
                    ? 'bg-background text-foreground shadow-sm border border-border/60'
                    : 'text-muted-foreground hover:text-foreground',
                )}>
                Tanpa Slot Waktu
              </button>
              <button type="button" onClick={() => setHasTimeSlot(true)}
                className={cn(
                  'flex-1 py-1.5 text-xs rounded-md font-medium transition-colors',
                  hasTimeSlot
                    ? 'bg-background text-foreground shadow-sm border border-border/60'
                    : 'text-muted-foreground hover:text-foreground',
                )}>
                Dengan Slot Waktu
              </button>
            </div>
            {hasTimeSlot && (
              <input type="text" value={form.time_slot}
                onChange={e => setForm(p => ({ ...p, time_slot: e.target.value }))}
                placeholder="contoh: 08:00-17:00"
                className={cn(INP_LG, 'mt-2')} />
            )}
          </div>

          {/* Quota */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">
              Total Kuota <span className="text-destructive">*</span>
            </label>
            <input type="number" min={1} value={form.total_quota}
              onChange={e => setForm(p => ({ ...p, total_quota: parseInt(e.target.value) || 1 }))}
              className={INP_LG} />
          </div>

          {/* Info banner */}
          <div className="text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2 leading-relaxed">
            Slot yang sudah ada di rentang ini akan diperbarui kuotanya, bukan ditambahkan ulang.
          </div>

          {mutation.isError && (
            <p className="text-sm text-destructive">{(mutation.error as Error)?.message}</p>
          )}
          {saved && (
            <p className="text-sm text-emerald-600 flex items-center gap-1.5">
              <Check size={13} /> Slot berhasil dibuat/diperbarui.
            </p>
          )}
        </div>

        {/* Footer */}
        <div className="px-5 py-4 border-t border-border bg-muted/20 flex justify-end gap-3">
          <button onClick={onClose}
            className="px-4 py-2 text-sm rounded-lg border border-border hover:bg-accent transition-colors">
            Tutup
          </button>
          <button onClick={() => mutation.mutate()} disabled={mutation.isPending || !canSubmit}
            className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
            {mutation.isPending ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />}
            Buat Slot
          </button>
        </div>
      </div>
    </div>
  )
}

// ── DetailPanel ───────────────────────────────────────────────────────────────

function DetailPanel({ selected, agg, productId, token }: {
  selected: string
  agg: DayAgg | undefined
  productId: string
  token: string
}) {
  const qc = useQueryClient()
  const [editingId,    setEditingId]    = useState<number | null>(null)
  const [showAdd,      setShowAdd]      = useState(false)
  const [confirmReset, setConfirmReset] = useState(false)

  const blockMutation = useMutation({
    mutationFn: (is_blocked: boolean) =>
      api.post<any>('/admin/availability/block', {
        product_id: productId,
        dates:      [selected],
        is_blocked,
      }, { token }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-availability-cal'] }),
  })

  const deleteMutation = useMutation({
    mutationFn: (slotId: number) =>
      api.delete<any>(`/admin/availability/${slotId}`, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-availability-cal'] })
      setEditingId(null)
    },
  })

  const resetMutation = useMutation({
    mutationFn: () =>
      api.post<any>('/admin/availability/reset', {
        product_id: productId,
        from: selected,
        to:   selected,
      }, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-availability-cal'] })
      setConfirmReset(false)
      setShowAdd(false)
      setEditingId(null)
    },
  })

  const allBlocked = !!agg && agg.slots.length > 0 && agg.slots.every(s => s.is_blocked)

  const dateLabel = new Date(selected + 'T00:00:00').toLocaleDateString('id-ID', {
    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
  })

  return (
    <div className="w-72 shrink-0 rounded-xl border border-border bg-card overflow-hidden">
      {/* Panel header */}
      <div className="px-4 py-3.5 border-b border-border bg-muted/20">
        <p className="text-xs text-muted-foreground">{dateLabel}</p>
        {agg ? (
          <div className="flex items-center justify-between mt-1">
            <p className="text-sm font-semibold text-foreground">
              {agg.bookedQty} / {agg.totalQuota} terbooking
            </p>
            <span className={cn(
              'text-xs font-bold px-2 py-0.5 rounded-full',
              agg.pct >= 100 ? 'bg-red-100 text-red-700' :
              agg.pct >= 90  ? 'bg-orange-100 text-orange-700' :
              agg.pct >= 70  ? 'bg-amber-100 text-amber-700' :
                               'bg-emerald-100 text-emerald-700',
            )}>
              {agg.pct}%
            </span>
          </div>
        ) : (
          <p className="text-sm font-medium text-muted-foreground mt-0.5">Tidak ada data</p>
        )}
      </div>

      {/* Slot list */}
      <div className="divide-y divide-border">
        {agg?.slots.map((slot, i) => {
          const slotPct = slot.total_quota > 0
            ? Math.round((slot.booked_qty / slot.total_quota) * 100)
            : 0

          if (editingId === slot.id) {
            return (
              <SlotEditRow
                key={slot.id}
                slot={slot}
                token={token}
                onDone={() => setEditingId(null)}
              />
            )
          }

          return (
            <div key={slot.id ?? i} className="px-4 py-3 space-y-2">
              <div className="flex items-center justify-between gap-2">
                <div className="flex items-center gap-1.5 min-w-0">
                  <Clock size={12} className="text-muted-foreground shrink-0" />
                  <span className="text-xs font-medium text-foreground truncate">
                    {slot.time_slot ?? 'Tanpa slot waktu'}
                  </span>
                </div>
                <div className="flex items-center gap-1 shrink-0">
                  {slot.is_blocked && (
                    <span className="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded">
                      Blokir
                    </span>
                  )}
                  <button
                    onClick={() => { setEditingId(slot.id); setShowAdd(false) }}
                    className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                    title="Edit slot"
                  >
                    <Pencil size={12} />
                  </button>
                  <button
                    onClick={() => deleteMutation.mutate(slot.id)}
                    disabled={deleteMutation.isPending}
                    className="p-1 rounded-md text-muted-foreground hover:text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50"
                    title="Hapus slot"
                  >
                    {deleteMutation.isPending
                      ? <Loader2 size={12} className="animate-spin" />
                      : <Trash2 size={12} />
                    }
                  </button>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <div className="flex-1 h-1.5 rounded-full bg-muted overflow-hidden">
                  <div
                    className={cn('h-full rounded-full', barColor(slotPct, slot.is_blocked))}
                    style={{ width: `${Math.min(slotPct, 100)}%` }}
                  />
                </div>
                <span className="text-xs text-muted-foreground shrink-0 tabular-nums">
                  {slot.booked_qty}/{slot.total_quota}
                </span>
              </div>
            </div>
          )
        })}

        {deleteMutation.isError && (
          <div className="px-4 py-2">
            <p className="text-[11px] text-destructive">{(deleteMutation.error as Error)?.message}</p>
          </div>
        )}

        {!agg && (
          <div className="px-4 py-8 text-center">
            <CalendarDays size={28} className="mx-auto mb-2 text-muted-foreground/30" />
            <p className="text-sm text-muted-foreground">Belum ada slot untuk tanggal ini.</p>
          </div>
        )}
      </div>

      {/* Add slot form */}
      {showAdd && (
        <div className="border-t border-border">
          <AddSlotForm
            date={selected}
            productId={productId}
            token={token}
            onDone={() => setShowAdd(false)}
          />
        </div>
      )}

      {/* Panel footer actions */}
      {confirmReset ? (
        <div className="px-4 py-3 border-t border-border bg-red-50 space-y-2">
          <p className="text-xs text-red-700 font-medium">Hapus semua slot pada tanggal ini?</p>
          {resetMutation.isError && (
            <p className="text-[11px] text-destructive">{(resetMutation.error as Error)?.message}</p>
          )}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setConfirmReset(false)}
              className="flex-1 py-1.5 text-xs rounded-lg border border-border bg-background hover:bg-accent transition-colors"
            >
              Batal
            </button>
            <button
              onClick={() => resetMutation.mutate()}
              disabled={resetMutation.isPending}
              className="flex-1 py-1.5 text-xs rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-1.5 transition-colors"
            >
              {resetMutation.isPending ? <Loader2 size={11} className="animate-spin" /> : <Trash2 size={11} />}
              Ya, Reset
            </button>
          </div>
        </div>
      ) : (
        <div className="px-4 py-3 border-t border-border bg-muted/10 flex items-center justify-between gap-2">
          <div className="flex items-center gap-1.5">
            {/* Block/Unblock — only when a product is filtered */}
            {productId && agg && agg.slots.length > 0 && (
              <button
                onClick={() => blockMutation.mutate(!allBlocked)}
                disabled={blockMutation.isPending}
                className={cn(
                  'flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border font-medium disabled:opacity-50 transition-colors',
                  allBlocked
                    ? 'border-emerald-200 text-emerald-700 hover:bg-emerald-50'
                    : 'border-red-200 text-red-600 hover:bg-red-50',
                )}
              >
                {blockMutation.isPending
                  ? <Loader2 size={12} className="animate-spin" />
                  : allBlocked ? <LockOpen size={12} /> : <Ban size={12} />
                }
                {allBlocked ? 'Buka Blokir' : 'Blokir'}
              </button>
            )}

            {/* Reset all slots for this day */}
            {productId && agg && agg.slots.length > 0 && (
              <button
                onClick={() => { setConfirmReset(true); setShowAdd(false); setEditingId(null) }}
                className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors"
              >
                <Trash2 size={12} /> Reset
              </button>
            )}
          </div>

          {/* Add slot */}
          {!showAdd && (
            <button
              onClick={() => { setShowAdd(true); setEditingId(null); setConfirmReset(false) }}
              className="flex items-center gap-1.5 px-2.5 py-1.5 text-xs rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
            >
              <Plus size={12} /> Tambah Slot
            </button>
          )}
        </div>
      )}
    </div>
  )
}

// ── BulkResetModal ────────────────────────────────────────────────────────────

function BulkResetModal({ token, onClose }: {
  token: string
  onClose: () => void
}) {
  const qc = useQueryClient()

  const [preset, setPreset] = useState<BulkPreset>('week')
  const [form, setForm] = useState(() => {
    const base = new Date()
    const end  = new Date(base); end.setDate(end.getDate() + 6)
    return { product_id: 'all', from: toYMD(base), to: toYMD(end) }
  })
  const [confirmed,    setConfirmed]    = useState(false)
  const [done,         setDone]         = useState(false)
  const [doneMessage,  setDoneMessage]  = useState('')

  const applyPreset = (p: BulkPreset) => {
    setPreset(p)
    const base = new Date()
    if (p === 'week') {
      const end = new Date(base); end.setDate(end.getDate() + 6)
      setForm(prev => ({ ...prev, from: toYMD(base), to: toYMD(end) }))
    } else if (p === 'month') {
      const end = new Date(base); end.setDate(end.getDate() + 29)
      setForm(prev => ({ ...prev, from: toYMD(base), to: toYMD(end) }))
    }
    setConfirmed(false)
  }

  const dayCount = useMemo(() => {
    if (!form.from || !form.to) return 0
    return Math.max(0, Math.round((new Date(form.to).getTime() - new Date(form.from).getTime()) / 86400000) + 1)
  }, [form.from, form.to])

  const { data: productsData } = useQuery({
    queryKey: ['admin-products-list'],
    queryFn:  () => api.get<any>('/admin/products?per_page=100', { token }),
    enabled:  !!token,
  })
  const products: any[] = productsData?.data ?? []

  const mutation = useMutation({
    mutationFn: () => api.post<any>('/admin/availability/reset', {
      ...form,
      product_id: form.product_id === 'all' ? null : form.product_id,
    }, { token }),
    onSuccess: (res: any) => {
      qc.invalidateQueries({ queryKey: ['admin-availability-cal'] })
      setDoneMessage(res?.message ?? 'Slot berhasil direset.')
      setDone(true)
    },
  })

  const INP_LG   = 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition'
  const canSubmit = !!form.from && !!form.to

  const PRESETS: { key: BulkPreset; label: string }[] = [
    { key: 'week',   label: 'Seminggu' },
    { key: 'month',  label: 'Sebulan' },
    { key: 'custom', label: 'Custom' },
  ]

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
      <div className="w-full max-w-md rounded-2xl border border-border bg-card shadow-xl overflow-hidden">

        {/* Header */}
        <div className="flex items-center justify-between px-5 py-4 border-b border-border">
          <div className="flex items-center gap-2">
            <Trash2 size={16} className="text-red-500" />
            <h3 className="text-sm font-semibold text-foreground">Reset Slot Massal</h3>
          </div>
          <button onClick={onClose}
            className="p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
            <X size={16} />
          </button>
        </div>

        <div className="px-5 py-5 space-y-4">

          {/* Product */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Produk</label>
            <select value={form.product_id}
              onChange={e => { setForm(p => ({ ...p, product_id: e.target.value })); setConfirmed(false) }}
              className={INP_LG}>
              <option value="all">Semua Produk</option>
              {products.map((p: any) => (
                <option key={p.id} value={p.id}>{p.name}</option>
              ))}
            </select>
          </div>

          {/* Date range */}
          <div>
            <label className="block text-sm font-medium text-foreground mb-2">
              Rentang Tanggal <span className="text-destructive">*</span>
            </label>
            <div className="flex gap-1 p-1 bg-muted/40 rounded-lg mb-3">
              {PRESETS.map(({ key, label }) => (
                <button key={key} type="button" onClick={() => applyPreset(key)}
                  className={cn(
                    'flex-1 py-1.5 text-xs rounded-md font-medium transition-colors',
                    preset === key
                      ? 'bg-background text-foreground shadow-sm border border-border/60'
                      : 'text-muted-foreground hover:text-foreground',
                  )}>
                  {label}
                </button>
              ))}
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Dari</label>
                <input type="date" value={form.from}
                  onChange={e => { setPreset('custom'); setConfirmed(false); setForm(p => ({ ...p, from: e.target.value })) }}
                  className={INP_LG} />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Sampai</label>
                <input type="date" value={form.to}
                  onChange={e => { setPreset('custom'); setConfirmed(false); setForm(p => ({ ...p, to: e.target.value })) }}
                  className={INP_LG} />
              </div>
            </div>
            {dayCount > 0 && (
              <p className="text-xs text-muted-foreground mt-1.5 tabular-nums">
                {dayCount} hari akan direset
              </p>
            )}
          </div>

          {/* Warning */}
          <div className="text-xs text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2 leading-relaxed">
            Semua slot tanpa booking pada rentang ini akan <strong>dihapus permanen</strong>. Slot yang sudah ada booking tidak akan terpengaruh.
          </div>

          {/* Confirm checkbox */}
          {!done && (
            <label className="flex items-center gap-2.5 cursor-pointer select-none">
              <input type="checkbox" checked={confirmed}
                onChange={e => setConfirmed(e.target.checked)}
                className="w-4 h-4 accent-red-600 rounded" />
              <span className="text-xs text-foreground">Saya mengerti dan ingin melanjutkan reset</span>
            </label>
          )}

          {mutation.isError && (
            <p className="text-sm text-destructive">{(mutation.error as Error)?.message}</p>
          )}
          {done && (
            <p className="text-sm text-emerald-600 flex items-start gap-1.5">
              <Check size={13} className="mt-0.5 shrink-0" /> {doneMessage}
            </p>
          )}
        </div>

        {/* Footer */}
        <div className="px-5 py-4 border-t border-border bg-muted/20 flex justify-end gap-3">
          <button onClick={onClose}
            className="px-4 py-2 text-sm rounded-lg border border-border hover:bg-accent transition-colors">
            Tutup
          </button>
          {!done && (
            <button onClick={() => mutation.mutate()}
              disabled={mutation.isPending || !canSubmit || !confirmed}
              className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 transition-colors">
              {mutation.isPending ? <Loader2 size={14} className="animate-spin" /> : <Trash2 size={14} />}
              Reset Slot
            </button>
          )}
        </div>
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function AvailabilityPage() {
  const token = useAdminAuthStore(s => s.token)

  const today = useMemo(() => new Date(), [])
  const [year,      setYear]      = useState(today.getFullYear())
  const [month,     setMonth]     = useState(today.getMonth())
  const [productId, setProductId] = useState('')
  const [selected,  setSelected]  = useState<string | null>(null)
  const [showBulk,       setShowBulk]       = useState(false)
  const [showBulkReset,  setShowBulkReset]  = useState(false)

  const firstDay = new Date(year, month, 1)
  const lastDay  = new Date(year, month + 1, 0)
  const from     = toYMD(firstDay)
  const to       = toYMD(lastDay)

  const { data: productsData } = useQuery({
    queryKey: ['admin-products-list'],
    queryFn:  () => api.get<any>('/admin/products?per_page=100', { token: token! }),
    enabled:  !!token,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-availability-cal', productId, from, to],
    queryFn:  () =>
      api.get<any>(
        `/admin/availability?product_id=${productId}&from=${from}&to=${to}&per_page=500`,
        { token: token! },
      ),
    enabled: !!token,
  })

  const products: any[] = productsData?.data ?? []
  const rows: SlotRow[]  = data?.data ?? []

  const byDate = useMemo(() => {
    const map = new Map<string, DayAgg>()
    for (const row of rows) {
      const key = row.date.slice(0, 10)
      const cur = map.get(key)
      if (!cur) {
        const pct = row.total_quota > 0 ? Math.round((row.booked_qty / row.total_quota) * 100) : 0
        map.set(key, { slots: [row], totalQuota: row.total_quota, bookedQty: row.booked_qty, anyBlocked: row.is_blocked, pct })
      } else {
        cur.slots.push(row)
        cur.totalQuota += row.total_quota
        cur.bookedQty  += row.booked_qty
        cur.anyBlocked  = cur.anyBlocked || row.is_blocked
        cur.pct = cur.totalQuota > 0 ? Math.round((cur.bookedQty / cur.totalQuota) * 100) : 0
      }
    }
    return map
  }, [rows])

  const prevMonth = () => {
    setSelected(null)
    if (month === 0) { setYear(y => y - 1); setMonth(11) }
    else setMonth(m => m - 1)
  }
  const nextMonth = () => {
    setSelected(null)
    if (month === 11) { setYear(y => y + 1); setMonth(0) }
    else setMonth(m => m + 1)
  }

  const days       = getDaysInMonth(year, month)
  const offset     = startOffset(year, month)
  const todayStr   = toYMD(today)
  const totalCells = Math.ceil((offset + days.length) / 7) * 7
  const cells: (Date | null)[] = [
    ...Array(offset).fill(null),
    ...days,
    ...Array(totalCells - offset - days.length).fill(null),
  ]

  return (
    <div className="space-y-6">

      {/* ── Header ── */}
      <div className="flex items-start justify-between gap-4 flex-wrap">
        <div className="flex items-center gap-3">
          <CalendarDays size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Ketersediaan</h1>
            <p className="text-sm text-muted-foreground">Pantau dan kelola kuota slot per produk</p>
          </div>
        </div>

        <div className="flex items-center gap-3">
          <select
            value={productId}
            onChange={e => { setProductId(e.target.value); setSelected(null) }}
            className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">Semua Produk</option>
            {products.map((p: any) => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>

          <button
            onClick={() => setShowBulkReset(true)}
            className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-colors font-medium"
          >
            <Trash2 size={15} /> Reset Massal
          </button>

          <button
            onClick={() => setShowBulk(true)}
            className="flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 transition-colors font-medium"
          >
            <LayoutGrid size={15} /> Buat Massal
          </button>
        </div>
      </div>

      <div className="flex gap-5 items-start">

        {/* ── Calendar card ── */}
        <div className="flex-1 min-w-0 rounded-xl border border-border bg-card overflow-hidden">

          {/* Month nav */}
          <div className="flex items-center justify-between px-5 py-4 border-b border-border">
            <button onClick={prevMonth}
              className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
              <ChevronLeft size={18} />
            </button>
            <h2 className="text-base font-semibold text-foreground">
              {MONTHS_ID[month]} {year}
            </h2>
            <button onClick={nextMonth}
              className="p-1.5 rounded-lg text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
              <ChevronRight size={18} />
            </button>
          </div>

          {/* Day-of-week headers */}
          <div className="grid grid-cols-7 border-b border-border bg-muted/30">
            {DAYS_ID.map(d => (
              <div key={d} className="py-2.5 text-center text-xs font-semibold text-muted-foreground">
                {d}
              </div>
            ))}
          </div>

          {/* Day cells */}
          <div className="grid grid-cols-7">
            {cells.map((day, i) => {
              const isLastCol  = (i + 1) % 7 === 0
              const isLastRow  = i >= totalCells - 7
              const borderCls  = cn(
                'border-b border-r border-border',
                isLastCol && 'border-r-0',
                isLastRow && 'border-b-0',
              )

              if (!day) {
                return <div key={`e-${i}`} className={cn('min-h-[88px] bg-muted/10', borderCls)} />
              }

              const key     = toYMD(day)
              const agg     = byDate.get(key)
              const isToday = key === todayStr
              const isSel   = key === selected
              const isPast  = key < todayStr

              return (
                <button
                  key={key}
                  type="button"
                  onClick={() => setSelected(isSel ? null : key)}
                  className={cn(
                    'min-h-[88px] p-2 flex flex-col items-start gap-1 text-left transition-colors',
                    borderCls,
                    isSel
                      ? 'ring-2 ring-inset ring-primary bg-primary/5'
                      : agg
                        ? cellBg(agg.pct, agg.anyBlocked)
                        : isPast
                          ? 'opacity-40 hover:bg-muted/20'
                          : 'hover:bg-muted/20',
                  )}
                >
                  <span className={cn(
                    'w-6 h-6 flex items-center justify-center rounded-full text-xs font-semibold leading-none shrink-0',
                    isToday
                      ? 'bg-primary text-primary-foreground'
                      : agg ? 'text-foreground' : 'text-muted-foreground',
                  )}>
                    {day.getDate()}
                  </span>

                  {agg?.anyBlocked && (
                    <span className="text-[10px] font-semibold text-red-600 bg-red-100 px-1.5 py-0.5 rounded leading-none">
                      Blokir
                    </span>
                  )}

                  {agg && (
                    <div className="w-full mt-auto space-y-1">
                      <div className="flex items-center justify-between gap-1">
                        <span className="text-[10px] text-muted-foreground leading-none tabular-nums">
                          {agg.bookedQty}/{agg.totalQuota}
                        </span>
                        <span className={cn('text-[10px] font-bold leading-none', pctColor(agg.pct))}>
                          {agg.pct}%
                        </span>
                      </div>
                      <div className="w-full h-1.5 rounded-full bg-muted overflow-hidden">
                        <div
                          className={cn('h-full rounded-full transition-all', barColor(agg.pct, agg.anyBlocked))}
                          style={{ width: `${Math.min(agg.pct, 100)}%` }}
                        />
                      </div>
                      {agg.slots.length > 1 && (
                        <p className="text-[10px] text-muted-foreground leading-none">
                          {agg.slots.length} slot
                        </p>
                      )}
                    </div>
                  )}

                  {isLoading && !agg && (
                    <div className="w-full mt-auto h-1.5 bg-muted rounded animate-pulse" />
                  )}
                </button>
              )
            })}
          </div>

          {/* Legend */}
          <div className="px-5 py-3 border-t border-border bg-muted/20 flex items-center gap-4 flex-wrap">
            {[
              { color: 'bg-emerald-500', label: 'Tersedia (< 70%)' },
              { color: 'bg-amber-400',   label: 'Hampir penuh (70–89%)' },
              { color: 'bg-orange-400',  label: 'Kritis (90–99%)' },
              { color: 'bg-red-500',     label: 'Penuh / Blokir' },
            ].map(({ color, label }) => (
              <div key={label} className="flex items-center gap-1.5 text-xs text-muted-foreground">
                <span className={cn('w-2.5 h-2.5 rounded-sm shrink-0', color)} />
                {label}
              </div>
            ))}
          </div>
        </div>

        {/* ── Detail panel ── */}
        {selected && (
          <DetailPanel
            selected={selected}
            agg={byDate.get(selected)}
            productId={productId}
            token={token!}
          />
        )}
      </div>

      {/* ── Bulk modal ── */}
      {showBulk && (
        <BulkModal
          token={token!}
          onClose={() => setShowBulk(false)}
        />
      )}

      {/* ── Bulk reset modal ── */}
      {showBulkReset && (
        <BulkResetModal
          token={token!}
          onClose={() => setShowBulkReset(false)}
        />
      )}
    </div>
  )
}
