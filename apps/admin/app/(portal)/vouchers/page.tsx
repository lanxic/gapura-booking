'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Percent, Search, Plus, Pencil, Trash2, X, Loader2, RefreshCw } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

// ── Types ──────────────────────────────────────────────────────────────────

type Voucher = {
  id: number
  code: string
  type: 'percent' | 'fixed'
  value: number
  min_purchase: number
  quota: number
  used_count: number
  valid_from: string | null
  valid_until: string | null
  is_active: boolean
}

type VoucherForm = {
  code: string
  type: 'percent' | 'fixed'
  value: string
  min_purchase: string
  quota: string
  valid_from: string
  valid_until: string
  is_active: boolean
}

const EMPTY_FORM: VoucherForm = {
  code: '', type: 'percent',
  value: '', min_purchase: '', quota: '',
  valid_from: '', valid_until: '',
  is_active: true,
}

// ── Helpers ────────────────────────────────────────────────────────────────

function formatDiscount(v: Voucher) {
  if (v.type === 'percent') return `${v.value}%`
  return `Rp ${Number(v.value).toLocaleString('id-ID')}`
}

function voucherToForm(v: Voucher): VoucherForm {
  return {
    code:         v.code,
    type:         v.type,
    value:        String(v.value),
    min_purchase: v.min_purchase ? String(v.min_purchase) : '',
    quota:        v.quota        ? String(v.quota)        : '',
    valid_from:   v.valid_from  ? v.valid_from.slice(0, 10)  : '',
    valid_until:  v.valid_until ? v.valid_until.slice(0, 10) : '',
    is_active:    v.is_active,
  }
}

function formToPayload(f: VoucherForm) {
  return {
    code:         f.code || undefined,
    type:         f.type,
    value:        parseInt(f.value) || 0,
    min_purchase: f.min_purchase ? parseInt(f.min_purchase) : null,
    quota:        f.quota        ? parseInt(f.quota)        : null,
    valid_from:   f.valid_from  || null,
    valid_until:  f.valid_until || null,
    is_active:    f.is_active,
  }
}

// ── Field components ───────────────────────────────────────────────────────

function Label({ children }: { children: React.ReactNode }) {
  return <label className="block text-xs font-medium text-muted-foreground mb-1">{children}</label>
}

function TextInput({ value, onChange, placeholder, type = 'text' }: {
  value: string; onChange: (v: string) => void; placeholder?: string; type?: string
}) {
  return (
    <input
      type={type}
      value={value}
      onChange={e => onChange(e.target.value)}
      placeholder={placeholder}
      className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
    />
  )
}

// ── Modal ──────────────────────────────────────────────────────────────────

