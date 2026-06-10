'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useMutation } from '@tanstack/react-query'
import {
  Ticket, ChevronLeft, Save, Loader2, Plus, X,
  Info, MapPin, Star, ImageIcon,
} from 'lucide-react'
import { ImageUpload, GalleryUpload } from '@/components/shared/ImageUpload'
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { cn } from '@/lib/utils'

// ── Types ─────────────────────────────────────────────────────────────────────

export type ProductFormData = {
  name: string
  slug: string
  description: string
  location: string
  opening_hours: string
  meeting_point: string
  instant_confirmation: boolean
  highlights: string[]
  usage_instructions: string
  cancellation_policy: string
  terms_conditions: string
  cloudinary_image_url: string
  cloudinary_thumbnail_url: string
  cloudinary_gallery_urls: string[]
  is_active: boolean
}

export const emptyProduct: ProductFormData = {
  name: '', slug: '', description: '',
  location: '', opening_hours: '', meeting_point: '',
  instant_confirmation: true,
  highlights: [],
  usage_instructions: '', cancellation_policy: '', terms_conditions: '',
  cloudinary_image_url: '', cloudinary_thumbnail_url: '',
  cloudinary_gallery_urls: [],
  is_active: true,
}

export function slugify(text: string) {
  return text.toLowerCase().trim()
    .replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '')
}

// ── Shared UI ─────────────────────────────────────────────────────────────────

export function Field({ label, hint, required, children }: {
  label: string; hint?: string; required?: boolean; children: React.ReactNode
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-foreground mb-1">
        {label}{required && <span className="text-destructive ml-0.5">*</span>}
      </label>
      {hint && <p className="text-xs text-muted-foreground mb-1.5">{hint}</p>}
      {children}
    </div>
  )
}

export const inputCls = 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition'

export function Toggle({ checked, onChange, label, description }: {
  checked: boolean; onChange: () => void; label: string; description?: string
}) {
  return (
    <div className="flex items-center justify-between">
      <div>
        <p className="text-sm font-medium text-foreground">{label}</p>
        {description && <p className="text-xs text-muted-foreground mt-0.5">{description}</p>}
      </div>
      <button type="button" role="switch" aria-checked={checked} onClick={onChange}
        className={cn('relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
          checked ? 'bg-emerald-500' : 'bg-gray-200')}>
        <span className={cn('inline-block h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-200',
          checked ? 'translate-x-5' : 'translate-x-0')} />
      </button>
    </div>
  )
}

export function DynamicList({ label, hint, items, onChange, placeholder }: {
  label: string; hint?: string; items: string[]
  onChange: (items: string[]) => void; placeholder?: string
}) {
  return (
    <Field label={label} hint={hint}>
      <div className="space-y-2">
        {items.map((item, i) => (
          <div key={i} className="flex items-center gap-2">
            <input type="text" value={item} placeholder={placeholder} className={inputCls}
              onChange={e => {
                const next = [...items]; next[i] = e.target.value; onChange(next)
              }} />
            <button type="button" onClick={() => onChange(items.filter((_, j) => j !== i))}
              className="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors shrink-0">
              <X size={15} />
            </button>
          </div>
        ))}
        <button type="button" onClick={() => onChange([...items, ''])}
          className="flex items-center gap-1.5 text-xs text-primary hover:text-primary/80 transition-colors">
          <Plus size={13} /> Tambah item
        </button>
      </div>
    </Field>
  )
}

// ── Sections nav ──────────────────────────────────────────────────────────────

const SECTIONS = [
  { key: 'info',   label: 'Informasi Dasar',     icon: Info },
  { key: 'lokasi', label: 'Lokasi & Operasional', icon: MapPin },
  { key: 'policy', label: 'Sorotan & Kebijakan',  icon: Star },
  { key: 'gambar', label: 'Gambar Produk',         icon: ImageIcon },
] as const

type SectionKey = typeof SECTIONS[number]['key']

// ── Page ──────────────────────────────────────────────────────────────────────

