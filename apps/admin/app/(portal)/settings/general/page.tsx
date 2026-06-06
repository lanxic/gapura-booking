'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { SlidersHorizontal, Upload, Save, Loader2, CheckCircle, AlertCircle, Eye, EyeOff, Wifi, Send, ChevronDown, X, Globe, CreditCard, Bell, Mail, Cloud } from 'lucide-react'
import { useState, useEffect, useRef } from 'react'
import { cn } from '@/lib/utils'

type Tab = 'umum' | 'pembayaran' | 'notifikasi' | 'email' | 'cloudinary'

const TABS: { id: Tab; label: string; icon: React.ElementType }[] = [
  { id: 'umum',       label: 'Umum',        icon: Globe },
  { id: 'pembayaran', label: 'Pembayaran',   icon: CreditCard },
  { id: 'notifikasi', label: 'Notifikasi',   icon: Bell },
  { id: 'email',      label: 'Email',        icon: Mail },
  { id: 'cloudinary', label: 'Cloudinary',   icon: Cloud },
]

const DP_OPTIONS = [30, 50, 70]

// ── Shared helpers ─────────────────────────────────────────────────────────

function Field({ label, hint, children }: { label: string; hint?: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="block text-sm font-medium text-foreground mb-0.5">{label}</label>
      {hint && <p className="text-xs text-muted-foreground mb-2">{hint}</p>}
      {children}
    </div>
  )
}

function TextInput({ value, onChange, placeholder, readOnly, type = 'text' }: {
  value: string; onChange?: (v: string) => void; placeholder?: string; readOnly?: boolean; type?: string
}) {
  return (
    <input
      type={type}
      value={value ?? ''}
      readOnly={readOnly}
      onChange={e => onChange?.(e.target.value)}
      placeholder={placeholder}
      className={cn(
        'w-full rounded-lg border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition',
        readOnly ? 'bg-muted text-muted-foreground cursor-not-allowed' : 'bg-background text-foreground',
      )}
    />
  )
}

function ToggleRow({ label, description, checked, onChange }: {
  label: string; description?: string; checked: boolean; onChange: () => void
}) {
  return (
    <div className="flex items-start justify-between gap-4">
      <div>
        <p className="text-sm font-medium text-foreground">{label}</p>
        {description && <p className="text-xs text-muted-foreground mt-0.5">{description}</p>}
      </div>
      <button type="button" role="switch" aria-checked={checked} onClick={onChange}
        className={cn(
          'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors',
          checked ? 'bg-emerald-500' : 'bg-gray-200',
        )}>
        <span className={cn(
          'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-md transition-transform duration-200',
          checked ? 'translate-x-5' : 'translate-x-0',
        )} />
      </button>
    </div>
  )
}

function ActionRow({ pending, saved, error }: { pending: boolean; saved: boolean; error?: string }) {
  return (
    <div className="flex items-center gap-4 pt-2">
      <button type="submit" disabled={pending}
        className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
        {pending ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
        Simpan
      </button>
      {saved && <span className="flex items-center gap-1.5 text-sm text-emerald-600"><CheckCircle size={14} /> Tersimpan</span>}
      {error  && <span className="flex items-center gap-1.5 text-sm text-destructive"><AlertCircle size={14} /> {error}</span>}
    </div>
  )
}

// ── Color picker ─────────────────────────────────────────────────────────────
function ColorPicker({ label, value, onChange }: {
  label: string; value: string; onChange: (v: string) => void
}) {
  const hex = /^#[0-9a-fA-F]{3,8}$/.test(value) ? value : '#163526'

  return (
    <div className="flex flex-col gap-1.5 min-w-0">
      <label className="text-xs font-medium text-muted-foreground">{label}</label>
      <div className="flex items-center gap-2 rounded-lg border border-input bg-background px-2.5 py-1.5">
        {/* Native color swatch — clicking opens the OS picker */}
        <div className="relative flex-shrink-0">
          <div
            className="w-7 h-7 rounded-md border border-border cursor-pointer shadow-sm"
            style={{ backgroundColor: hex }}
          />
          <input
            type="color"
            value={hex}
            onChange={e => onChange(e.target.value)}
            className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
          />
        </div>
        {/* Hex input */}
        <input
          type="text"
          value={value}
          onChange={e => onChange(e.target.value)}
          maxLength={9}
          spellCheck={false}
          placeholder="#000000"
          className="w-24 bg-transparent text-sm font-mono text-foreground focus:outline-none uppercase"
        />
      </div>
    </div>
  )
}

// ── Compact inline upload (logo / favicon) ────────────────────────────────────
function InlineUpload({ label, hint, urlValue, onUrlChange, uploadEndpoint, token }: {
  label: string; hint?: string; urlValue: string; onUrlChange: (v: string) => void
  uploadEndpoint?: string; token?: string
}) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [uploading, setUploading] = useState(false)
  const [uploadError, setUploadError] = useState('')

  const handleFile = async (file: File) => {
    if (!uploadEndpoint || !token) return
    setUploading(true); setUploadError('')
    try {
      const fd = new FormData()
      fd.append('file', file)
      const res = await fetch(
        `${process.env.NEXT_PUBLIC_ADMIN_API_URL ?? 'http://localhost:8000/v1'}${uploadEndpoint}`,
        { method: 'POST', headers: { Authorization: `Bearer ${token}` }, body: fd },
      )
      if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }))
        throw new Error(err.message ?? 'Upload gagal')
      }
      const data = await res.json()
      const url = data?.data?.logo_url ?? data?.data?.url ?? ''
      if (url) onUrlChange(url)
    } catch (e) {
      setUploadError(e instanceof Error ? e.message : 'Upload gagal')
    } finally { setUploading(false) }
  }

  return (
    <div>
      <label className="block text-sm font-medium text-foreground mb-0.5">{label}</label>
      {hint && <p className="text-xs text-muted-foreground mb-2">{hint}</p>}
      <div className="flex items-center gap-3 p-3 rounded-lg border border-border bg-muted/20">
        {/* Thumbnail / placeholder */}
        <div className="w-16 h-10 flex-shrink-0 rounded border border-border bg-background flex items-center justify-center overflow-hidden">
          {urlValue
            // eslint-disable-next-line @next/next/no-img-element
            ? <img src={urlValue} alt="" className="w-full h-full object-contain" />
            : <Upload size={14} className="text-muted-foreground" />}
        </div>
        {/* Actions */}
        <div className="flex-1 min-w-0 space-y-1.5">
          <input
            type="text"
            value={urlValue}
            onChange={e => onUrlChange(e.target.value)}
            placeholder="URL gambar…"
            className="w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring transition"
          />
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => inputRef.current?.click()}
              disabled={uploading}
              className="flex items-center gap-1.5 px-2.5 py-1 rounded-md border border-border bg-background text-xs font-medium text-foreground hover:bg-accent disabled:opacity-50 transition-colors"
            >
              {uploading ? <Loader2 size={11} className="animate-spin" /> : <Upload size={11} />}
              {uploading ? 'Mengupload…' : 'Pilih File'}
            </button>
            {urlValue && (
              <button
                type="button"
                onClick={() => onUrlChange('')}
                className="flex items-center gap-1 text-xs text-muted-foreground hover:text-destructive transition-colors"
              >
                <X size={11} /> Hapus
              </button>
            )}
            {uploadError && (
              <span className="text-[11px] text-destructive flex items-center gap-1">
                <AlertCircle size={10} /> {uploadError}
              </span>
            )}
          </div>
        </div>
      </div>
      <input
        ref={inputRef} type="file" accept="image/*" className="hidden"
        onChange={e => { const f = e.target.files?.[0]; if (f) handleFile(f) }}
      />
    </div>
  )
}

