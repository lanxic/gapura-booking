'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useAdminAuthStore } from '@/store/auth'
import { ArrowLeft, Plus, Trash2, Loader2 } from 'lucide-react'
import Link from 'next/link'

type AddonForm = { name: string; price: string; unit: string; max_qty: string }

type ActivityFormData = {
  name: string
  category: string
  description: string
  duration_minutes: string
  min_pax: string
  max_pax: string
  level: string
  min_age: string
  base_price: string
  status: string
  meta_meeting_point: string
  meta_what_to_bring: string
  addons: AddonForm[]
}

const EMPTY_ADDON: AddonForm = { name: '', price: '', unit: 'orang', max_qty: '1' }

const LEVELS    = ['all', 'beginner', 'intermediate', 'advanced']
const CATEGORIES = ['wellness', 'culinary', 'outdoor', 'art', 'sport', 'water', 'education', 'other']

export default function NewActivityPage() {
  const router = useRouter()
  const { token } = useAdminAuthStore()

  const [form, setForm] = useState<ActivityFormData>({
    name: '', category: 'wellness', description: '',
    duration_minutes: '60', min_pax: '1', max_pax: '20',
    level: 'all', min_age: '', base_price: '',
    status: 'active', meta_meeting_point: '', meta_what_to_bring: '',
    addons: [],
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

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
        addons: form.addons
          .filter((a) => a.name.trim())
          .map((a) => ({
            name:    a.name,
            price:   parseFloat(a.price.replace(/[^0-9]/g, '')) || 0,
            unit:    a.unit,
            max_qty: parseInt(a.max_qty) || 1,
          })),
      }
      return api.post('/admin/activities', payload, { token: token! })
    },
    onSuccess: () => router.push('/activities'),
    onError: (err: Error) => {
      try {
        const parsed = JSON.parse(err.message)
        setErrors(parsed.errors ?? {})
      } catch {
        setErrors({ _: err.message })
      }
    },
  })

  function set(key: keyof ActivityFormData, value: string) {
    setForm((f) => ({ ...f, [key]: value }))
    setErrors((e) => { const next = { ...e }; delete next[key]; return next })
  }

  function addAddon() {
    setForm((f) => ({ ...f, addons: [...f.addons, { ...EMPTY_ADDON }] }))
  }

  function removeAddon(i: number) {
    setForm((f) => ({ ...f, addons: f.addons.filter((_, idx) => idx !== i) }))
  }

  function setAddon(i: number, key: keyof AddonForm, value: string) {
    setForm((f) => {
      const addons = [...f.addons]
      addons[i] = { ...addons[i], [key]: value }
      return { ...f, addons }
    })
  }

  const err = (k: string) => errors[k]
    ? <p className="mt-1 text-xs text-red-500">{errors[k]}</p>
    : null

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Link href="/activities" className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <ArrowLeft className="w-5 h-5" />
        </Link>
        <div>
          <h1 className="text-xl font-bold text-foreground">Tambah Aktivitas</h1>
          <p className="text-sm text-muted-foreground">Buat aktivitas baru</p>
        </div>
      </div>

      {errors._ && (
        <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-600">
          {errors._}
        </div>
      )}

      <form
        onSubmit={(e) => { e.preventDefault(); mutation.mutate() }}
        className="space-y-6"
      >
        {/* Basic info */}
        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Informasi Dasar</h2>

          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Nama Aktivitas *</label>
            <input
              value={form.name}
              onChange={(e) => set('name', e.target.value)}
              placeholder="Yoga Morning Flow"
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
            />
            {err('name')}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Kategori *</label>
              <select
                value={form.category}
                onChange={(e) => set('category', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                {CATEGORIES.map((c) => (
                  <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Level</label>
              <select
                value={form.level}
                onChange={(e) => set('level', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
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
            <textarea
              value={form.description}
              onChange={(e) => set('description', e.target.value)}
              rows={4}
              placeholder="Ceritakan tentang aktivitas ini..."
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"
            />
          </div>
        </section>

        {/* Capacity & Pricing */}
        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Kapasitas &amp; Harga</h2>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Durasi (menit) *</label>
              <input
                type="number" min="1"
                value={form.duration_minutes}
                onChange={(e) => set('duration_minutes', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Min Pax *</label>
              <input
                type="number" min="1"
                value={form.min_pax}
                onChange={(e) => set('min_pax', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Max Pax *</label>
              <input
                type="number" min="1"
                value={form.max_pax}
                onChange={(e) => set('max_pax', e.target.value)}
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Harga Dasar (Rp) *</label>
              <input
                value={form.base_price}
                onChange={(e) => set('base_price', e.target.value)}
                placeholder="150000"
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
              {err('base_price')}
            </div>
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Usia Minimum</label>
              <input
                type="number" min="0"
                value={form.min_age}
                onChange={(e) => set('min_age', e.target.value)}
                placeholder="tidak ada batasan"
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
          </div>
        </section>

        {/* Meta */}
        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <h2 className="font-semibold text-foreground">Informasi Tambahan</h2>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Meeting Point</label>
            <input
              value={form.meta_meeting_point}
              onChange={(e) => set('meta_meeting_point', e.target.value)}
              placeholder="Lobby hotel, pintu utama..."
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-foreground mb-1">Yang Perlu Dibawa</label>
            <textarea
              value={form.meta_what_to_bring}
              onChange={(e) => set('meta_what_to_bring', e.target.value)}
              rows={2}
              placeholder="Baju olahraga, botol minum..."
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"
            />
          </div>
        </section>

        {/* Add-ons */}
        <section className="rounded-xl border border-border bg-card p-5 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="font-semibold text-foreground">Add-on (opsional)</h2>
            <button
              type="button"
              onClick={addAddon}
              className="flex items-center gap-1.5 text-sm font-medium text-primary hover:text-primary/80 transition-colors"
            >
              <Plus className="w-4 h-4" /> Tambah Add-on
            </button>
          </div>

          {form.addons.length === 0 && (
            <p className="text-sm text-muted-foreground">Belum ada add-on.</p>
          )}

          {form.addons.map((addon, i) => (
            <div key={i} className="grid grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end">
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Nama</label>
                <input
                  value={addon.name}
                  onChange={(e) => setAddon(i, 'name', e.target.value)}
                  placeholder="Sewa matras"
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20"
                />
              </div>
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Harga (Rp)</label>
                <input
                  value={addon.price}
                  onChange={(e) => setAddon(i, 'price', e.target.value)}
                  placeholder="25000"
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20"
                />
              </div>
              <div>
                <label className="block text-xs text-muted-foreground mb-1">Max Qty</label>
                <input
                  type="number" min="1"
                  value={addon.max_qty}
                  onChange={(e) => setAddon(i, 'max_qty', e.target.value)}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary/20"
                />
              </div>
              <button
                type="button"
                onClick={() => removeAddon(i)}
                className="p-2 rounded-lg text-muted-foreground hover:text-red-500 hover:bg-red-50 transition-colors"
              >
                <Trash2 className="w-4 h-4" />
              </button>
            </div>
          ))}
        </section>

        {/* Status & Submit */}
        <section className="rounded-xl border border-border bg-card p-5">
          <div className="flex items-center justify-between">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1">Status</label>
              <select
                value={form.status}
                onChange={(e) => set('status', e.target.value)}
                className="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
            <div className="flex items-center gap-3">
              <Link
                href="/activities"
                className="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
              >
                Batal
              </Link>
              <button
                type="submit"
                disabled={mutation.isPending}
                className="flex items-center gap-2 px-5 py-2.5 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-60"
              >
                {mutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
                Simpan Aktivitas
              </button>
            </div>
          </div>
        </section>
      </form>
    </div>
  )
}
