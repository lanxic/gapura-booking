'use client'

import { useRef, useState, DragEvent, ChangeEvent } from 'react'
import { Upload, X, Loader2, Plus, Trash2, AlertCircle, ImageIcon } from 'lucide-react'
import { cn } from '@/lib/utils'
import { api } from '@/lib/api'

type UploadResult = {
  image_url: string
  thumbnail_url: string
  public_id: string
}

// ── ImageUpload ───────────────────────────────────────────────────────────────

type ImageUploadProps = {
  value: string
  onChange: (url: string) => void
  onThumbnail?: (url: string) => void
  token: string
  aspectRatio?: 'video' | '4/3' | 'square'
  hint?: string
}

export function ImageUpload({
  value, onChange, onThumbnail, token,
  aspectRatio = 'video', hint,
}: ImageUploadProps) {
  const [dragging, setDragging]   = useState(false)
  const [uploading, setUploading] = useState(false)
  const [error, setError]         = useState('')
  const inputRef = useRef<HTMLInputElement>(null)

  async function upload(file: File) {
    if (!file.type.startsWith('image/')) { setError('File harus berupa gambar.'); return }
    if (file.size > 5 * 1024 * 1024)    { setError('Ukuran maksimal 5 MB.');     return }
    setError('')
    setUploading(true)
    try {
      const fd = new FormData()
      fd.append('file', file)
      const res = await api.upload<UploadResult>('/admin/products/upload-image', fd, { token })
      onChange(res.image_url)
      onThumbnail?.(res.thumbnail_url)
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Upload gagal.')
    } finally {
      setUploading(false)
    }
  }

  function onDrop(e: DragEvent) {
    e.preventDefault(); setDragging(false)
    const file = e.dataTransfer.files[0]
    if (file) upload(file)
  }

  function onFileChange(e: ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0]
    if (file) upload(file)
    e.target.value = ''
  }

  const ratioClass = aspectRatio === 'square' ? 'aspect-square'
    : aspectRatio === '4/3'  ? 'aspect-[4/3]'
    : 'aspect-video'

  return (
    <div className="space-y-2.5">
      <div
        onClick={() => !uploading && inputRef.current?.click()}
        onDragOver={e => { e.preventDefault(); setDragging(true) }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={cn(
          ratioClass,
          'relative w-full rounded-xl overflow-hidden transition-all duration-200 cursor-pointer group',
          value
            ? 'border border-border shadow-sm'
            : cn(
                'border-2 border-dashed',
                dragging
                  ? 'border-primary bg-primary/5 scale-[0.99]'
                  : 'border-border/70 bg-muted/20 hover:border-primary/50 hover:bg-muted/40',
              ),
          uploading && 'pointer-events-none',
        )}
      >
        {value ? (
          <>
            <img src={value} alt="Preview" className="w-full h-full object-cover" />

            {/* Gradient overlay on hover */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex flex-col justify-end p-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-1.5 bg-white/95 text-foreground rounded-lg px-3 py-1.5 text-xs font-medium shadow-sm">
                  <Upload size={11} strokeWidth={2.5} />
                  Ganti gambar
                </div>
                <button
                  type="button"
                  onClick={e => { e.stopPropagation(); onChange('') }}
                  className="bg-destructive text-white rounded-lg p-1.5 shadow-sm hover:bg-destructive/90 transition-colors"
                >
                  <Trash2 size={13} strokeWidth={2} />
                </button>
              </div>
            </div>
          </>
        ) : (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6">
            <div className={cn(
              'w-11 h-11 rounded-full flex items-center justify-center transition-colors duration-200',
              dragging ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground',
            )}>
              {uploading
                ? <Loader2 size={20} className="animate-spin" />
                : <Upload size={18} strokeWidth={2} />}
            </div>

            <div className="text-center space-y-1">
              <p className="text-sm font-medium text-foreground">
                {uploading ? 'Mengunggah…'
                  : dragging ? 'Lepaskan di sini'
                  : 'Seret & letakkan gambar'}
              </p>
              {!uploading && (
                <p className="text-xs text-muted-foreground">
                  atau <span className="text-primary font-medium">klik untuk memilih</span>
                </p>
              )}
            </div>

            {!uploading && (
              <div className="flex items-center gap-1.5">
                {['JPG', 'PNG', 'WEBP'].map(f => (
                  <span key={f} className="px-2 py-0.5 rounded-md bg-muted text-muted-foreground text-[10px] font-medium">{f}</span>
                ))}
                <span className="text-muted-foreground/50 text-[10px]">· maks. 5 MB</span>
              </div>
            )}
          </div>
        )}

        {/* Uploading overlay when replacing existing image */}
        {uploading && value && (
          <div className="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex flex-col items-center justify-center gap-2">
            <Loader2 size={26} className="text-white animate-spin" />
            <p className="text-xs text-white/80 font-medium">Mengunggah…</p>
          </div>
        )}
      </div>

      {error && (
        <div className="flex items-center gap-2 text-xs text-destructive bg-destructive/5 border border-destructive/20 rounded-lg px-3 py-2">
          <AlertCircle size={13} className="shrink-0" />
          {error}
        </div>
      )}

      {hint && <p className="text-xs text-muted-foreground">{hint}</p>}

      {/* Secondary URL input */}
      <input
        type="text"
        value={value}
        onChange={e => onChange(e.target.value)}
        placeholder="Atau tempel URL gambar…"
        className="w-full rounded-lg border border-input bg-muted/30 px-3 py-2 text-xs text-muted-foreground placeholder:text-muted-foreground/40 focus:outline-none focus:ring-2 focus:ring-ring focus:bg-background transition"
      />

      <input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={onFileChange} />
    </div>
  )
}

// ── GalleryUpload ─────────────────────────────────────────────────────────────

type GalleryUploadProps = {
  items: string[]
  onChange: (items: string[]) => void
  token: string
}

function GalleryCard({
  value, onUpload, onRemove, uploading, error,
}: {
  value: string
  onUpload: (file: File) => void
  onRemove: () => void
  uploading: boolean
  error: string
}) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [dragging, setDragging] = useState(false)

  function onDrop(e: DragEvent) {
    e.preventDefault(); setDragging(false)
    const file = e.dataTransfer.files[0]
    if (file) onUpload(file)
  }

  return (
    <div className="space-y-1">
      <div
        onClick={() => !uploading && inputRef.current?.click()}
        onDragOver={e => { e.preventDefault(); setDragging(true) }}
        onDragLeave={() => setDragging(false)}
        onDrop={onDrop}
        className={cn(
          'relative aspect-square rounded-xl overflow-hidden transition-all duration-200 cursor-pointer group',
          value
            ? 'border border-border shadow-sm'
            : cn(
                'border-2 border-dashed',
                dragging
                  ? 'border-primary bg-primary/5 scale-[0.98]'
                  : error
                    ? 'border-destructive/40 bg-destructive/5'
                    : 'border-border/60 bg-muted/20 hover:border-primary/40 hover:bg-muted/40',
              ),
          uploading && 'pointer-events-none',
        )}
      >
        {value ? (
          <>
            <img src={value} alt="" className="w-full h-full object-cover" />
            <div className="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-colors duration-200 flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
              <button
                type="button"
                onClick={e => { e.stopPropagation(); inputRef.current?.click() }}
                className="w-8 h-8 rounded-full bg-white/90 flex items-center justify-center text-foreground hover:bg-white transition-colors shadow"
              >
                <Upload size={13} strokeWidth={2.5} />
              </button>
              <button
                type="button"
                onClick={e => { e.stopPropagation(); onRemove() }}
                className="w-8 h-8 rounded-full bg-destructive flex items-center justify-center text-white hover:bg-destructive/90 transition-colors shadow"
              >
                <Trash2 size={13} strokeWidth={2} />
              </button>
            </div>
          </>
        ) : (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-1.5 p-2">
            {uploading ? (
              <Loader2 size={18} className="text-muted-foreground animate-spin" />
            ) : (
              <>
                <div className={cn(
                  'w-8 h-8 rounded-full flex items-center justify-center transition-colors',
                  dragging ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground',
                )}>
                  <Upload size={14} strokeWidth={2} />
                </div>
                <span className="text-[10px] text-muted-foreground font-medium text-center leading-tight">
                  {dragging ? 'Lepaskan' : 'Upload'}
                </span>
              </>
            )}
          </div>
        )}

        {uploading && value && (
          <div className="absolute inset-0 bg-black/60 flex items-center justify-center">
            <Loader2 size={18} className="text-white animate-spin" />
          </div>
        )}

        <input
          ref={inputRef}
          type="file" accept="image/*" className="hidden"
          onChange={e => {
            const file = e.target.files?.[0]
            if (file) onUpload(file)
            e.target.value = ''
          }}
        />
      </div>

      {error && (
        <p className="text-[10px] text-destructive leading-tight px-0.5">{error}</p>
      )}
    </div>
  )
}