// ── Section card wrapper ───────────────────────────────────────────────────────
function SectionCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="rounded-xl border border-border bg-card overflow-hidden">
      <div className="px-5 py-3 border-b border-border bg-muted/30">
        <p className="text-sm font-semibold text-foreground">{title}</p>
      </div>
      <div className="px-5 py-4">{children}</div>
    </div>
  )
}

// ── Tab: Umum ──────────────────────────────────────────────────────────────

function TabUmum({ token }: { token: string }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    app_name: '', app_description: '', logo_url: '', favicon_url: '',
    contact_email: '', contact_phone: '', contact_address: '', copyright_text: '',
    footer_bg_color: '#1a1a2e',
    facebook_url: '', instagram_url: '', twitter_url: '', youtube_url: '', tripadvisor_url: '',
  })
  const [saved, setSaved] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['settings-general'],
    queryFn: () => api.get<any>('/admin/settings/general', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (data?.data) {
      const d = data.data
      setForm({
        app_name:        d.app_name        ?? '',
        app_description: d.app_description ?? '',
        logo_url:        d.logo_url        ?? '',
        favicon_url:     d.favicon_url     ?? '',
        contact_email:   d.contact_email   ?? '',
        contact_phone:   d.contact_phone   ?? '',
        contact_address: d.contact_address ?? '',
        copyright_text:  d.copyright_text  ?? '',
        footer_bg_color: d.footer_bg_color ?? '#1a1a2e',
        facebook_url:    d.facebook_url    ?? '',
        instagram_url:   d.instagram_url   ?? '',
        twitter_url:     d.twitter_url     ?? '',
        youtube_url:     d.youtube_url     ?? '',
        tripadvisor_url: d.tripadvisor_url ?? '',
      })
    }
  }, [data])

  const mutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.put<any>('/admin/settings/general', payload, { token }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings-general'] })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const set = (key: keyof typeof form) => (v: string) => setForm(prev => ({ ...prev, [key]: v }))

  if (isLoading) return <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>

  const socialFields = [
    { key: 'facebook_url',    label: 'Facebook',    placeholder: 'https://facebook.com/...' },
    { key: 'instagram_url',   label: 'Instagram',   placeholder: 'https://instagram.com/...' },
    { key: 'twitter_url',     label: 'X / Twitter', placeholder: 'https://x.com/...' },
    { key: 'youtube_url',     label: 'YouTube',     placeholder: 'https://youtube.com/...' },
    { key: 'tripadvisor_url', label: 'Tripadvisor', placeholder: 'https://tripadvisor.com/...' },
  ]

  return (
    <form onSubmit={e => { e.preventDefault(); mutation.mutate(form) }} className="space-y-4">

      {/* ── Identitas Aplikasi ── */}
      <SectionCard title="Identitas Aplikasi">
        <div className="grid grid-cols-2 gap-4 mb-4">
          <Field label="Nama Aplikasi" hint="Tampil di header dan title halaman">
            <TextInput value={form.app_name} onChange={set('app_name')} placeholder="Amartha eTicket" />
          </Field>
          <Field label="Deskripsi" hint="Deskripsi singkat platform">
            <TextInput value={form.app_description} onChange={set('app_description')} placeholder="Platform ticketing wisata" />
          </Field>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <InlineUpload
            label="Logo"
            hint="Header storefront — maks 400×80 px"
            urlValue={form.logo_url}
            onUrlChange={set('logo_url')}
            uploadEndpoint="/admin/settings/general/upload-logo"
            token={token}
          />
          <InlineUpload
            label="Favicon"
            hint="Icon tab browser — maks 64×64 px"
            urlValue={form.favicon_url}
            onUrlChange={set('favicon_url')}
          />
        </div>
      </SectionCard>

      {/* ── Kontak & Footer ── */}
      <SectionCard title="Kontak &amp; Footer">
        <div className="grid grid-cols-2 gap-4 mb-4">
          <Field label="Email">
            <TextInput value={form.contact_email} onChange={set('contact_email')} type="email" placeholder="info@example.com" />
          </Field>
          <Field label="Nomor Telepon">
            <TextInput value={form.contact_phone} onChange={set('contact_phone')} placeholder="+62361-950000" />
          </Field>
        </div>
        <div className="grid grid-cols-2 gap-4 mb-4">
          <Field label="Alamat">
            <textarea
              value={form.contact_address}
              onChange={e => set('contact_address')(e.target.value)}
              rows={3}
              placeholder="Jl. Contoh No. 1, Kota, Provinsi"
              className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition resize-none"
            />
          </Field>
          <Field label="Teks Copyright" hint="Tampil di baris bawah footer">
            <TextInput value={form.copyright_text} onChange={set('copyright_text')} placeholder="Hak Cipta © 2026 Nama Perusahaan" />
          </Field>
        </div>

        {/* Background color */}
        <div>
          <label className="block text-sm font-medium text-foreground mb-1">Warna Background Footer</label>
          <p className="text-xs text-muted-foreground mb-3">Warna solid untuk background footer storefront</p>
          <div className="flex items-center gap-4">
            <ColorPicker
              label="Warna"
              value={form.footer_bg_color}
              onChange={set('footer_bg_color')}
            />
            {/* Live preview */}
            <div className="flex-1 mt-5">
              <div
                className="h-10 rounded-lg border border-border flex items-center justify-center text-xs font-medium shadow-inner"
                style={{
                  background: /^#[0-9a-fA-F]{3,8}$/.test(form.footer_bg_color) ? form.footer_bg_color : '#1a1a2e',
                  color: 'rgba(255,255,255,0.6)',
                }}
              >
                Preview Footer
              </div>
            </div>
          </div>
        </div>
      </SectionCard>

      {/* ── Media Sosial ── */}
      <SectionCard title="Tautan Media Sosial">
        <div className="grid grid-cols-2 gap-x-6 gap-y-3">
          {socialFields.map(({ key, label, placeholder }) => (
            <Field key={key} label={label}>
              <TextInput
                value={(form as Record<string, string>)[key]}
                onChange={v => setForm(prev => ({ ...prev, [key]: v }))}
                placeholder={placeholder}
              />
            </Field>
          ))}
        </div>
      </SectionCard>

      <ActionRow
        pending={mutation.isPending}
        saved={saved}
        error={mutation.isError ? (mutation.error as Error)?.message : undefined}
      />
    </form>
  )
}

