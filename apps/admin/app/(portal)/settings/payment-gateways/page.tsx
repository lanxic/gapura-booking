'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import {
  CreditCard, Shield, AlertCircle, CheckCircle,
  Loader2, Eye, EyeOff, Banknote, Building2, ToggleLeft, ToggleRight,
} from 'lucide-react'

// ─── Types ───────────────────────────────────────────────────────────────────

type OnlineGateway = {
  id: number
  name: string
  type: 'online'
  is_active: boolean
  environment: 'sandbox' | 'production'
  has_server_key: boolean
  has_client_key: boolean
}

type OfflineMethod = {
  id: number
  name: string
  type: 'offline'
  is_active: boolean
  notes: string | null
  config: Record<string, string> | null
}

type GatewaysResponse = {
  data: {
    online: OnlineGateway[]
    offline: OfflineMethod[]
  }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

const GATEWAY_META: Record<string, { label: string; desc: string; color: string }> = {
  midtrans:     { label: 'Midtrans',      desc: 'Snap payment — kartu kredit, transfer, e-wallet', color: 'text-blue-600' },
  doku:         { label: 'DOKU',          desc: 'Payment gateway lokal Indonesia',                  color: 'text-indigo-600' },
  cash:         { label: 'Tunai (Cash)',   desc: 'Pembayaran tunai langsung di lokasi',             color: 'text-emerald-600' },
  bank_transfer: { label: 'Transfer Bank', desc: 'Transfer ke rekening perusahaan',                color: 'text-teal-600' },
}

// ─── Online Gateway Card ──────────────────────────────────────────────────────

function OnlineGatewayCard({
  gateway, token, onMutated,
}: { gateway: OnlineGateway; token: string; onMutated: () => void }) {
  const [expanded, setExpanded] = useState(false)
  const [showKey, setShowKey]   = useState(false)
  const [saved, setSaved]       = useState(false)
  const [form, setForm] = useState({
    server_key: '', client_key: '', merchant_id: '',
    environment: gateway.environment,
  })

  const update = useMutation({
    mutationFn: () => api.put(`/admin/settings/payment-gateways/${gateway.name}`, form, { token }),
    onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); onMutated() },
  })

  const activate = useMutation({
    mutationFn: () => api.post(`/admin/settings/payment-gateways/${gateway.name}/activate`, {}, { token }),
    onSuccess: onMutated,
  })

  const meta = GATEWAY_META[gateway.name] ?? { label: gateway.name, desc: '', color: 'text-gray-600' }

  return (
    <div className={`bg-card border rounded-xl overflow-hidden transition-all ${
      gateway.is_active ? 'border-emerald-400 shadow-sm' : 'border-border'
    }`}>
      <div className="p-5 flex items-center gap-4">
        <div className="w-10 h-10 rounded-lg bg-muted flex items-center justify-center flex-shrink-0">
          <CreditCard size={20} className={meta.color} />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 flex-wrap">
            <h3 className="font-bold text-foreground">{meta.label}</h3>
            {gateway.is_active && (
              <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
            )}
            <span className={`text-xs px-2 py-0.5 rounded-full ${
              gateway.environment === 'production' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600'
            }`}>
              {gateway.environment}
            </span>
          </div>
          <p className="text-xs text-muted-foreground mt-0.5">{meta.desc}</p>
          <p className="text-xs text-muted-foreground mt-0.5">
            Server key: {gateway.has_server_key ? '•••••••' : <span className="text-red-400">Belum diisi</span>} ·
            Client key: {gateway.has_client_key ? '•••••••' : <span className="text-red-400">Belum diisi</span>}
          </p>
        </div>
        <div className="flex items-center gap-2 flex-shrink-0">
          <button
            onClick={() => activate.mutate()}
            disabled={activate.isPending}
            className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
          >
            {activate.isPending
              ? <Loader2 size={16} className="animate-spin" />
              : gateway.is_active
              ? <ToggleRight size={22} className="text-emerald-500" />
              : <ToggleLeft size={22} className="text-gray-300" />
            }
            {gateway.is_active ? 'Aktif' : 'Nonaktif'}
          </button>
          <button
            onClick={() => setExpanded(v => !v)}
            className="px-3 py-1.5 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors"
          >
            {expanded ? 'Tutup' : 'Konfigurasi'}
          </button>
        </div>
      </div>

      {expanded && (
        <div className="border-t border-border p-5 space-y-4 bg-muted/20">
          {gateway.is_active && (
            <div className="flex items-center gap-2 text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg p-3">
              <AlertCircle size={14} className="shrink-0" />
              Mengaktifkan gateway lain akan menonaktifkan yang ini secara otomatis.
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1.5">Environment</label>
              <select
                value={form.environment}
                onChange={e => setForm(p => ({ ...p, environment: e.target.value as 'sandbox' | 'production' }))}
                className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20"
              >
                <option value="sandbox">Sandbox (Testing)</option>
                <option value="production">Production</option>
              </select>
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1.5">Merchant ID</label>
              <input
                type="text"
                value={form.merchant_id}
                onChange={e => setForm(p => ({ ...p, merchant_id: e.target.value }))}
                placeholder="Kosongkan jika tidak diubah"
                className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1.5">Server Key</label>
              <div className="relative">
                <input
                  type={showKey ? 'text' : 'password'}
                  value={form.server_key}
                  onChange={e => setForm(p => ({ ...p, server_key: e.target.value }))}
                  placeholder="Kosongkan jika tidak diubah"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 pr-9 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
                <button type="button" onClick={() => setShowKey(v => !v)}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                  {showKey ? <EyeOff size={15} /> : <Eye size={15} />}
                </button>
              </div>
            </div>
            <div>
              <label className="block text-xs font-medium text-muted-foreground mb-1.5">Client Key</label>
              <input
                type={showKey ? 'text' : 'password'}
                value={form.client_key}
                onChange={e => setForm(p => ({ ...p, client_key: e.target.value }))}
                placeholder="Kosongkan jika tidak diubah"
                className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20"
              />
            </div>
          </div>

          <div className="flex items-center gap-3 justify-end">
            {saved && (
              <span className="flex items-center gap-1 text-sm text-emerald-600">
                <CheckCircle size={14} /> Tersimpan
              </span>
            )}
            <button
              onClick={() => update.mutate()}
              disabled={update.isPending}
              className="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              {update.isPending && <Loader2 size={14} className="animate-spin" />}
              Simpan Konfigurasi
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

// ─── Offline Method Card ──────────────────────────────────────────────────────

function OfflineMethodCard({
  method, token, onMutated,
}: { method: OfflineMethod; token: string; onMutated: () => void }) {
  const [expanded, setExpanded] = useState(false)
  const [notes, setNotes]       = useState(method.notes ?? '')
  const [bankName, setBankName] = useState(method.config?.bank_name ?? '')
  const [acctName, setAcctName] = useState(method.config?.account_name ?? '')
  const [acctNum, setAcctNum]   = useState(method.config?.account_number ?? '')
  const [saved, setSaved]       = useState(false)

  const toggle = useMutation({
    mutationFn: () => api.post(`/admin/settings/payment-gateways/${method.name}/activate`, {}, { token }),
    onSuccess: onMutated,
  })

  const update = useMutation({
    mutationFn: () => api.put(`/admin/settings/payment-gateways/${method.name}`, {
      notes: notes || null,
      ...(method.name === 'bank_transfer' ? {
        config: { bank_name: bankName, account_name: acctName, account_number: acctNum },
      } : {}),
    }, { token }),
    onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); onMutated() },
  })

  const meta = GATEWAY_META[method.name] ?? { label: method.name, desc: '', color: 'text-gray-600' }
  const Icon = method.name === 'bank_transfer' ? Building2 : Banknote

  return (
    <div className={`bg-card border rounded-xl overflow-hidden transition-all ${
      method.is_active ? 'border-emerald-400 shadow-sm' : 'border-border'
    }`}>
      <div className="p-5 flex items-center gap-4">
        <div className="w-10 h-10 rounded-lg bg-muted flex items-center justify-center flex-shrink-0">
          <Icon size={20} className={meta.color} />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <h3 className="font-bold text-foreground">{meta.label}</h3>
            {method.is_active
              ? <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
              : <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">Nonaktif</span>
            }
          </div>
          <p className="text-xs text-muted-foreground mt-0.5">{meta.desc}</p>
          {method.is_active && method.notes && (
            <p className="text-xs text-muted-foreground mt-0.5 italic">"{method.notes}"</p>
          )}
        </div>
        <div className="flex items-center gap-2 flex-shrink-0">
          <button
            onClick={() => toggle.mutate()}
            disabled={toggle.isPending}
            className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground hover:text-foreground transition-colors"
          >
            {toggle.isPending
              ? <Loader2 size={16} className="animate-spin" />
              : method.is_active
              ? <ToggleRight size={22} className="text-emerald-500" />
              : <ToggleLeft size={22} className="text-gray-300" />
            }
            {method.is_active ? 'Aktif' : 'Nonaktif'}
          </button>
          <button
            onClick={() => setExpanded(v => !v)}
            className="px-3 py-1.5 text-xs font-medium border border-border rounded-lg hover:bg-muted transition-colors"
          >
            {expanded ? 'Tutup' : 'Konfigurasi'}
          </button>
        </div>
      </div>

      {expanded && (
        <div className="border-t border-border p-5 space-y-4 bg-muted/20">
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1.5">
              Instruksi / Catatan untuk Pelanggan
            </label>
            <textarea
              value={notes}
              onChange={e => setNotes(e.target.value)}
              rows={3}
              placeholder={
                method.name === 'cash'
                  ? 'Contoh: Pembayaran tunai diterima oleh petugas di meja registrasi.'
                  : 'Contoh: Transfer ke BCA 1234567890 a/n PT Amartha sebelum jadwal aktivitas.'
              }
              className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20 resize-none"
            />
          </div>

          {method.name === 'bank_transfer' && (
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Nama Bank</label>
                <input value={bankName} onChange={e => setBankName(e.target.value)}
                  placeholder="BCA, Mandiri, BNI..."
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20" />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Nama Rekening</label>
                <input value={acctName} onChange={e => setAcctName(e.target.value)}
                  placeholder="PT Amartha..."
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20" />
              </div>
              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1.5">Nomor Rekening</label>
                <input value={acctNum} onChange={e => setAcctNum(e.target.value)}
                  placeholder="1234567890"
                  className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-primary/20" />
              </div>
            </div>
          )}

          <div className="flex items-center gap-3 justify-end">
            {saved && (
              <span className="flex items-center gap-1 text-sm text-emerald-600">
                <CheckCircle size={14} /> Tersimpan
              </span>
            )}
            <button
              onClick={() => update.mutate()}
              disabled={update.isPending}
              className="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 flex items-center gap-2"
            >
              {update.isPending && <Loader2 size={14} className="animate-spin" />}
              Simpan
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function PaymentGatewaysPage() {
  const token = useAdminAuthStore(s => s.token)!
  const qc    = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-gateways'],
    queryFn: () => api.get<GatewaysResponse>('/admin/settings/payment-gateways', { token }),
  })

  const onlineGateways  = data?.data?.online  ?? []
  const offlineMethods  = data?.data?.offline ?? []
  const refetch = () => qc.invalidateQueries({ queryKey: ['admin-gateways'] })

  return (
    <div className="space-y-8 max-w-2xl">
      <div className="flex items-center gap-3">
        <Shield size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Metode Pembayaran</h1>
          <p className="text-sm text-muted-foreground">Konfigurasi gateway online dan metode pembayaran offline</p>
        </div>
      </div>

      {/* Online Gateways */}
      <section className="space-y-4">
        <div>
          <h2 className="font-semibold text-foreground">Gateway Online</h2>
          <p className="text-xs text-muted-foreground mt-0.5">Hanya 1 gateway online yang dapat aktif sekaligus — mengaktifkan satu akan menonaktifkan yang lain</p>
        </div>

        {isLoading ? (
          <div className="space-y-3">
            {[1, 2].map(i => <div key={i} className="h-20 rounded-xl bg-muted animate-pulse" />)}
          </div>
        ) : (
          <div className="space-y-3">
            {onlineGateways.map(g => (
              <OnlineGatewayCard key={g.id} gateway={g} token={token} onMutated={refetch} />
            ))}
          </div>
        )}
      </section>

      {/* Offline Methods */}
      <section className="space-y-4">
        <div>
          <h2 className="font-semibold text-foreground">Metode Pembayaran Offline</h2>
          <p className="text-xs text-muted-foreground mt-0.5">Digunakan untuk booking walk-in oleh admin. Dapat diaktifkan bersamaan.</p>
        </div>

        {isLoading ? (
          <div className="space-y-3">
            {[1, 2].map(i => <div key={i} className="h-20 rounded-xl bg-muted animate-pulse" />)}
          </div>
        ) : offlineMethods.length === 0 ? (
          <div className="text-sm text-muted-foreground text-center py-8 border border-dashed border-border rounded-xl">
            Tidak ada metode offline tersedia.<br/>
            Jalankan <code className="text-xs bg-muted px-1 py-0.5 rounded">php artisan migrate</code> untuk mengaktifkan.
          </div>
        ) : (
          <div className="space-y-3">
            {offlineMethods.map(m => (
              <OfflineMethodCard key={m.id} method={m} token={token} onMutated={refetch} />
            ))}
          </div>
        )}
      </section>
    </div>
  )
}