export default function NewProductPage() {
  const token  = useAdminAuthStore(s => s.token)
  const router = useRouter()

  const [form, setForm]             = useState<ProductFormData>({ ...emptyProduct })
  const [slugEdited, setSlugEdited] = useState(false)
  const [error, setError]           = useState('')
  const [activeSection, setActive]  = useState<SectionKey>('info')

  const set = (key: keyof ProductFormData) => (v: any) =>
    setForm(prev => ({ ...prev, [key]: v }))

  const mutation = useMutation({
    mutationFn: (payload: ProductFormData) =>
      api.post<any>('/admin/products', payload, { token: token! }),
    onSuccess: (res) => {
      const id = res?.data?.id
      router.push(id ? `/products/${id}` : '/products')
    },
    onError: (err) => setError(err instanceof Error ? err.message : 'Gagal menyimpan produk.'),
  })

  const sections: Record<SectionKey, { title: string; description: string; content: React.ReactNode }> = {
    info: {
      title:       'Informasi Dasar',
      description: 'Nama, slug, deskripsi, dan pengaturan umum produk',
      content: (
        <div className="space-y-5">
          <Field label="Nama Produk" required>
            <input type="text" value={form.name} required
              onChange={e => {
                const v = e.target.value
                setForm(prev => ({ ...prev, name: v, slug: slugEdited ? prev.slug : slugify(v) }))
              }}
              placeholder="contoh: Safari Legend (Selasa - Minggu)" className={inputCls} />
          </Field>

          <Field label="Slug" hint={`URL: /products/${form.slug || 'slug-produk'}`}>
            <input type="text" value={form.slug}
              onChange={e => { setSlugEdited(true); set('slug')(e.target.value) }}
              placeholder="safari-legend-selasa-minggu" className={cn(inputCls, 'font-mono')} />
          </Field>

          <Field label="Deskripsi">
            <textarea value={form.description} onChange={e => set('description')(e.target.value)}
              rows={4} placeholder="Deskripsikan produk wisata ini..."
              className={cn(inputCls, 'resize-none')} />
          </Field>

          <div className="pt-1 space-y-4 border-t border-border">
            <Toggle label="Status Aktif" description="Produk aktif akan tampil di storefront"
              checked={form.is_active} onChange={() => set('is_active')(!form.is_active)} />
            <Toggle label="Konfirmasi Instan" description='Tampilkan badge "Konfirmasi Instan" pada produk'
              checked={form.instant_confirmation} onChange={() => set('instant_confirmation')(!form.instant_confirmation)} />
          </div>
        </div>
      ),
    },

    lokasi: {
      title:       'Lokasi & Operasional',
      description: 'Alamat, jam operasional, dan titik pertemuan peserta',
      content: (
        <div className="space-y-5">
          <Field label="Alamat / Lokasi" hint="Alamat lengkap venue wisata">
            <textarea value={form.location} onChange={e => set('location')(e.target.value)}
              rows={3} placeholder="Jl. Prof. Dr. Ida Bagus Mantra No.KM 19, Serongga, Kec. Gianyar..."
              className={cn(inputCls, 'resize-none')} />
          </Field>

          <Field label="Jam Operasional">
            <input type="text" value={form.opening_hours}
              onChange={e => set('opening_hours')(e.target.value)}
              placeholder="Buka 09.00 - 17.00 WITA" className={inputCls} />
          </Field>

          <Field label="Meeting Point" hint="Titik pertemuan untuk peserta">
            <textarea value={form.meeting_point} onChange={e => set('meeting_point')(e.target.value)}
              rows={3} placeholder="Lobby utama / Ticketing Counter..."
              className={cn(inputCls, 'resize-none')} />
          </Field>
        </div>
      ),
    },

    policy: {
      title:       'Sorotan & Kebijakan',
      description: 'Highlights, cara penggunaan, kebijakan pembatalan, dan syarat ketentuan',
      content: (
        <div className="space-y-5">
          <DynamicList label="Sorotan (Highlights)"
            hint="Poin-poin unggulan yang ditampilkan di halaman produk"
            items={form.highlights} onChange={set('highlights')}
            placeholder="contoh: Saksikan pertunjukan spektakuler Bali Agung" />

          <Field label="Cara Penggunaan" hint="Instruksi cara menggunakan tiket">
            <textarea value={form.usage_instructions} onChange={e => set('usage_instructions')(e.target.value)}
              rows={3} placeholder="Show Mobile Ticket; Ticketing Counter"
              className={cn(inputCls, 'resize-none')} />
          </Field>

          <Field label="Kebijakan Pembatalan">
            <textarea value={form.cancellation_policy} onChange={e => set('cancellation_policy')(e.target.value)}
              rows={3} placeholder="No Cancellation and Non-refundable"
              className={cn(inputCls, 'resize-none')} />
          </Field>

          <Field label="Syarat & Ketentuan">
            <textarea value={form.terms_conditions} onChange={e => set('terms_conditions')(e.target.value)}
              rows={5} placeholder={'Anak 3-12 Tahun, Gratis untuk usia dibawah 3 tahun\nDapat dipesan di hari yang sama...'}
              className={cn(inputCls, 'resize-none')} />
          </Field>
        </div>
      ),
    },

    gambar: {
      title:       'Gambar Produk',
      description: 'Gambar utama, thumbnail, dan galeri untuk halaman produk',
      content: (
        <div className="space-y-6">

          {/* ── Main + Thumbnail row ── */}
          <div className="grid grid-cols-3 gap-5 items-start">
            <div className="col-span-2 space-y-1.5">
              <p className="text-sm font-medium text-foreground">Gambar Utama</p>
              <p className="text-xs text-muted-foreground">Resolusi disarankan 1920×1080px</p>
              <ImageUpload
                value={form.cloudinary_image_url}
                onChange={set('cloudinary_image_url')}
                onThumbnail={set('cloudinary_thumbnail_url')}
                token={token!}
                aspectRatio="video"
              />
            </div>

            <div className="space-y-1.5">
              <p className="text-sm font-medium text-foreground">Thumbnail</p>
              <p className="text-xs text-muted-foreground">Auto-diisi · 400×300px</p>
              <ImageUpload
                value={form.cloudinary_thumbnail_url}
                onChange={set('cloudinary_thumbnail_url')}
                token={token!}
                aspectRatio="4/3"
              />
            </div>
          </div>

          <div className="border-t border-border" />

          {/* ── Gallery ── */}
          <GalleryUpload
            items={form.cloudinary_gallery_urls}
            onChange={set('cloudinary_gallery_urls')}
            token={token!}
          />
        </div>
      ),
    },
  }

  const active = sections[activeSection]

  return (
    <div className="space-y-6">

      {/* ── Header ── */}
      <div>
        <button onClick={() => router.back()}
          className="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground mb-4 transition-colors">
          <ChevronLeft size={16} /> Kembali ke Produk
        </button>
        <div className="flex items-center gap-3">
          <Ticket size={22} className="text-muted-foreground" />
          <h1 className="text-2xl font-bold text-foreground">Tambah Produk</h1>
        </div>
      </div>

      {/* ── Body ── */}
      <div className="flex gap-6 items-start">

        {/* Sidebar */}
        <nav className="w-52 shrink-0 rounded-xl border border-border bg-card overflow-hidden">
          {SECTIONS.map(({ key, label, icon: Icon }, i) => (
            <button
              key={key}
              type="button"
              onClick={() => setActive(key)}
              className={cn(
                'w-full flex items-center gap-3 px-4 py-3 text-sm text-left transition-colors',
                i < SECTIONS.length - 1 && 'border-b border-border',
                activeSection === key
                  ? 'bg-primary/10 text-primary font-semibold'
                  : 'text-muted-foreground hover:bg-muted/40 hover:text-foreground',
              )}
            >
              <Icon size={15} className="shrink-0" />
              {label}
            </button>
          ))}
        </nav>

        {/* Content card */}
        <div className="flex-1 min-w-0">
          <form
            onSubmit={e => { e.preventDefault(); setError(''); mutation.mutate(form) }}
            className="rounded-xl border border-border bg-card"
          >
            <div className="px-6 py-5 border-b border-border">
              <h2 className="text-base font-semibold text-foreground">{active.title}</h2>
              <p className="text-sm text-muted-foreground mt-0.5">{active.description}</p>
            </div>

            <div className="px-6 py-6">
              {active.content}
            </div>

            <div className="px-6 py-4 border-t border-border bg-muted/20 flex items-center justify-between">
              {error
                ? <p className="text-sm text-destructive">{error}</p>
                : <span />
              }
              <button type="submit" disabled={mutation.isPending}
                className="flex items-center gap-2 px-5 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
                {mutation.isPending
                  ? <Loader2 size={14} className="animate-spin" />
                  : <Save size={14} />
                }
                Simpan & Atur Varian
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  )
}