// ── Tab: Pembayaran ────────────────────────────────────────────────────────

type GatewayData = {
  midtrans: { enabled: boolean; environment: string; server_key: string; client_key: string; server_key_configured: boolean; client_key_configured: boolean; snap_url: string }
  doku:     { enabled: boolean; environment: string; mall_id: string; client_id: string; secret_key: string; secret_key_configured: boolean }
  cash:     { enabled: boolean }
}

function GatewayCard({ title, logo, enabled, onToggleEnabled, children, onSave, onTest, saving, testing, saved, testResult, testError }: {
  title: string; logo: string; enabled: boolean; onToggleEnabled: () => void
  children: React.ReactNode
  onSave: () => void; onTest: () => void
  saving: boolean; testing: boolean; saved: boolean
  testResult: 'ok' | 'fail' | null; testError: string
}) {
  const [open, setOpen] = useState(enabled)

  return (
    <div className="rounded-xl border border-border bg-card overflow-hidden">
      {/* Header */}
      <div className="flex items-center justify-between px-5 py-4">
        <div className="flex items-center gap-3">
          <button type="button" onClick={() => setOpen(v => !v)}
            className="flex items-center gap-2 text-sm font-semibold text-foreground hover:text-primary transition-colors">
            <ChevronDown size={16} className={cn('transition-transform', open && 'rotate-180')} />
            {logo} {title}
          </button>
          {enabled && <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700">Aktif</span>}
        </div>
        <ToggleRow label="" checked={enabled} onChange={onToggleEnabled} />
      </div>

      {/* Body */}
      {open && (
        <div className="border-t border-border px-5 py-5 space-y-5">
          {children}
          <div className="flex items-center gap-3 flex-wrap pt-1">
            <button type="button" onClick={onSave} disabled={saving}
              className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              Simpan
            </button>
            <button type="button" onClick={onTest} disabled={testing}
              className="flex items-center gap-2 px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent disabled:opacity-50 transition-colors">
              {testing ? <Loader2 size={14} className="animate-spin" /> : <Wifi size={14} />}
              Test Koneksi
            </button>
            {saved          && <span className="flex items-center gap-1 text-sm text-emerald-600"><CheckCircle size={13} /> Tersimpan</span>}
            {testResult === 'ok'   && <span className="flex items-center gap-1 text-sm text-emerald-600"><CheckCircle size={13} /> Koneksi berhasil</span>}
            {testResult === 'fail' && <span className="flex items-center gap-1 text-sm text-destructive"><AlertCircle size={13} /> {testError || 'Koneksi gagal'}</span>}
          </div>
        </div>
      )}
    </div>
  )
}

function TabPembayaran({ token }: { token: string }) {
  const queryClient = useQueryClient()

  // Payment method form (full/DP)
  const [methodForm, setMethodForm] = useState({ full_payment: true, down_payment: false, dp_percentages: [] as number[] })
  const [methodSaved, setMethodSaved] = useState(false)

  // Midtrans
  const [midtrans, setMidtrans] = useState({ enabled: true, environment: 'sandbox', server_key: '', client_key: '' })
  const [midtransConf, setMidtransConf] = useState({ server_key: false, client_key: false })
  const [midtransSaving, setMidtransSaving]   = useState(false)
  const [midtransTesting, setMidtransTesting] = useState(false)
  const [midtransSaved, setMidtransSaved]     = useState(false)
  const [midtransTest, setMidtransTest]       = useState<'ok'|'fail'|null>(null)
  const [midtransTestErr, setMidtransTestErr] = useState('')

  // Doku
  const [doku, setDoku] = useState({ enabled: false, environment: 'sandbox', mall_id: '', client_id: '', secret_key: '' })
  const [dokuConf, setDokuConf]       = useState({ secret_key: false })
  const [dokuSaving, setDokuSaving]   = useState(false)
  const [dokuTesting, setDokuTesting] = useState(false)
  const [dokuSaved, setDokuSaved]     = useState(false)
  const [dokuTest, setDokuTest]       = useState<'ok'|'fail'|null>(null)
  const [dokuTestErr, setDokuTestErr] = useState('')

  // Cash
  const [cashEnabled, setCashEnabled] = useState(true)
  const [cashSaved, setCashSaved]     = useState(false)

  // Fetch all data
  const { data: methodData, isLoading: methodLoading } = useQuery({
    queryKey: ['settings-payment'],
    queryFn: () => api.get<any>('/admin/settings/payment-options', { token }),
    enabled: !!token,
  })
  const { data: gwData, isLoading: gwLoading } = useQuery({
    queryKey: ['settings-gateways'],
    queryFn: () => api.get<any>('/admin/settings/gateways', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (methodData?.data) {
      const d = methodData.data
      setMethodForm({ full_payment: d.full_payment ?? true, down_payment: d.down_payment ?? false, dp_percentages: d.dp_percentages ?? [] })
    }
  }, [methodData])

  useEffect(() => {
    if (gwData?.data) {
      const { midtrans: m, doku: d, cash: c } = gwData.data as GatewayData
      setMidtrans({ enabled: m.enabled, environment: m.environment, server_key: '', client_key: '' })
      setMidtransConf({ server_key: m.server_key_configured, client_key: m.client_key_configured })
      setDoku({ enabled: d.enabled, environment: d.environment, mall_id: d.mall_id, client_id: d.client_id, secret_key: '' })
      setDokuConf({ secret_key: d.secret_key_configured })
      setCashEnabled(c.enabled)
    }
  }, [gwData])

  const methodMutation = useMutation({
    mutationFn: (p: typeof methodForm) => api.put<any>('/admin/settings/payment-options', p, { token }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['settings-payment'] }); setMethodSaved(true); setTimeout(() => setMethodSaved(false), 3000) },
  })

  const toggleDp = (pct: number) => setMethodForm(prev => ({
    ...prev,
    dp_percentages: prev.dp_percentages.includes(pct)
      ? prev.dp_percentages.filter(p => p !== pct)
      : [...prev.dp_percentages, pct].sort((a, b) => a - b),
  }))

  const saveMidtrans = async () => {
    setMidtransSaving(true)
    try {
      await api.put('/admin/settings/gateways/midtrans', midtrans, { token })
      queryClient.invalidateQueries({ queryKey: ['settings-gateways'] })
      setMidtransSaved(true); setTimeout(() => setMidtransSaved(false), 3000)
    } finally { setMidtransSaving(false) }
  }

  const testMidtrans = async () => {
    setMidtransTesting(true); setMidtransTest(null); setMidtransTestErr('')
    try {
      const r = await api.post<any>('/admin/settings/gateways/midtrans/test', {}, { token })
      setMidtransTest(r?.data?.connected ? 'ok' : 'fail')
      setMidtransTestErr(r?.data?.error ?? '')
    } catch (e) { setMidtransTest('fail'); setMidtransTestErr(e instanceof Error ? e.message : 'Error') }
    finally { setMidtransTesting(false) }
  }

  const saveDoku = async () => {
    setDokuSaving(true)
    try {
      await api.put('/admin/settings/gateways/doku', doku, { token })
      queryClient.invalidateQueries({ queryKey: ['settings-gateways'] })
      setDokuSaved(true); setTimeout(() => setDokuSaved(false), 3000)
    } finally { setDokuSaving(false) }
  }

  const testDoku = async () => {
    setDokuTesting(true); setDokuTest(null); setDokuTestErr('')
    try {
      const r = await api.post<any>('/admin/settings/gateways/doku/test', {}, { token })
      setDokuTest(r?.data?.connected ? 'ok' : 'fail')
      setDokuTestErr(r?.data?.error ?? '')
    } catch (e) { setDokuTest('fail'); setDokuTestErr(e instanceof Error ? e.message : 'Error') }
    finally { setDokuTesting(false) }
  }

  const saveCash = async (enabled: boolean) => {
    await api.put('/admin/settings/gateways/cash', { enabled }, { token })
    setCashSaved(true); setTimeout(() => setCashSaved(false), 3000)
  }

  if (methodLoading || gwLoading) return <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>

  return (
    <div className="space-y-8">
      {/* Metode Pembayaran */}
      <div>
        <p className="text-sm font-semibold text-foreground mb-4">Metode Pembayaran</p>
        <div className="space-y-4">
          <ToggleRow label="Bayar Penuh" description="Pelanggan membayar total tagihan sekaligus" checked={methodForm.full_payment} onChange={() => setMethodForm(p => ({ ...p, full_payment: !p.full_payment }))} />
          <div className="h-px bg-border" />
          <ToggleRow label="Uang Muka (DP)" description="Pelanggan dapat membayar sebagian terlebih dahulu" checked={methodForm.down_payment} onChange={() => setMethodForm(p => ({ ...p, down_payment: !p.down_payment }))} />
          {methodForm.down_payment && (
            <div className="ml-4 pl-4 border-l-2 border-emerald-200 space-y-2">
              <p className="text-sm font-medium text-foreground">Persentase DP yang diizinkan</p>
              <div className="flex gap-4">
                {DP_OPTIONS.map(pct => (
                  <label key={pct} className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={methodForm.dp_percentages.includes(pct)} onChange={() => toggleDp(pct)}
                      className="h-4 w-4 rounded border-input accent-emerald-600" />
                    <span className="text-sm">{pct}%</span>
                  </label>
                ))}
              </div>
            </div>
          )}
        </div>
        <div className="flex items-center gap-3 mt-5">
          <button type="button" onClick={() => methodMutation.mutate(methodForm)} disabled={methodMutation.isPending}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
            {methodMutation.isPending ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
            Simpan
          </button>
          {methodSaved && <span className="flex items-center gap-1.5 text-sm text-emerald-600"><CheckCircle size={14} /> Tersimpan</span>}
        </div>
      </div>

      {/* Gateway Pembayaran */}
      <div>
        <p className="text-sm font-semibold text-foreground mb-4">Konfigurasi Gateway</p>
        <div className="space-y-4">

          {/* Midtrans */}
          <GatewayCard
            title="Midtrans" logo="💳"
            enabled={midtrans.enabled}
            onToggleEnabled={() => setMidtrans(p => ({ ...p, enabled: !p.enabled }))}
            onSave={saveMidtrans} onTest={testMidtrans}
            saving={midtransSaving} testing={midtransTesting}
            saved={midtransSaved} testResult={midtransTest} testError={midtransTestErr}
          >
            <Field label="Environment">
              <SelectInput value={midtrans.environment} onChange={v => setMidtrans(p => ({ ...p, environment: v }))} options={[
                { value: 'sandbox',    label: 'Sandbox (Testing)' },
                { value: 'production', label: 'Production' },
              ]} />
            </Field>
            <SecretField label="Server Key" hint="Midtrans Server Key dari dashboard"
              configured={midtransConf.server_key} value={midtrans.server_key}
              onChange={v => setMidtrans(p => ({ ...p, server_key: v }))} />
            <SecretField label="Client Key" hint="Midtrans Client Key (digunakan di frontend)"
              configured={midtransConf.client_key} value={midtrans.client_key}
              onChange={v => setMidtrans(p => ({ ...p, client_key: v }))} />
          </GatewayCard>

          {/* Doku */}
          <GatewayCard
            title="Doku" logo="🏦"
            enabled={doku.enabled}
            onToggleEnabled={() => setDoku(p => ({ ...p, enabled: !p.enabled }))}
            onSave={saveDoku} onTest={testDoku}
            saving={dokuSaving} testing={dokuTesting}
            saved={dokuSaved} testResult={dokuTest} testError={dokuTestErr}
          >
            <Field label="Environment">
              <SelectInput value={doku.environment} onChange={v => setDoku(p => ({ ...p, environment: v }))} options={[
                { value: 'sandbox',    label: 'Sandbox (Testing)' },
                { value: 'production', label: 'Production' },
              ]} />
            </Field>
            <div className="grid grid-cols-2 gap-4">
              <Field label="Mall ID" hint="DOKU Mall ID">
                <TextInput value={doku.mall_id} onChange={v => setDoku(p => ({ ...p, mall_id: v }))} placeholder="mall_id" />
              </Field>
              <Field label="Client ID" hint="DOKU Client ID">
                <TextInput value={doku.client_id} onChange={v => setDoku(p => ({ ...p, client_id: v }))} placeholder="client_id" />
              </Field>
            </div>
            <SecretField label="Secret Key" hint="DOKU Secret Key dari dashboard"
              configured={dokuConf.secret_key} value={doku.secret_key}
              onChange={v => setDoku(p => ({ ...p, secret_key: v }))} />
          </GatewayCard>

          {/* Tunai */}
          <div className="rounded-xl border border-border bg-card px-5 py-4 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span>💵</span>
              <div>
                <p className="text-sm font-semibold text-foreground">Tunai</p>
                <p className="text-xs text-muted-foreground">Pembayaran tunai di lokasi melalui kasir</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              {cashSaved && <span className="text-xs text-emerald-600 flex items-center gap-1"><CheckCircle size={12} /> Tersimpan</span>}
              <ToggleRow label="" checked={cashEnabled} onChange={() => { const next = !cashEnabled; setCashEnabled(next); saveCash(next) }} />
            </div>
          </div>

        </div>
      </div>
    </div>
  )
}

// ── Tab: Notifikasi ────────────────────────────────────────────────────────

function TabNotifikasi({ token }: { token: string }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ email_order: true, email_payment: true, whatsapp_enabled: false })
  const [saved, setSaved] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['settings-notifications'],
    queryFn: () => api.get<any>('/admin/settings/notifications', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (data?.data) {
      const d = data.data
      setForm({
        email_order:      d.email_order      ?? true,
        email_payment:    d.email_payment    ?? true,
        whatsapp_enabled: d.whatsapp_enabled ?? false,
      })
    }
  }, [data])

  const mutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.put<any>('/admin/settings/notifications', payload, { token }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings-notifications'] })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const toggle = (key: keyof typeof form) => setForm(prev => ({ ...prev, [key]: !prev[key] }))

  if (isLoading) return <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>

  return (
    <form onSubmit={e => { e.preventDefault(); mutation.mutate(form) }} className="space-y-5">
      <ToggleRow label="Email konfirmasi pesanan" description="Kirim email ke pelanggan saat pesanan berhasil dibuat" checked={form.email_order} onChange={() => toggle('email_order')} />
      <div className="h-px bg-border" />
      <ToggleRow label="Email konfirmasi pembayaran" description="Kirim email ke pelanggan saat pembayaran berhasil" checked={form.email_payment} onChange={() => toggle('email_payment')} />
      <div className="h-px bg-border" />
      <ToggleRow label="Notifikasi WhatsApp (Fonnte)" description="Kirim pesan WhatsApp otomatis via Fonnte API" checked={form.whatsapp_enabled} onChange={() => toggle('whatsapp_enabled')} />
      <ActionRow
        pending={mutation.isPending}
        saved={saved}
        error={mutation.isError ? (mutation.error as Error)?.message : undefined}
      />
    </form>
  )
}