export function GalleryUpload({ items, onChange, token }: GalleryUploadProps) {
  const [uploading, setUploading] = useState<Record<number, boolean>>({})
  const [errors,    setErrors]    = useState<Record<number, string>>({})

  async function upload(index: number, file: File) {
    if (!file.type.startsWith('image/')) {
      setErrors(prev => ({ ...prev, [index]: 'Bukan gambar.' })); return
    }
    if (file.size > 5 * 1024 * 1024) {
      setErrors(prev => ({ ...prev, [index]: 'Maks. 5 MB.' })); return
    }
    setErrors(prev => ({ ...prev, [index]: '' }))
    setUploading(prev => ({ ...prev, [index]: true }))
    try {
      const fd = new FormData()
      fd.append('file', file)
      const res = await api.upload<UploadResult>('/admin/products/upload-image', fd, { token })
      const next = [...items]; next[index] = res.image_url; onChange(next)
    } catch (e) {
      setErrors(prev => ({ ...prev, [index]: e instanceof Error ? e.message : 'Gagal.' }))
    } finally {
      setUploading(prev => ({ ...prev, [index]: false }))
    }
  }

  function remove(index: number) {
    onChange(items.filter((_, i) => i !== index))
  }

  const filledCount = items.filter(Boolean).length

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-foreground">Galeri</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            Gambar carousel untuk halaman produk
            {filledCount > 0 && (
              <span className="ml-1.5 px-1.5 py-0.5 rounded-full bg-primary/10 text-primary text-[10px] font-semibold">
                {filledCount}
              </span>
            )}
          </p>
        </div>
        {items.length > 0 && (
          <button
            type="button"
            onClick={() => onChange([])}
            className="text-xs text-muted-foreground hover:text-destructive transition-colors"
          >
            Hapus semua
          </button>
        )}
      </div>

      {items.length === 0 ? (
        /* Empty state */
        <div
          onClick={() => onChange([''])}
          className="rounded-xl border-2 border-dashed border-border/60 bg-muted/20 hover:border-primary/40 hover:bg-muted/40 transition-all duration-200 cursor-pointer py-8 flex flex-col items-center gap-2.5"
        >
          <div className="w-10 h-10 rounded-full bg-muted flex items-center justify-center text-muted-foreground">
            <ImageIcon size={18} strokeWidth={1.5} />
          </div>
          <div className="text-center">
            <p className="text-sm font-medium text-foreground">Belum ada gambar galeri</p>
            <p className="text-xs text-muted-foreground mt-0.5">Klik untuk menambahkan gambar pertama</p>
          </div>
        </div>
      ) : (
        <div className="grid grid-cols-4 gap-3">
          {items.map((item, i) => (
            <GalleryCard
              key={i}
              value={item}
              uploading={!!uploading[i]}
              error={errors[i] ?? ''}
              onUpload={file => upload(i, file)}
              onRemove={() => remove(i)}
            />
          ))}

          {/* Add card */}
          <button
            type="button"
            onClick={() => onChange([...items, ''])}
            className="aspect-square rounded-xl border-2 border-dashed border-border/60 hover:border-primary/50 hover:bg-primary/5 flex flex-col items-center justify-center gap-1.5 text-muted-foreground hover:text-primary transition-all duration-200 group"
          >
            <div className="w-8 h-8 rounded-full bg-muted group-hover:bg-primary/10 flex items-center justify-center transition-colors">
              <Plus size={16} strokeWidth={2.5} />
            </div>
            <span className="text-[10px] font-medium">Tambah</span>
          </button>
        </div>
      )}
    </div>
  )
}