function VoucherModal({
  mode, initial, onClose, onSave, saving, error,
}: {
  mode: 'create' | 'edit'
  initial: VoucherForm
  onClose: () => void
  onSave: (f: VoucherForm) => void
  saving: boolean
  error: string
}) {
  const [form, setForm] = useState<VoucherForm>(initial)
  const set = (key: keyof VoucherForm) => (v: any) => setForm(prev => ({ ...prev, [key]: v }))

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div className="w-full max-w-lg mx-4 rounded-2xl border border-border bg-card shadow-2xl">
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h2 className="text-base font-semibold text-foreground">
            {mode === 'create' ? 'Tambah Voucher' : 'Edit Voucher'}
          </h2>
          <button type="button" onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors">
            <X size={18} />
          </button>
        </div>

        {/* Body */}
        <div className="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

          {/* Kode + Jenis */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Kode Voucher</Label>
              <div className="relative">
                <TextInput
                  value={form.code}
                  onChange={set('code')}
                  placeholder="Otomatis jika kosong"
                />
                {mode === 'create' && form.code === '' && (
                  <button
                    type="button"
                    onClick={() => set('code')(Math.random().toString(36).slice(2, 10).toUpperCase())}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
                    title="Generate kode acak"
                  >
                    <RefreshCw size={13} />
                  </button>
                )}
              </div>
            </div>
            <div>
              <Label>Jenis Diskon</Label>
              <select
                value={form.type}
                onChange={e => set('type')(e.target.value as 'percent' | 'fixed')}
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
              >
                <option value="percent">Persen (%)</option>
                <option value="fixed">Nominal (Rp)</option>
              </select>
            </div>
          </div>

          {/* Diskon */}
          <div>
            <Label>{form.type === 'percent' ? 'Persentase Diskon (%)' : 'Nominal Diskon (Rp)'}</Label>
            <TextInput
              value={form.value}
              onChange={set('value')}
              placeholder={form.type === 'percent' ? '10' : '25000'}
              type="number"
            />
          </div>

          {/* Min purchase + quota */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Min. Pembelian (Rp, opsional)</Label>
              <TextInput value={form.min_purchase} onChange={set('min_purchase')} placeholder="100000" type="number" />
            </div>
            <div>
              <Label>Kuota Penggunaan (opsional)</Label>
              <TextInput value={form.quota} onChange={set('quota')} placeholder="100" type="number" />
            </div>
          </div>

          {/* Tanggal */}
          <div className="grid grid-cols-2 gap-4">
            <div>
              <Label>Berlaku Dari (opsional)</Label>
              <TextInput value={form.valid_from} onChange={set('valid_from')} type="date" />
            </div>
            <div>
              <Label>Berlaku Hingga (opsional)</Label>
              <TextInput value={form.valid_until} onChange={set('valid_until')} type="date" />
            </div>
          </div>

          {/* Status */}
          <div className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
            <div>
              <p className="text-sm font-medium text-foreground">Status Aktif</p>
              <p className="text-xs text-muted-foreground">Voucher dapat digunakan oleh pelanggan</p>
            </div>
            <button
              type="button"
              role="switch"
              aria-checked={form.is_active}
              onClick={() => set('is_active')(!form.is_active)}
              className={cn(
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
                form.is_active ? 'bg-emerald-500' : 'bg-gray-200',
              )}
            >
              <span className={cn(
                'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-200',
                form.is_active ? 'translate-x-5' : 'translate-x-0',
              )} />
            </button>
          </div>

          {error && (
            <p className="text-sm text-destructive rounded-lg bg-destructive/10 px-3 py-2">{error}</p>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-border">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-lg border border-border text-sm font-medium text-foreground hover:bg-accent transition-colors"
          >
            Batal
          </button>
          <button
            type="button"
            onClick={() => onSave(form)}
            disabled={saving}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors"
          >
            {saving && <Loader2 size={14} className="animate-spin" />}
            {mode === 'create' ? 'Buat Voucher' : 'Simpan Perubahan'}
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Delete confirm ─────────────────────────────────────────────────────────

function DeleteConfirm({ voucher, onClose, onConfirm, deleting }: {
  voucher: Voucher; onClose: () => void; onConfirm: () => void; deleting: boolean
}) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div className="w-full max-w-sm mx-4 rounded-2xl border border-border bg-card shadow-2xl p-6 space-y-4">
        <h2 className="text-base font-semibold text-foreground">Hapus Voucher</h2>
        <p className="text-sm text-muted-foreground">
          Yakin ingin menghapus voucher <span className="font-mono font-semibold text-foreground">{voucher.code}</span>? Tindakan ini tidak dapat dibatalkan.
        </p>
        <div className="flex gap-3 justify-end">
          <button type="button" onClick={onClose} className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">Batal</button>
          <button type="button" onClick={onConfirm} disabled={deleting}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-destructive text-destructive-foreground text-sm font-semibold hover:bg-destructive/90 disabled:opacity-50 transition-colors">
            {deleting && <Loader2 size={13} className="animate-spin" />}
            Hapus
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Page ───────────────────────────────────────────────────────────────────

export default function VouchersPage() {
  const token = useAdminAuthStore(s => s.token)!
  const queryClient = useQueryClient()

  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)

  const [modal, setModal] = useState<{ mode: 'create' | 'edit'; voucher?: Voucher } | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Voucher | null>(null)
  const [modalError, setModalError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-vouchers', search, page],
    queryFn: () => api.get<any>(`/admin/vouchers?search=${search}&page=${page}`, { token }),
    enabled: !!token,
  })

  const vouchers: Voucher[] = data?.data ?? []
  const meta = data?.meta ?? {}

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-vouchers'] })

  const createMutation = useMutation({
    mutationFn: (payload: ReturnType<typeof formToPayload>) =>
      api.post<any>('/admin/vouchers', payload, { token }),
    onSuccess: () => { invalidate(); setModal(null); setModalError('') },
    onError: (e: any) => setModalError(e?.message ?? 'Gagal membuat voucher'),
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: ReturnType<typeof formToPayload> }) =>
      api.put<any>(`/admin/vouchers/${id}`, payload, { token }),
    onSuccess: () => { invalidate(); setModal(null); setModalError('') },
    onError: (e: any) => setModalError(e?.message ?? 'Gagal menyimpan voucher'),
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete<any>(`/admin/vouchers/${id}`, { token }),
    onSuccess: () => { invalidate(); setDeleteTarget(null) },
  })

  const handleSave = (form: VoucherForm) => {
    setModalError('')
    const payload = formToPayload(form)
    if (modal?.mode === 'create') {
      createMutation.mutate(payload)
    } else if (modal?.voucher) {
      updateMutation.mutate({ id: modal.voucher.id, payload })
    }
  }

  const isSaving = createMutation.isPending || updateMutation.isPending

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center gap-3">
          <Percent size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Voucher</h1>
            <p className="text-sm text-muted-foreground">Kelola kode diskon dan promo</p>
          </div>
        </div>
        <button
          type="button"
          onClick={() => { setModal({ mode: 'create' }); setModalError('') }}
          className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-colors"
        >
          <Plus size={16} />
          Tambah Voucher
        </button>
      </div>

      {/* Search */}
      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari kode voucher..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Kode</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Jenis</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Diskon</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Penggunaan</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Berlaku Hingga</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Status</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 7 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : vouchers.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted-foreground">
                  Tidak ada voucher. Klik <strong>Tambah Voucher</strong> untuk membuat yang pertama.
                </td>
              </tr>
            ) : vouchers.map(voucher => {
              const isExpired = voucher.valid_until && new Date(voucher.valid_until) < new Date()
              return (
                <tr
                  key={voucher.id}
                  className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
                >
                  <td className="px-4 py-3 font-mono font-semibold tracking-wide text-foreground">
                    {voucher.code}
                  </td>
                  <td className="px-4 py-3">
                    <span className={cn(
                      'px-2 py-0.5 rounded-md text-xs font-medium',
                      voucher.type === 'percent' ? 'bg-violet-50 text-violet-700' : 'bg-blue-50 text-blue-700',
                    )}>
                      {voucher.type === 'percent' ? 'Persen' : 'Nominal'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right font-medium">
                    {formatDiscount(voucher)}
                  </td>
                  <td className="px-4 py-3 text-right text-muted-foreground">
                    {voucher.used_count}
                    {voucher.quota > 0 ? <span className="text-xs"> / {voucher.quota}</span> : ''}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {voucher.valid_until
                      ? new Date(voucher.valid_until).toLocaleDateString('id-ID')
                      : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <span className={cn(
                      'px-2 py-0.5 rounded-md text-xs font-medium',
                      !voucher.is_active || isExpired
                        ? 'bg-gray-100 text-gray-500'
                        : 'bg-emerald-50 text-emerald-700',
                    )}>
                      {isExpired ? 'Kadaluarsa' : voucher.is_active ? 'Aktif' : 'Nonaktif'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        type="button"
                        onClick={() => { setModal({ mode: 'edit', voucher }); setModalError('') }}
                        className="p-1.5 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                        title="Edit"
                      >
                        <Pencil size={14} />
                      </button>
                      <button
                        type="button"
                        onClick={() => setDeleteTarget(voucher)}
                        className="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors"
                        title="Hapus"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta.lastPage > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Total: {meta.total} voucher</span>
          <div className="flex gap-2">
            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors">
              Sebelumnya
            </button>
            <span className="px-3 py-1.5">{page} / {meta.lastPage}</span>
            <button onClick={() => setPage(p => Math.min(meta.lastPage, p + 1))} disabled={page === meta.lastPage}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors">
              Berikutnya
            </button>
          </div>
        </div>
      )}

      {/* Create / Edit modal */}
      {modal && (
        <VoucherModal
          mode={modal.mode}
          initial={modal.voucher ? voucherToForm(modal.voucher) : EMPTY_FORM}
          onClose={() => { setModal(null); setModalError('') }}
          onSave={handleSave}
          saving={isSaving}
          error={modalError}
        />
      )}

      {/* Delete confirm */}
      {deleteTarget && (
        <DeleteConfirm
          voucher={deleteTarget}
          onClose={() => setDeleteTarget(null)}
          onConfirm={() => deleteMutation.mutate(deleteTarget.id)}
          deleting={deleteMutation.isPending}
        />
      )}
    </div>
  )
}