// ── Tab: Email ─────────────────────────────────────────────────────────────

function SelectInput({ value, onChange, options }: {
  value: string
  onChange: (v: string) => void
  options: { value: string; label: string }[]
}) {
  return (
    <select
      value={value}
      onChange={e => onChange(e.target.value)}
      className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition"
    >
      {options.map(o => (
        <option key={o.value} value={o.value}>{o.label}</option>
      ))}
    </select>
  )
}

function TabEmail({ token }: { token: string }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    mailer: 'smtp', host: '', port: 587, username: '', password: '',
    encryption: 'tls', from_address: '', from_name: '',
  })
  const [passwordConfigured, setPasswordConfigured] = useState(false)
  const [testTo,     setTestTo]     = useState('')
  const [saved,      setSaved]      = useState(false)
  const [testing,    setTesting]    = useState(false)
  const [testResult, setTestResult] = useState<'ok' | 'fail' | null>(null)
  const [testError,  setTestError]  = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['settings-email'],
    queryFn: () => api.get<any>('/admin/settings/email', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (data?.data) {
      const d = data.data
      setForm({
        mailer:       d.mailer       ?? 'smtp',
        host:         d.host         ?? '',
        port:         d.port         ?? 587,
        username:     d.username     ?? '',
        password:     '',
        encryption:   d.encryption   ?? 'tls',
        from_address: d.from_address ?? '',
        from_name:    d.from_name    ?? '',
      })
      setPasswordConfigured(d.password_configured ?? false)
    }
  }, [data])

  const mutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.put<any>('/admin/settings/email', payload, { token }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings-email'] })
      setSaved(true)
      setTestResult(null)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const handleTest = async () => {
    if (!testTo) return
    setTesting(true); setTestResult(null); setTestError('')
    try {
      const res = await api.post<any>('/admin/settings/email/test', { to: testTo }, { token })
      if (res?.data?.sent) {
        setTestResult('ok')
      } else {
        setTestResult('fail')
        setTestError(res?.data?.error ?? 'Gagal mengirim')
      }
    } catch (err) {
      setTestResult('fail')
      setTestError(err instanceof Error ? err.message : 'Gagal mengirim')
    } finally {
      setTesting(false)
    }
  }

  const set = (key: keyof typeof form) => (v: any) => setForm(prev => ({ ...prev, [key]: v }))

  if (isLoading) return <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>

  return (
    <div className="space-y-8">
      <form onSubmit={e => { e.preventDefault(); mutation.mutate(form) }} className="space-y-6">
        {/* SMTP Config */}
        <div className="grid grid-cols-2 gap-4">
          <Field label="Mailer" hint="Driver pengiriman email">
            <SelectInput value={form.mailer} onChange={set('mailer')} options={[
              { value: 'smtp',  label: 'SMTP' },
              { value: 'log',   label: 'Log (development)' },
              { value: 'array', label: 'Array (testing)' },
            ]} />
          </Field>
          <Field label="Enkripsi" hint="Protokol keamanan koneksi">
            <SelectInput value={form.encryption} onChange={set('encryption')} options={[
              { value: 'tls',  label: 'TLS (STARTTLS)' },
              { value: 'ssl',  label: 'SSL' },
              { value: 'none', label: 'Tidak ada' },
            ]} />
          </Field>
        </div>

        <div className="grid grid-cols-3 gap-4">
          <div className="col-span-2">
            <Field label="Host" hint="Alamat SMTP server">
              <TextInput value={form.host} onChange={set('host')} placeholder="smtp.resend.com" />
            </Field>
          </div>
          <Field label="Port" hint="Port SMTP">
            <input
              type="number"
              value={form.port}
              onChange={e => set('port')(parseInt(e.target.value) || 587)}
              min={1} max={65535}
              className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
            />
          </Field>
        </div>

        <Field label="Username" hint="Username SMTP (biasanya alamat email atau 'resend')">
          <TextInput value={form.username} onChange={set('username')} placeholder="resend" />
        </Field>

        <SecretField
          label="Password / API Key"
          hint="Password SMTP atau API key (contoh: Resend API key)"
          configured={passwordConfigured}
          value={form.password}
          onChange={set('password')}
        />

        <div className="grid grid-cols-2 gap-4">
          <Field label="From Address" hint="Alamat pengirim email">
            <TextInput value={form.from_address} onChange={set('from_address')} type="email" placeholder="noreply@amartha-eticket.com" />
          </Field>
          <Field label="From Name" hint="Nama pengirim email">
            <TextInput value={form.from_name} onChange={set('from_name')} placeholder="Amartha eTicket" />
          </Field>
        </div>

        <div className="flex items-center gap-3 flex-wrap pt-1">
          <button type="submit" disabled={mutation.isPending}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
            {mutation.isPending ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
            Simpan
          </button>
          {saved       && <span className="flex items-center gap-1.5 text-sm text-emerald-600"><CheckCircle size={14} /> Tersimpan</span>}
          {mutation.isError && <span className="text-sm text-destructive"><AlertCircle size={14} className="inline mr-1" />{(mutation.error as Error)?.message}</span>}
        </div>
      </form>

      {/* Test Email */}
      <div className="rounded-lg border border-border bg-muted/20 p-5 space-y-4">
        <div>
          <p className="text-sm font-semibold text-foreground">Test Kirim Email</p>
          <p className="text-xs text-muted-foreground mt-0.5">Kirim email percobaan untuk memverifikasi konfigurasi di atas</p>
        </div>
        <div className="flex gap-3">
          <input
            type="email"
            value={testTo}
            onChange={e => setTestTo(e.target.value)}
            placeholder="Alamat email tujuan..."
            className="flex-1 rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
          />
          <button
            type="button"
            onClick={handleTest}
            disabled={testing || !testTo}
            className="flex items-center gap-2 px-4 py-2 rounded-lg border border-border bg-background text-sm font-medium text-foreground hover:bg-accent disabled:opacity-50 transition-colors"
          >
            {testing ? <Loader2 size={15} className="animate-spin" /> : <Send size={15} />}
            Kirim Test
          </button>
        </div>
        {testResult === 'ok'   && <p className="text-sm text-emerald-600 flex items-center gap-1.5"><CheckCircle size={14} /> Email berhasil dikirim ke {testTo}</p>}
        {testResult === 'fail' && <p className="text-sm text-destructive flex items-center gap-1.5"><AlertCircle size={14} /> {testError || 'Gagal mengirim email'}</p>}
      </div>
    </div>
  )
}

// ── Tab: Cloudinary ────────────────────────────────────────────────────────

function SecretField({ label, hint, configured, value, onChange }: {
  label: string; hint?: string; configured?: boolean; value: string; onChange: (v: string) => void
}) {
  const [show, setShow] = useState(false)
  const hasValue = configured || value.length > 0

  return (
    <Field label={label} hint={hint}>
      <div className="relative">
        <input
          type={show ? 'text' : 'password'}
          value={value}
          onChange={e => onChange(e.target.value)}
          placeholder={hasValue && !value ? '••••••••' : undefined}
          className="w-full rounded-lg border border-input bg-background px-3 py-2 pr-10 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition"
        />
        {hasValue && !value && (
          <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground pointer-events-none">
            {'••••••••'} <span className="text-xs">(sudah dikonfigurasi)</span>
          </span>
        )}
        <button
          type="button"
          onClick={() => setShow(v => !v)}
          className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors"
        >
          {show ? <EyeOff size={16} /> : <Eye size={16} />}
        </button>
      </div>
    </Field>
  )
}

function TabCloudinary({ token }: { token: string }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({
    cloud_name: '', api_key: '', api_secret: '', upload_preset: '',
    folder_products: 'amartha/products', folder_tickets: 'amartha/tickets', folder_avatars: 'amartha/avatars',
    auto_quality: true, auto_format: true, max_width: 1920, thumb_width: 400,
  })
  const [configured, setConfigured] = useState({ api_key: false, api_secret: false })
  const [saved, setSaved]   = useState(false)
  const [testing, setTesting] = useState(false)
  const [testResult, setTestResult] = useState<'ok' | 'fail' | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['settings-cloudinary'],
    queryFn: () => api.get<any>('/admin/settings/cloudinary', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (data?.data) {
      const d = data.data
      setForm(prev => ({
        ...prev,
        cloud_name:      d.cloud_name      ?? '',
        upload_preset:   d.upload_preset   ?? '',
        folder_products: d.folder_products ?? 'amartha/products',
        folder_tickets:  d.folder_tickets  ?? 'amartha/tickets',
        folder_avatars:  d.folder_avatars  ?? 'amartha/avatars',
        auto_quality:    d.auto_quality    ?? true,
        auto_format:     d.auto_format     ?? true,
        max_width:       d.max_width       ?? 1920,
        thumb_width:     d.thumb_width     ?? 400,
      }))
      setConfigured({ api_key: d.api_key_configured, api_secret: d.api_secret_configured })
    }
  }, [data])

  const mutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.put<any>('/admin/settings/cloudinary', payload, { token }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings-cloudinary'] })
      setSaved(true)
      setTestResult(null)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const handleTest = async () => {
    setTesting(true); setTestResult(null)
    try {
      const res = await api.post<any>('/admin/settings/cloudinary/test', {}, { token })
      setTestResult(res?.data?.connected ? 'ok' : 'fail')
    } catch {
      setTestResult('fail')
    } finally {
      setTesting(false)
    }
  }

  const set = (key: keyof typeof form) => (v: any) => setForm(prev => ({ ...prev, [key]: v }))

  if (isLoading) return <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>

  return (
    <form onSubmit={e => { e.preventDefault(); mutation.mutate(form) }} className="space-y-6">
      {/* Credentials */}
      <Field label="Cloud Name" hint="Cloudinary Cloud Name dari dashboard">
        <TextInput value={form.cloud_name} onChange={set('cloud_name')} placeholder="your-cloud-name" />
      </Field>

      <SecretField
        label="API Key"
        hint="Cloudinary API Key"
        configured={configured.api_key}
        value={form.api_key}
        onChange={set('api_key')}
      />

      <SecretField
        label="API Secret"
        hint="Cloudinary API Secret"
        configured={configured.api_secret}
        value={form.api_secret}
        onChange={set('api_secret')}
      />

      <Field label="Upload Preset" hint="Cloudinary Upload Preset (opsional)">
        <TextInput value={form.upload_preset} onChange={set('upload_preset')} placeholder="" />
      </Field>

      {/* Toggles */}
      <div className="space-y-4">
        <ToggleRow label="Kualitas Otomatis" checked={form.auto_quality} onChange={() => set('auto_quality')(!form.auto_quality)} />
        <ToggleRow label="Format Otomatis" checked={form.auto_format}  onChange={() => set('auto_format')(!form.auto_format)} />
      </div>

      {/* Dimensions */}
      <div className="grid grid-cols-2 gap-4">
        <Field label="Lebar Maksimal (px)" hint="Lebar maksimal gambar setelah upload (dalam pixel)">
          <input
            type="number"
            value={form.max_width}
            onChange={e => set('max_width')(parseInt(e.target.value) || 1920)}
            min={100} max={5000}
            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
          />
        </Field>
        <Field label="Lebar Thumbnail (px)" hint="Lebar thumbnail gambar (dalam pixel)">
          <input
            type="number"
            value={form.thumb_width}
            onChange={e => set('thumb_width')(parseInt(e.target.value) || 400)}
            min={50} max={2000}
            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
          />
        </Field>
      </div>

      {/* Actions */}
      <div className="flex items-center gap-3 flex-wrap pt-1">
        <button type="submit" disabled={mutation.isPending}
          className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
          {mutation.isPending ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
          Simpan
        </button>

        <button type="button" onClick={handleTest} disabled={testing}
          className="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-border bg-background text-sm font-medium text-foreground hover:bg-accent disabled:opacity-50 transition-colors">
          {testing ? <Loader2 size={15} className="animate-spin" /> : <Wifi size={15} />}
          Test Koneksi
        </button>

        {saved && <span className="flex items-center gap-1.5 text-sm text-emerald-600"><CheckCircle size={14} /> Tersimpan</span>}
        {testResult === 'ok'   && <span className="text-sm text-emerald-600 flex items-center gap-1.5"><CheckCircle size={14} /> Koneksi berhasil</span>}
        {testResult === 'fail' && <span className="text-sm text-destructive flex items-center gap-1.5"><AlertCircle size={14} /> Koneksi gagal</span>}
        {mutation.isError && <span className="text-sm text-destructive"><AlertCircle size={14} className="inline mr-1" />{(mutation.error as Error)?.message}</span>}
      </div>
    </form>
  )
}

// ── Page ───────────────────────────────────────────────────────────────────

export default function SettingsGeneralPage() {
  const token = useAdminAuthStore(s => s.token)!
  const [tab, setTab] = useState<Tab>('umum')

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <SlidersHorizontal size={22} className="text-muted-foreground" />
        <h1 className="text-2xl font-bold text-foreground">Pengaturan</h1>
      </div>

      <div className="flex gap-6 items-start">
        {/* Sidebar */}
        <nav className="w-52 shrink-0 rounded-xl border border-border bg-card overflow-hidden">
          {TABS.map(({ id, label, icon: Icon }, i) => (
            <button
              key={id}
              type="button"
              onClick={() => setTab(id)}
              className={cn(
                'w-full flex items-center gap-3 px-4 py-3 text-sm text-left transition-colors',
                i < TABS.length - 1 && 'border-b border-border',
                tab === id
                  ? 'bg-primary/10 text-primary font-semibold'
                  : 'text-muted-foreground hover:bg-muted/40 hover:text-foreground',
              )}
            >
              <Icon size={15} className="shrink-0" />
              {label}
            </button>
          ))}
        </nav>

        {/* Content */}
        <div className="flex-1 min-w-0">
          {tab === 'umum'       && <TabUmum token={token} />}
          {tab === 'pembayaran' && <TabPembayaran token={token} />}
          {tab === 'notifikasi' && <TabNotifikasi token={token} />}
          {tab === 'email'      && <TabEmail token={token} />}
          {tab === 'cloudinary' && <TabCloudinary token={token} />}
        </div>
      </div>
    </div>
  )
}
