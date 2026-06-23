'use client'

import { use, useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useAdminAuthStore } from '@/store/auth'
import { ArrowLeft, Plus, Trash2, Loader2, Calendar } from 'lucide-react'
import Link from 'next/link'

type AddonForm = { id?: number; name: string; price: string; unit: string; max_qty: string; is_active: boolean }

const LEVELS    = ['all', 'beginner', 'intermediate', 'advanced']
const CATEGORIES = ['wellness', 'culinary', 'outdoor', 'art', 'sport', 'water', 'education', 'other']

export default function EditActivityPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const router  = useRouter()
  const qc      = useQueryClient()
  const { token } = useAdminAuthStore()

  const { data, isLoading } = useQuery({
    queryKey: ['activity-admin', id],
    queryFn: () => api.get<{ data: Record<string, unknown> }>(`/admin/activities/${id}`, { token: token! }),
    enabled: !!token,
  })

  const [form, setForm] = useState<Record<string, string>>({})
  const [addons, setAddons] = useState<AddonForm[]>([])
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [loaded, setLoaded] = useState(false)

  useEffect(() => {
    if (data?.data && !loaded) {
      const a = data.data as Record<string, unknown>
      setForm({
        name:               String(a.name ?? ''),
        category:           String(a.category ?? 'wellness'),
        description:        String(a.description ?? ''),
        duration_minutes:   String(a.duration_minutes ?? 60),
        min_pax:            String(a.min_pax ?? 1),
        max_pax:            String(a.max_pax ?? 20),
        level:              String(a.level ?? 'all'),
        min_age:            a.min_age != null ? String(a.min_age) : '',
        base_price:         String(a.base_price ?? ''),
        status:             String(a.status ?? 'active'),
        meta_meeting_point: String((a.meta as Record<string, unknown>)?.meeting_point ?? ''),
        meta_what_to_bring: String((a.meta as Record<string, unknown>)?.what_to_bring ?? ''),
      })
      setAddons(
        ((a.addons as unknown[]) ?? []).map((addon) => {
          const ad = addon as Record<string, unknown>
          return {
            id:        ad.id as number,
            name:      String(ad.name ?? ''),
            price:     String(ad.price ?? ''),
            unit:      String(ad.unit ?? 'orang'),
            max_qty:   String(ad.max_qty ?? 1),
            is_active: Boolean(ad.is_active ?? true),
          }
        })
      )
      setLoaded(true)
    }
  }, [data, loaded])

  const mutation = useMutation({
    mutationFn: async () => {
      const payload = {
        name:             form.name,
        category:         form.category,
        description:      form.description,
        duration_minutes: parseInt(form.duration_minutes) || 60,
        min_pax:          parseInt(form.min_pax) || 1,
        max_pax:          parseInt(form.max_pax) || 20,
        level:            form.level,
        min_age:          form.min_age ? parseInt(form.min_age) : null,
        base_price:       parseFloat(form.base_price.replace(/[^0-9]/g, '')) || 0,
        status:           form.status,
        meta: {
          meeting_point:  form.meta_meeting_point,
          what_to_bring:  form.meta_what_to_bring,
        },
        addons: addons.filter((a) => a.name.trim()).map((a) => ({
          id:        a.id,
          name:      a.name,
          price:     parseFloat(a.price.replace(/[^0-9]/g, '')) || 0,
          unit:      a.unit,
          max_qty:   parseInt(a.max_qty) || 1,
          is_active: a.is_active,
        })),
      }
      return api.put(`/admin/activities/${id}`, payload, { token: token! })
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['activities-admin'] })
      router.push('/activities')
    },
    onError: (err: Error) => {
      try { setErrors(JSON.parse(err.message).errors ?? {}) } catch { setErrors({ _: err.message }) }
    },
  })

  // Generate slots mutation
  const genSlots = useMutation({
    mutationFn: () => api.post(`/admin/activities/${id}/generate-slots`, { days: 30 }, { token: token! }),
    onSuccess: () => alert('Slot berhasil di-generate untuk 30 hari ke depan!'),
    onError: (e: Error) => alert(e.message),
  })

  function setField(key: string, value: string) {
    setForm((f) => ({ ...f, [key]: value }))
    setErrors((e) => { const n = { ...e }; delete n[key]; return n })
  }

  function addAddon() {
    setAddons((a) => [...a, { name: '', price: '', unit: 'orang', max_qty: '1', is_active: true }])
  }

  function removeAddon(i: number) {
    setAddons((a) => a.filter((_, idx) => idx !== i))
  }

  function setAddonField(i: number, key: keyof AddonForm, value: string | boolean) {
    setAddons((a) => { const n = [...a]; n[i] = { ...n[i], [key]: value }; return n })
  }

  const err = (k: string) => errors[k]
    ? <p className="mt-1 text-xs text-red-500">{errors[k]}</p>
    : null

  if (isLoading || !loaded) {
    return (
      <div className="max-w-2xl mx-auto space-y-4">
        <div className="h-8 bg-gray-200 rounded w-1/3 animate-pulse" />
        <div className="h-64 bg-gray-100 rounded-xl animate-pulse" />
      </div>
    )
  }

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Link href="/activities" className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div>
            <h1 className="text-xl font-bold text-foreground">Edit Aktivitas</h1>
            <p className="text-sm text-muted-foreground">{form.name}</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Link
            href={`/activities/${id}/slots`}
            className="flex items-center gap-1.5 px-3 py-2 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors"
          >
            <Calendar className="w-4 h-4" /> Kelola Slot
          </Link>
          <button
            onClick={() => genSlots.mutate()}
            disabled={genSlots.isPending}
            className="flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-primary/10 text-primary rounded-lg hover:bg-primary/20 transition-colors"
          >
            {genSlots.isPending && <Loader2 className="w-3 h-3 animate-spin" />}
            Generate Slot 30 Hari
          </button>
        </div>
      </div>

      {errors._ && (
        <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600">
          {errors._}
        </div>
      )}

      <form onSubmit={(e) => { e.preventDefault(); mutation.mutate() }} className="space-y-6">
        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Informasi Dasar</h2>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Nama Aktivitas *</label>
            <input value={form.name} onChange={(e) => setField('name', e.target.value)}
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            {err('name')}
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Kategori</label>
              <select value={form.category} onChange={(e) => setField('category', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                {CATEGORIES.map((c) => <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Level</label>
              <select value={form.level} onChange={(e) => setField('level', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                {LEVELS.map((l) => (
                  <option key={l} value={l}>
                    {{ all: 'Semua Level', beginner: 'Pemula', intermediate: 'Menengah', advanced: 'Mahir' }[l] ?? l}
                  </option>
                ))}
              </select>
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Deskripsi</label>
            <textarea value={form.description} onChange={(e) => setField('description', e.target.value)}
              rows={4} className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none" />
          </div>
        </section>

        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Kapasitas &amp; Harga</h2>
          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Durasi (menit)</label>
              <input type="number" min="1" value={form.duration_minutes} onChange={(e) => setField('duration_minutes', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Min Pax</label>
              <input type="number" min="1" value={form.min_pax} onChange={(e) => setField('min_pax', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Max Pax</label>
              <input type="number" min="1" value={form.max_pax} onChange={(e) => setField('max_pax', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Harga Dasar (Rp)</label>
              <input value={form.base_price} onChange={(e) => setField('base_price', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Usia Minimum</label>
              <input type="number" min="0" value={form.min_age} onChange={(e) => setField('min_age', e.target.value)}
                placeholder="tidak ada batasan"
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
            </div>
          </div>
        </section>

        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Informasi Tambahan</h2>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Meeting Point</label>
            <input value={form.meta_meeting_point} onChange={(e) => setField('meta_meeting_point', e.target.value)}
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20" />
          </div>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Yang Perlu Dibawa</label>
            <textarea value={form.meta_what_to_bring} onChange={(e) => setField('meta_what_to_bring', e.target.value)}
              rows={2} className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none" />
          </div>
        </section>

        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="font-semibold text-foreground">Add-on</h2>
            <button type="button" onClick={addAddon}
              className="flex items-center gap-1.5 text-sm font-medium text-primary hover:text-primary/80 transition-colors">
              <Plus className="w-4 h-4" /> Tambah
            </button>
          </div>
          {addons.length === 0 && <p className="text-sm text-muted-foreground">Belum ada add-on.</p>}
          {addons.map((addon, i) => (
            <div key={i} className="grid grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end">
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Nama</label>
                <input value={addon.name} onChange={(e) => setAddonField(i, 'name', e.target.value)}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20" />
              </div>
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Harga (Rp)</label>
                <input value={addon.price} onChange={(e) => setAddonField(i, 'price', e.target.value)}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20" />
              </div>
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Max Qty</label>
                <input type="number" min="1" value={addon.max_qty} onChange={(e) => setAddonField(i, 'max_qty', e.target.value)}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20" />
              </div>
              <button type="button" onClick={() => removeAddon(i)}
                className="p-2 rounded-lg text-muted-foreground hover:text-red-500 hover:bg-red-50 transition-colors">
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          ))}
        </section>

        <section className="rounded-xl border border-border bg-card p-5">
          <div className="flex items-center justify-between">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Status</label>
              <select value={form.status} onChange={(e) => setField('status', e.target.value)}
                className="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
                <option value="archived">Arsip</option>
              </select>
            </div>
            <div className="flex items-center gap-3">
              <Link href="/activities" className="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                Batal
              </Link>
              <button type="submit" disabled={mutation.isPending}
                className="flex items-center gap-2 px-5 py-2.5 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-60">
                {mutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
                Simpan Perubahan
              </button>
            </div>
          </div>
        </section>
      </form>
    </div>
  )
}
