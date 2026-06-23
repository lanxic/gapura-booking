'use client'

import { use, useState, useCallback } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { useRouter } from 'next/navigation'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { Activity, ActivitySlot, ActivityAddon, ApiResponse, Invoice } from '@/types'
import {
  ChevronLeft, ChevronRight, Clock, Users, Star, MapPin,
  Calendar, Plus, Minus, CheckCircle, Loader2, AlertCircle,
  Tag, CreditCard, Shield,
} from 'lucide-react'

// ─── Helpers ────────────────────────────────────────────────────────────────

const MONTHS_ID = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
const DAYS_ID = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']

function todayISO() { return new Date().toISOString().split('T')[0] }
function addDays(iso: string, n: number) {
  const d = new Date(iso); d.setDate(d.getDate() + n); return d.toISOString().split('T')[0]
}
function fmtDate(iso: string) {
  const d = new Date(iso + 'T00:00:00')
  return `${DAYS_ID[d.getDay()]}, ${d.getDate()} ${MONTHS_ID[d.getMonth()]} ${d.getFullYear()}`
}

// ─── Checkout state ──────────────────────────────────────────────────────────

type AddonSelection = { addon_id: number; quantity: number }
type CheckoutState = {
  slot: ActivitySlot | null
  pax: number
  addons: AddonSelection[]
  guestName: string
  guestEmail: string
  guestPhone: string
  promoCode: string
  paymentPlan: 'FULL' | 'DP30' | 'DP50' | 'DP70'
}

// ─── Step indicators ─────────────────────────────────────────────────────────

