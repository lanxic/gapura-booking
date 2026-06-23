'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useAdminAuthStore } from '@/store/auth'
import { formatRupiah } from 'ui'
import { Tag, Plus, Pencil, Trash2, Loader2, ChevronLeft, ChevronRight, X } from 'lucide-react'

type Offer = {
  id: number
  title: string
  slug: string
  discount_type: 'percent' | 'fixed'
  discount_value: number
  badge: string | null
  start_date: string
  end_date: string
  is_active: boolean
  activities: { id: number; name: string }[]
}

type OfferForm = {
  title: string
  discount_type: 'percent' | 'fixed'
  discount_value: string
  badge: string
  start_date: string
  end_date: string
  description: string
  is_active: boolean
}

const EMPTY_FORM: OfferForm = {
  title: '', discount_type: 'percent', discount_value: '',
  badge: '', start_date: '', end_date: '', description: '', is_active: true,
}

function formatDate(iso: string) {
  return new Date(iso).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

function OfferFormModal({
  offer,
  onClose,
  token,
}: {
  offer: Offer | null
  onClose: () => void
  token: string
}) {
  const qc    = useQueryClient()
  const isNew = !offer
  const [form, setForm] = useState<OfferForm>(
    offer
      ? {
          title:          offer.title,
          discount_type:  offer.discount_type,
          discount_value: String(offer.discount_value),
          badge:          offer.badge ?? '',
          start_date:     offer.start_date?.split('T')[0] ?? '',
          end_date:       offer.end_date?.split('T')[0] ?? '',
          description:    '',
          is_active:      offer.is_active,
        }
      : { ...EMPTY_FORM }
  )
  const [error, setError] = useState('')

  const mutation = useMutation({
    mutationFn: () => {
      const payload = {
        title:          form.title,
        discount_type:  form.discount_type,
        discount_value: parseFloat(form.discount_value) || 0,
        badge:          form.badge || null,
        start_date:     form.start_date,
        end_date:       form.end_date,
        description:    form.description || null,
        is_active:      form.is_active,
      }
      return isNew
        ? api.post('/admin/offers', payload, { token })
        : api.put(`/admin/offers/${offer!.id}`, payload, { token })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['offers-admin'] })
      onClose()
    },
    onError: (e: Error) => setError(e.message),
  })

  function setF<K extends keyof OfferForm>(key: K, value: OfferForm[K]) {
    setForm((f) => ({ ...f, [key]: value }))
    setError('')
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div className="bg-card rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4 overflow-y-auto max-h-[90vh]">
        <div className="flex items-center justify-between">
          <h3 className="font-bold text-foreground">{isNew ? 'Tambah Offer' : 'Edit Offer'}</h3>
          <button onClick={onClose} className="p-1.5 rounded-lg hover:bg-accent transition-colors">
            <X className="w-4 h-4" />
          </button>
        </div>

        {error && <p className="text-sm text-red-500">{error}</p>}

        <div className="space-y-3">
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Judul *</label>
            <input value={form.title} onChange={(e) => setF('title', e.target.value)}
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Tipe Diskon</label>
              <select value={form.discount_type} onChange={(e) => setF('discount_type', e.target.value as 'percent' | 'fixed')}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="percent">Persen (%)</option>
                <option value="fixed">Nominal (Rp)</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">
                Nilai {form.discount_type === 'percent' ? '(%)' : '(Rp)'}
              </label>
              <input value={form.discount_value} onChange={(e) => setF('discount_value', e.target.value)}
                placeholder={form.discount_type === 'percent' ? '20' : '50000'}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Badge (opsional)</label>
            <input value={form.badge} onChange={(e) => setF('badge', e.target.value)}
              placeholder="HOT, BARU, TERBATAS..."
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Mulai *</label>
              <input type="date" value={form.start_date} onChange={(e) => setF('start_date', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Berakhir *</label>
              <input type="date" value={form.end_date} onChange={(e) => setF('end_date', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>

          <div className="flex items-center gap-2">
            <input type="checkbox" id="is_active" checked={form.is_active}
              onChange={(e) => setF('is_active', e.target.checked)}
              className="rounded border-border" />
            <label htmlFor="is_active" className="text-sm text-foreground">Aktif</label>
          </div>
        </div>

        <div className="flex gap-3 pt-2">
          <button onClick={onClose}
            className="flex-1 py-2.5 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors">
            Batal
          </button>
          <button onClick={() => mutation.mutate()} disabled={mutation.isPending}
            className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-primary text-primary-foreground text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-60">
            {mutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
            {isNew ? 'Tambah' : 'Simpan'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default function OffersAdminPage() {
  const { token } = useAdminAuthStore()
  const qc        = useQueryClient()
  const [page, setPage]         = useState(1)
  const [modal, setModal]       = useState<Offer | null | 'new'>(null)
  const [deletingId, setDel]    = useState<number | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['offers-admin', page],
    queryFn: () => api.get<{ data: Offer[]; meta: { last_page: number; total: number } }>(
      `/admin/offers?page=${page}&per_page=15`,
      { token: token! }
    ),
    enabled: !!token,
  })

  const deleteOffer = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/offers/${id}`, { token: token! }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['offers-admin'] })
      setDel(null)
    },
  })

  const offers   = data?.data ?? []
  const lastPage = data?.meta?.last_page ?? 1

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Offer &amp; Promo</h1>
          <p className="text-sm text-muted-foreground">
            Kelola penawaran dan kode promo
          </p>
        </div>
        <button
          onClick={() => setModal('new')}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors"
        >
          <Plus className="w-4 h-4" /> Tambah Offer
        </button>
      </div>

      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="px-4 py-3 text-left font-semibold text-muted-foreground">Judul</th>
              <th className="px-4 py-3 text-left font-semibold text-muted-foreground">Diskon</th>
              <th className="px-4 py-3 text-left font-semibold text-muted-foreground">Periode</th>
              <th className="px-4 py-3 text-left font-semibold text-muted-foreground">Status</th>
              <th className="px-4 py-3 text-right font-semibold text-muted-foreground">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {isLoading && (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border/50">
                  <td colSpan={5} className="px-4 py-3">
                    <div className="h-5 bg-gray-100 rounded animate-pulse" />
                  </td>
                </tr>
              ))
            )}
            {!isLoading && offers.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-16 text-center text-muted-foreground">
                  <Tag className="w-8 h-8 mx-auto mb-2 text-gray-300" />
                  <p className="font-medium">Belum ada offer.</p>
                </td>
              </tr>
            )}
            {offers.map((offer) => (
              <tr key={offer.id} className="border-b border-border/50 hover:bg-muted/20 transition-colors">
                <td className="px-4 py-3">
                  <p className="font-medium text-foreground">{offer.title}</p>
                  {offer.badge && (
                    <span className="inline-block mt-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs px-2 py-0.5 font-semibold">
                      {offer.badge}
                    </span>
                  )}
                </td>
                <td className="px-4 py-3 text-foreground">
                  {offer.discount_type === 'percent'
                    ? `${offer.discount_value}%`
                    : formatRupiah(offer.discount_value)}
                </td>
                <td className="px-4 py-3 text-muted-foreground">
                  {formatDate(offer.start_date)} – {formatDate(offer.end_date)}
                </td>
                <td className="px-4 py-3">
                  <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                    offer.is_active
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-gray-100 text-gray-500'
                  }`}>
                    {offer.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>
                <td className="px-4 py-3 text-right">
                  <div className="flex items-center justify-end gap-2">
                    <button
                      onClick={() => setModal(offer)}
                      className="p-1.5 rounded-lg hover:bg-accent transition-colors text-muted-foreground hover:text-foreground"
                    >
                      <Pencil className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => setDel(offer.id)}
                      className="p-1.5 rounded-lg hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-500"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {lastPage > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-border">
            <p className="text-sm text-muted-foreground">Halaman {page} dari {lastPage}</p>
            <div className="flex gap-2">
              <button onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page === 1}
                className="p-1.5 rounded-lg border border-border hover:bg-accent disabled:opacity-40 transition-colors">
                <ChevronLeft className="w-4 h-4" />
              </button>
              <button onClick={() => setPage((p) => Math.min(lastPage, p + 1))} disabled={page === lastPage}
                className="p-1.5 rounded-lg border border-border hover:bg-accent disabled:opacity-40 transition-colors">
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Offer form modal */}
      {modal !== null && (
        <OfferFormModal
          offer={modal === 'new' ? null : modal}
          onClose={() => setModal(null)}
          token={token!}
        />
      )}

      {/* Delete confirm */}
      {deletingId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="bg-card rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
            <h3 className="font-bold text-foreground">Hapus Offer?</h3>
            <p className="text-sm text-muted-foreground">
              Offer yang dihapus tidak dapat dikembalikan.
            </p>
            <div className="flex gap-3">
              <button onClick={() => setDel(null)}
                className="flex-1 py-2.5 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors">
                Batal
              </button>
              <button
                onClick={() => deleteOffer.mutate(deletingId!)}
                disabled={deleteOffer.isPending}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-red-500 text-white text-sm font-semibold rounded-lg hover:bg-red-600 transition-colors disabled:opacity-60"
              >
                {deleteOffer.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
                Hapus
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