function StepBar({ step }: { step: number }) {
  const steps = ['Pilih Slot', 'Peserta', 'Data Tamu', 'Pembayaran']
  return (
    <div className="flex items-center gap-0 mb-8">
      {steps.map((label, i) => {
        const idx = i + 1
        const done = step > idx
        const active = step === idx
        return (
          <div key={label} className="flex items-center flex-1">
            <div className="flex flex-col items-center">
              <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-colors ${
                done ? 'bg-emerald-600 text-white' : active ? 'bg-emerald-600 text-white ring-4 ring-emerald-100' : 'bg-gray-200 text-gray-500'
              }`}>
                {done ? <CheckCircle className="w-4 h-4" /> : idx}
              </div>
              <span className={`text-xs mt-1 font-medium text-center hidden sm:block ${active ? 'text-emerald-600' : done ? 'text-emerald-500' : 'text-gray-400'}`}>
                {label}
              </span>
            </div>
            {i < steps.length - 1 && (
              <div className={`flex-1 h-0.5 mx-1 mb-5 ${step > idx ? 'bg-emerald-500' : 'bg-gray-200'}`} />
            )}
          </div>
        )
      })}
    </div>
  )
}

// ─── Step 1: Slot selection ──────────────────────────────────────────────────

function Step1Slots({
  activity, checkout, setCheckout, onNext,
}: {
  activity: Activity
  checkout: CheckoutState
  setCheckout: React.Dispatch<React.SetStateAction<CheckoutState>>
  onNext: () => void
}) {
  const [selectedDate, setSelectedDate] = useState(todayISO())
  const [calOffset, setCalOffset] = useState(0)

  const { data: slotsData, isLoading } = useQuery({
    queryKey: ['activity-slots', activity.slug, selectedDate, checkout.pax],
    queryFn: () =>
      api.get<{ data: ActivitySlot[] }>(
        `/activities/${activity.slug}/slots?date=${selectedDate}&pax=${checkout.pax}`
      ),
  })
  const slots = slotsData?.data ?? []

  // Generate 14-day calendar strip
  const days = Array.from({ length: 14 }, (_, i) => addDays(todayISO(), i + calOffset))

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900 mb-1">Pilih Tanggal & Slot Waktu</h2>
        <p className="text-sm text-gray-500">Pilih tanggal yang tersedia untuk aktivitas ini</p>
      </div>

      {/* Calendar strip */}
      <div className="relative">
        <div className="flex items-center gap-2">
          <button
            onClick={() => setCalOffset(o => Math.max(0, o - 7))}
            disabled={calOffset === 0}
            className="p-1.5 rounded-lg border border-gray-200 disabled:opacity-40 hover:bg-gray-50 transition-colors shrink-0"
          >
            <ChevronLeft className="w-4 h-4" />
          </button>
          <div className="flex-1 overflow-hidden">
            <div className="flex gap-2">
              {days.map(day => {
                const d = new Date(day + 'T00:00:00')
                const isSelected = day === selectedDate
                return (
                  <button
                    key={day}
                    onClick={() => setSelectedDate(day)}
                    className={`flex-1 min-w-[44px] flex flex-col items-center py-2 px-1 rounded-xl border text-xs font-medium transition-all ${
                      isSelected
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'border-gray-200 text-gray-600 hover:border-emerald-300 hover:bg-emerald-50'
                    }`}
                  >
                    <span className="opacity-70">{DAYS_ID[d.getDay()]}</span>
                    <span className="text-base font-bold">{d.getDate()}</span>
                    <span className="opacity-70">{MONTHS_ID[d.getMonth()]}</span>
                  </button>
                )
              })}
            </div>
          </div>
          <button
            onClick={() => setCalOffset(o => o + 7)}
            className="p-1.5 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors shrink-0"
          >
            <ChevronRight className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Slots */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 mb-3">{fmtDate(selectedDate)}</h3>
        {isLoading ? (
          <div className="flex items-center gap-2 text-gray-400 py-6">
            <Loader2 className="w-5 h-5 animate-spin" /> Memuat slot...
          </div>
        ) : slots.length === 0 ? (
          <div className="text-center py-10 text-gray-400">
            <Calendar className="w-10 h-10 mx-auto mb-2 opacity-40" />
            <p className="text-sm">Tidak ada slot tersedia di tanggal ini</p>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
            {slots.map(slot => {
              const isSelected = checkout.slot?.id === slot.id
              const isFull = slot.status !== 'available'
              return (
                <button
                  key={slot.id}
                  disabled={isFull}
                  onClick={() => setCheckout(prev => ({ ...prev, slot }))}
                  className={`p-4 rounded-xl border-2 text-left transition-all ${
                    isSelected
                      ? 'border-emerald-500 bg-emerald-50'
                      : isFull
                      ? 'border-gray-200 bg-gray-50 opacity-50 cursor-not-allowed'
                      : 'border-gray-200 hover:border-emerald-300 hover:bg-emerald-50/50'
                  }`}
                >
                  <p className="font-bold text-gray-900 text-base">
                    {slot.start_time.slice(0, 5)} – {slot.end_time.slice(0, 5)}
                  </p>
                  <p className="text-xs text-gray-500 mt-0.5">
                    {isFull ? 'Penuh' : `Sisa ${slot.remaining_capacity} tempat`}
                  </p>
                  <p className="text-sm font-semibold text-emerald-600 mt-2">
                    {formatRupiah(slot.price)}<span className="text-gray-400 font-normal">/orang</span>
                  </p>
                </button>
              )
            })}
          </div>
        )}
      </div>

      <button
        disabled={!checkout.slot}
        onClick={onNext}
        className="w-full py-3 bg-emerald-600 text-white rounded-xl font-semibold disabled:opacity-40 hover:bg-emerald-700 transition-colors"
      >
        Lanjut — Pilih Peserta
      </button>
    </div>
  )
}

// ─── Step 2: Pax + Addons ────────────────────────────────────────────────────

function Step2Pax({
  activity, checkout, setCheckout, onBack, onNext,
}: {
  activity: Activity
  checkout: CheckoutState
  setCheckout: React.Dispatch<React.SetStateAction<CheckoutState>>
  onBack: () => void
  onNext: () => void
}) {
  const slot = checkout.slot!

  const addonQty = (addonId: number) =>
    checkout.addons.find(a => a.addon_id === addonId)?.quantity ?? 0

  const setAddon = (addon: ActivityAddon, qty: number) => {
    setCheckout(prev => {
      const filtered = prev.addons.filter(a => a.addon_id !== addon.id)
      if (qty <= 0) return { ...prev, addons: filtered }
      return { ...prev, addons: [...filtered, { addon_id: addon.id, quantity: qty }] }
    })
  }

  const baseTotal = slot.price * checkout.pax
  const addonTotal = checkout.addons.reduce((sum, a) => {
    const addon = activity.addons.find(x => x.id === a.addon_id)
    return sum + (addon?.price ?? 0) * a.quantity
  }, 0)
  const total = baseTotal + addonTotal

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900 mb-1">Jumlah Peserta & Add-on</h2>
        <p className="text-sm text-gray-500">
          {slot.start_time.slice(0, 5)} – {slot.end_time.slice(0, 5)} · {fmtDate(checkout.slot?.date ?? '')}
        </p>
      </div>

      {/* Pax counter */}
      <div className="bg-gray-50 rounded-xl p-5">
        <div className="flex items-center justify-between">
          <div>
            <p className="font-semibold text-gray-900">Jumlah Peserta</p>
            <p className="text-xs text-gray-500">Min {activity.min_pax} – Maks {Math.min(activity.max_pax, slot.remaining_capacity)} orang</p>
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={() => setCheckout(prev => ({ ...prev, pax: Math.max(activity.min_pax, prev.pax - 1) }))}
              disabled={checkout.pax <= activity.min_pax}
              className="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center disabled:opacity-40 hover:border-emerald-500 transition-colors"
            >
              <Minus className="w-4 h-4" />
            </button>
            <span className="text-2xl font-bold text-gray-900 w-8 text-center">{checkout.pax}</span>
            <button
              onClick={() => setCheckout(prev => ({ ...prev, pax: Math.min(Math.min(activity.max_pax, slot.remaining_capacity), prev.pax + 1) }))}
              disabled={checkout.pax >= Math.min(activity.max_pax, slot.remaining_capacity)}
              className="w-8 h-8 rounded-full border-2 border-gray-300 flex items-center justify-center disabled:opacity-40 hover:border-emerald-500 transition-colors"
            >
              <Plus className="w-4 h-4" />
            </button>
          </div>
        </div>
        <div className="mt-3 pt-3 border-t border-gray-200 flex justify-between text-sm">
          <span className="text-gray-600">{formatRupiah(slot.price)} × {checkout.pax} orang</span>
          <span className="font-semibold">{formatRupiah(baseTotal)}</span>
        </div>
      </div>

      {/* Addons */}
      {activity.addons.filter(a => a.is_active).length > 0 && (
        <div>
          <h3 className="font-semibold text-gray-900 mb-3">Add-on Opsional</h3>
          <div className="space-y-3">
            {activity.addons.filter(a => a.is_active).map(addon => {
              const qty = addonQty(addon.id)
              return (
                <div key={addon.id} className="flex items-center justify-between p-4 rounded-xl border border-gray-200">
                  <div>
                    <p className="font-medium text-gray-900 text-sm">{addon.name}</p>
                    <p className="text-emerald-600 font-semibold text-sm">{formatRupiah(addon.price)} <span className="text-gray-400 font-normal">/{addon.unit}</span></p>
                  </div>
                  <div className="flex items-center gap-2">
                    <button
                      disabled={qty === 0}
                      onClick={() => setAddon(addon, qty - 1)}
                      className="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center disabled:opacity-40 hover:border-emerald-500 transition-colors text-sm"
                    >
                      <Minus className="w-3 h-3" />
                    </button>
                    <span className="w-5 text-center font-semibold text-sm">{qty}</span>
                    <button
                      disabled={qty >= addon.max_qty}
                      onClick={() => setAddon(addon, qty + 1)}
                      className="w-7 h-7 rounded-full border border-gray-300 flex items-center justify-center disabled:opacity-40 hover:border-emerald-500 transition-colors text-sm"
                    >
                      <Plus className="w-3 h-3" />
                    </button>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      )}

      {/* Summary */}
      <div className="bg-emerald-50 rounded-xl p-4 border border-emerald-200">
        <div className="flex justify-between text-sm text-gray-600 mb-1">
          <span>Aktivitas ({checkout.pax} orang)</span>
          <span>{formatRupiah(baseTotal)}</span>
        </div>
        {addonTotal > 0 && (
          <div className="flex justify-between text-sm text-gray-600 mb-1">
            <span>Add-on</span>
            <span>{formatRupiah(addonTotal)}</span>
          </div>
        )}
        <div className="flex justify-between font-bold text-gray-900 pt-2 border-t border-emerald-200 mt-2">
          <span>Subtotal</span>
          <span>{formatRupiah(total)}</span>
        </div>
      </div>

      <div className="flex gap-3">
        <button onClick={onBack} className="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
          Kembali
        </button>
        <button onClick={onNext} className="flex-2 flex-1 py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors">
          Lanjut — Data Tamu
        </button>
      </div>
    </div>
  )
}

// ─── Step 3: Guest info ──────────────────────────────────────────────────────

function Step3Guest({
  checkout, setCheckout, onBack, onNext,
}: {
  checkout: CheckoutState
  setCheckout: React.Dispatch<React.SetStateAction<CheckoutState>>
  onBack: () => void
  onNext: () => void
}) {
  const [promoStatus, setPromoStatus] = useState<{ valid: boolean; msg: string } | null>(null)
  const [promoLoading, setPromoLoading] = useState(false)

  const validatePromo = async () => {
    if (!checkout.promoCode) return
    setPromoLoading(true)
    try {
      await api.post('/promo/validate', {
        code: checkout.promoCode,
        amount: (checkout.slot?.price ?? 0) * checkout.pax,
      })
      setPromoStatus({ valid: true, msg: 'Kode promo valid!' })
    } catch (e: unknown) {
      const err = e as { message?: string }
      setPromoStatus({ valid: false, msg: err.message ?? 'Kode promo tidak valid.' })
    }
    setPromoLoading(false)
  }

  const valid = checkout.guestName.length >= 2 && /\S+@\S+/.test(checkout.guestEmail)

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900 mb-1">Data Pemesan</h2>
        <p className="text-sm text-gray-500">Masukkan informasi kontak Anda untuk konfirmasi booking</p>
      </div>

      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap <span className="text-red-500">*</span></label>
          <input
            type="text"
            value={checkout.guestName}
            onChange={e => setCheckout(prev => ({ ...prev, guestName: e.target.value }))}
            placeholder="Masukkan nama lengkap"
            className="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">Email <span className="text-red-500">*</span></label>
          <input
            type="email"
            value={checkout.guestEmail}
            onChange={e => setCheckout(prev => ({ ...prev, guestEmail: e.target.value }))}
            placeholder="email@contoh.com"
            className="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
          <p className="text-xs text-gray-400 mt-1">Invoice dan e-ticket akan dikirim ke email ini</p>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1.5">No. WhatsApp</label>
          <input
            type="tel"
            value={checkout.guestPhone}
            onChange={e => setCheckout(prev => ({ ...prev, guestPhone: e.target.value }))}
            placeholder="08xxxxxxxxxx"
            className="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
      </div>

      {/* Promo */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1.5">Kode Promo</label>
        <div className="flex gap-2">
          <input
            type="text"
            value={checkout.promoCode}
            onChange={e => { setCheckout(prev => ({ ...prev, promoCode: e.target.value.toUpperCase() })); setPromoStatus(null) }}
            placeholder="Masukkan kode promo"
            className="flex-1 px-4 py-3 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 uppercase"
          />
          <button
            onClick={validatePromo}
            disabled={!checkout.promoCode || promoLoading}
            className="px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-xl disabled:opacity-40 hover:bg-gray-700 transition-colors"
          >
            {promoLoading ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Cek'}
          </button>
        </div>
        {promoStatus && (
          <div className={`flex items-center gap-1.5 mt-2 text-sm ${promoStatus.valid ? 'text-emerald-600' : 'text-red-500'}`}>
            {promoStatus.valid ? <CheckCircle className="w-4 h-4" /> : <AlertCircle className="w-4 h-4" />}
            {promoStatus.msg}
          </div>
        )}
      </div>

      <div className="flex gap-3">
        <button onClick={onBack} className="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
          Kembali
        </button>
        <button
          disabled={!valid}
          onClick={onNext}
          className="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-semibold disabled:opacity-40 hover:bg-emerald-700 transition-colors"
        >
          Lanjut — Pembayaran
        </button>
      </div>
    </div>
  )
}

// ─── Step 4: Review + Payment plan ──────────────────────────────────────────

function Step4Payment({
  activity, checkout, setCheckout, onBack,
}: {
  activity: Activity
  checkout: CheckoutState
  setCheckout: React.Dispatch<React.SetStateAction<CheckoutState>>
  onBack: () => void
}) {
  const router = useRouter()
  const slot = checkout.slot!

  const baseTotal = slot.price * checkout.pax
  const addonTotal = checkout.addons.reduce((sum, a) => {
    const addon = activity.addons.find(x => x.id === a.addon_id)
    return sum + (addon?.price ?? 0) * a.quantity
  }, 0)
  const grandTotal = baseTotal + addonTotal

  const mutation = useMutation({
    mutationFn: () =>
      api.post<ApiResponse<Invoice>>('/invoices', {
        slot_id: slot.id,
        pax_count: checkout.pax,
        guest_name: checkout.guestName,
        guest_email: checkout.guestEmail,
        guest_phone: checkout.guestPhone || undefined,
        addons: checkout.addons,
        promo_code: checkout.promoCode || undefined,
        payment_plan: checkout.paymentPlan,
      }),
    onSuccess: (res) => {
      const paymentUrl = res.data.payment_url
      if (paymentUrl) {
        window.location.href = paymentUrl
      } else {
        router.push(`/invoice/${res.data.invoice_code}`)
      }
    },
  })

  const PAYMENT_PLANS = [
    { code: 'FULL' as const, label: 'Bayar Penuh', desc: `${formatRupiah(grandTotal)}` },
    { code: 'DP30' as const, label: 'DP 30%', desc: `${formatRupiah(Math.ceil(grandTotal * 0.3))} sekarang` },
    { code: 'DP50' as const, label: 'DP 50%', desc: `${formatRupiah(Math.ceil(grandTotal * 0.5))} sekarang` },
  ]

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-xl font-bold text-gray-900 mb-1">Konfirmasi & Pembayaran</h2>
        <p className="text-sm text-gray-500">Periksa kembali pesanan Anda sebelum melanjutkan</p>
      </div>

      {/* Order summary */}
      <div className="bg-gray-50 rounded-xl p-5 space-y-3">
        <h3 className="font-semibold text-gray-900 text-sm uppercase tracking-wide">Ringkasan Pesanan</h3>
        <div className="space-y-2 text-sm">
          <div className="flex justify-between">
            <span className="text-gray-600">{activity.name}</span>
            <span className="font-medium">{formatRupiah(slot.price)} × {checkout.pax}</span>
          </div>
          {checkout.addons.map(a => {
            const addon = activity.addons.find(x => x.id === a.addon_id)
            if (!addon) return null
            return (
              <div key={a.addon_id} className="flex justify-between text-gray-500">
                <span>{addon.name} × {a.quantity}</span>
                <span>{formatRupiah(addon.price * a.quantity)}</span>
              </div>
            )
          })}
          {checkout.promoCode && (
            <div className="flex items-center gap-1 text-emerald-600">
              <Tag className="w-3.5 h-3.5" /> Promo: {checkout.promoCode}
            </div>
          )}
        </div>
        <div className="pt-3 border-t border-gray-200 flex justify-between font-bold text-gray-900">
          <span>Total</span>
          <span>{formatRupiah(grandTotal)}</span>
        </div>
        <div className="pt-2 text-xs text-gray-500 space-y-0.5">
          <p><strong>Slot:</strong> {fmtDate(slot.date)}, {slot.start_time.slice(0, 5)} – {slot.end_time.slice(0, 5)}</p>
          <p><strong>Pemesan:</strong> {checkout.guestName} · {checkout.guestEmail}</p>
        </div>
      </div>

      {/* Payment plan */}
      <div>
        <h3 className="font-semibold text-gray-900 mb-3 flex items-center gap-2">
          <CreditCard className="w-4 h-4" /> Metode Pembayaran
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
          {PAYMENT_PLANS.map(plan => (
            <button
              key={plan.code}
              onClick={() => setCheckout(prev => ({ ...prev, paymentPlan: plan.code }))}
              className={`p-4 rounded-xl border-2 text-left transition-all ${
                checkout.paymentPlan === plan.code
                  ? 'border-emerald-500 bg-emerald-50'
                  : 'border-gray-200 hover:border-emerald-300'
              }`}
            >
              <p className="font-semibold text-gray-900 text-sm">{plan.label}</p>
              <p className="text-emerald-600 text-xs mt-1">{plan.desc}</p>
            </button>
          ))}
        </div>
      </div>

      {/* Trust signals */}
      <div className="flex items-center gap-2 text-xs text-gray-400">
        <Shield className="w-4 h-4 text-emerald-400" />
        Pembayaran aman diproses oleh Midtrans. Data Anda terenkripsi.
      </div>

      {mutation.isError && (
        <div className="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
          <AlertCircle className="w-4 h-4 shrink-0" />
          {(mutation.error as Error).message ?? 'Terjadi kesalahan. Silakan coba lagi.'}
        </div>
      )}

      <div className="flex gap-3">
        <button onClick={onBack} className="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors">
          Kembali
        </button>
        <button
          onClick={() => mutation.mutate()}
          disabled={mutation.isPending}
          className="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-semibold disabled:opacity-50 hover:bg-emerald-700 transition-colors flex items-center justify-center gap-2"
        >
          {mutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
          Bayar Sekarang
        </button>
      </div>
    </div>
  )
}

// ─── Main page ───────────────────────────────────────────────────────────────

export default function ActivityDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = use(params)
  const [step, setStep] = useState(1)
  const [checkout, setCheckout] = useState<CheckoutState>({
    slot: null,
    pax: 1,
    addons: [],
    guestName: '',
    guestEmail: '',
    guestPhone: '',
    promoCode: '',
    paymentPlan: 'FULL',
  })

  const { data, isLoading, error } = useQuery({
    queryKey: ['activity', slug],
    queryFn: () => api.get<ApiResponse<Activity>>(`/activities/${slug}`),
  })

  const activity = data?.data

  if (isLoading) {
    return (
      <div className="max-w-2xl mx-auto px-4 py-16 flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-emerald-600" />
      </div>
    )
  }

  if (error || !activity) {
    return (
      <div className="max-w-2xl mx-auto px-4 py-16 text-center text-gray-500">
        <AlertCircle className="w-12 h-12 mx-auto mb-3 text-red-400" />
        <p>Aktivitas tidak ditemukan.</p>
      </div>
    )
  }

  const primaryMedia = activity.media?.find(m => m.is_primary) ?? activity.media?.[0]

  return (
    <div className="max-w-2xl mx-auto px-4 py-6">
      {/* Header */}
      <div className="mb-6">
        <a href="/activities" className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 mb-4 transition-colors">
          <ChevronLeft className="w-4 h-4" /> Kembali ke Aktivitas
        </a>

        <div className="rounded-2xl overflow-hidden mb-4 h-56 bg-gradient-to-br from-emerald-100 to-teal-200">
          {primaryMedia ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img src={primaryMedia.url} alt={activity.name} className="w-full h-full object-cover" />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <MapPin className="w-12 h-12 text-emerald-400" />
            </div>
          )}
        </div>

        <div className="flex flex-wrap gap-2 mb-3">
          <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 capitalize">
            {activity.category}
          </span>
          {activity.level && (
            <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 capitalize">
              {activity.level}
            </span>
          )}
        </div>
        <h1 className="text-2xl font-bold text-gray-900">{activity.name}</h1>
        <div className="flex items-center gap-4 mt-2 text-sm text-gray-500">
          <span className="flex items-center gap-1"><Clock className="w-4 h-4" /> {activity.duration_minutes} menit</span>
          <span className="flex items-center gap-1"><Users className="w-4 h-4" /> {activity.min_pax}–{activity.max_pax} orang</span>
        </div>
        {activity.description && (
          <p className="text-sm text-gray-600 mt-3 leading-relaxed">{activity.description}</p>
        )}
      </div>

      {/* Checkout card */}
      <div className="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <StepBar step={step} />
        {step === 1 && (
          <Step1Slots activity={activity} checkout={checkout} setCheckout={setCheckout} onNext={() => setStep(2)} />
        )}
        {step === 2 && (
          <Step2Pax activity={activity} checkout={checkout} setCheckout={setCheckout} onBack={() => setStep(1)} onNext={() => setStep(3)} />
        )}
        {step === 3 && (
          <Step3Guest checkout={checkout} setCheckout={setCheckout} onBack={() => setStep(2)} onNext={() => setStep(4)} />
        )}
        {step === 4 && (
          <Step4Payment activity={activity} checkout={checkout} setCheckout={setCheckout} onBack={() => setStep(3)} />
        )}
      </div>
    </div>
  )
}
