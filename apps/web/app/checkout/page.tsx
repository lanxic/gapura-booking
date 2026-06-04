'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah } from 'ui'
import { useCartStore } from '@/store/cart'
import { useAuthStore } from '@/store/auth'
import type { ApiResponse, Order } from '@/types'
import { Trash2, Loader2 } from 'lucide-react'

const COUNTRY_CODES = [
  { code: '+62', flag: '🇮🇩', name: 'Indonesia' },
  { code: '+1', flag: '🇺🇸', name: 'USA' },
  { code: '+44', flag: '🇬🇧', name: 'UK' },
  { code: '+60', flag: '🇲🇾', name: 'Malaysia' },
  { code: '+65', flag: '🇸🇬', name: 'Singapore' },
  { code: '+61', flag: '🇦🇺', name: 'Australia' },
  { code: '+81', flag: '🇯🇵', name: 'Japan' },
  { code: '+82', flag: '🇰🇷', name: 'Korea' },
  { code: '+86', flag: '🇨🇳', name: 'China' },
]

const COUNTRIES = [
  'Indonesia', 'United States', 'United Kingdom', 'Malaysia', 'Singapore',
  'Australia', 'Japan', 'South Korea', 'China', 'India', 'Germany', 'France',
  'Netherlands', 'Canada', 'New Zealand', 'Other',
]

const schema = z.object({
  customerEmail: z.string().email('Email tidak valid'),
  customerName: z.string().min(2, 'Nama minimal 2 karakter'),
  countryCode: z.string().min(1),
  customerPhone: z
    .string()
    .min(7, 'Nomor telepon minimal 7 digit')
    .regex(/^[0-9]+$/, 'Hanya angka'),
  country: z.string().min(1, 'Pilih negara'),
  agreeTerms: z.literal(true, { errorMap: () => ({ message: 'Anda harus menyetujui syarat & ketentuan' }) }),
  voucherCode: z.string().optional(),
})

type FormValues = z.infer<typeof schema>

export default function CheckoutPage() {
  const router = useRouter()
  const cart = useCartStore()
  const auth = useAuthStore()

  const [voucherMsg, setVoucherMsg] = useState<{ ok: boolean; text: string } | null>(null)
  const [voucherInput, setVoucherInput] = useState('')

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      customerName: auth.user?.name ?? '',
      customerEmail: auth.user?.email ?? '',
      countryCode: '+62',
      country: 'Indonesia',
      agreeTerms: undefined,
    },
  })

  const total = cart.total()
  const discount = cart.voucher?.discount ?? 0

  const applyVoucher = useMutation({
    mutationFn: (code: string) =>
      api.post<ApiResponse<{ discount: number }>>('/vouchers/validate', { code }),
    onSuccess: (res) => {
      cart.setVoucher(voucherInput, res.data.discount)
      setVoucherMsg({ ok: true, text: `Voucher berhasil! Diskon ${formatRupiah(res.data.discount)}` })
    },
    onError: () => {
      cart.clearVoucher()
      setVoucherMsg({ ok: false, text: 'Kode voucher tidak valid atau sudah kadaluarsa.' })
    },
  })

  const createOrder = useMutation({
    mutationFn: (payload: object) =>
      api.post<ApiResponse<Order>>('/orders', payload, {
        token: auth.token ?? undefined,
      }),
    onSuccess: (res) => {
      const order = res.data
      cart.clear()
      router.push(`/booking-success?code=${order.bookingCode}&orderId=${order.id}`)
    },
  })

  function onSubmit(values: FormValues) {
    if (cart.tickets.length === 0) return

    const payload = {
      productSlug: cart.productSlug,
      slotId: cart.slotId,
      date: cart.selectedDate,
      customerName: values.customerName,
      customerEmail: values.customerEmail,
      customerPhone: `${values.countryCode}${values.customerPhone}`,
      paymentType: 'full',
      voucherCode: cart.voucher?.code ?? undefined,
      items: cart.tickets.map((t) => ({
        variantId: t.variantId,
        qtyAdult: t.qtyAdult,
        qtyChild: t.qtyChild,
        addons: t.addons,
      })),
    }

    createOrder.mutate(payload)
  }

  if (cart.tickets.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center text-gray-500">
        <p className="text-lg font-medium">Keranjang kamu kosong.</p>
        <a href="/products" className="text-emerald-600 hover:underline text-sm mt-2 inline-block">
          Lihat produk wisata
        </a>
      </div>
    )
  }

  const visitDate = cart.selectedDate
    ? new Date(cart.selectedDate + 'T00:00:00').toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric',
      })
    : null

  return (
    <div className="max-w-5xl mx-auto px-4 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Checkout</h1>
        <a href="/" className="text-sm text-gray-500 border border-gray-300 px-4 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
          Kembali Ke Beranda
        </a>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left: Notice + Form */}
          <div className="lg:col-span-2 space-y-5">
            {/* Notice */}
            <div className="bg-white border border-gray-200 rounded-xl p-5">
              <h2 className="font-semibold text-gray-800 mb-2">Hal-hal yang perlu diperhatikan</h2>
              <p className="text-sm text-gray-600">
                Mohon dicek kembali transaksi anda, sebelum anda melakukan pembayaran
              </p>
            </div>

            {/* Contact Info */}
            <div className="bg-white border border-gray-200 rounded-xl p-5 space-y-4">
              <h2 className="font-semibold text-gray-800">Kontak Informasi</h2>

              {/* Email */}
              <div>
                <input
                  {...register('customerEmail')}
                  type="email"
                  placeholder="Email*"
                  className={cn(
                    'w-full px-4 py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                    errors.customerEmail ? 'border-red-400' : 'border-gray-200',
                  )}
                />
                {errors.customerEmail && (
                  <p className="text-xs text-red-500 mt-1">{errors.customerEmail.message}</p>
                )}
                <p className="text-xs text-gray-400 mt-1">
                  ℹ Harap berikan email yang valid untuk menerima e-Tiket Anda.
                </p>
              </div>

              {/* Name */}
              <div>
                <input
                  {...register('customerName')}
                  type="text"
                  placeholder="Nama*"
                  className={cn(
                    'w-full px-4 py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                    errors.customerName ? 'border-red-400' : 'border-gray-200',
                  )}
                />
                {errors.customerName && (
                  <p className="text-xs text-red-500 mt-1">{errors.customerName.message}</p>
                )}
              </div>

              {/* Phone with country code */}
              <div>
                <div className="flex gap-2">
                  <div className="relative">
                    <select
                      {...register('countryCode')}
                      className="h-full pl-8 pr-2 py-3 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white appearance-none"
                    >
                      {COUNTRY_CODES.map(c => (
                        <option key={c.code} value={c.code}>{c.flag} {c.code}</option>
                      ))}
                    </select>
                    <span className="absolute left-2 top-1/2 -translate-y-1/2 text-base pointer-events-none">
                      {COUNTRY_CODES.find(c => c.code === watch('countryCode'))?.flag ?? '🇮🇩'}
                    </span>
                  </div>
                  <input
                    {...register('customerPhone')}
                    type="tel"
                    placeholder="Nomor Telepon*"
                    className={cn(
                      'flex-1 px-4 py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                      errors.customerPhone ? 'border-red-400' : 'border-gray-200',
                    )}
                  />
                </div>
                {errors.customerPhone && (
                  <p className="text-xs text-red-500 mt-1">{errors.customerPhone.message}</p>
                )}
              </div>

              {/* Country */}
              <div>
                <select
                  {...register('country')}
                  className={cn(
                    'w-full px-4 py-3 rounded-lg border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white',
                    errors.country ? 'border-red-400' : 'border-gray-200',
                  )}
                >
                  <option value="">Select Country*</option>
                  {COUNTRIES.map(c => (
                    <option key={c} value={c}>{c}</option>
                  ))}
                </select>
                {errors.country && (
                  <p className="text-xs text-red-500 mt-1">{errors.country.message}</p>
                )}
              </div>

              {/* Terms */}
              <div>
                <label className="flex items-start gap-3 cursor-pointer">
                  <input
                    {...register('agreeTerms')}
                    type="checkbox"
                    className="mt-0.5 w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                  />
                  <span className="text-sm text-gray-600">
                    Dengan melakukan pembelian ini, saya menyatakan bahwa saya telah membaca, memahami, dan menyetujui{' '}
                    <a href="#" className="text-emerald-600 hover:underline">
                      Persyaratan dan Ketentuan berikut.*
                    </a>
                  </span>
                </label>
                {errors.agreeTerms && (
                  <p className="text-xs text-red-500 mt-1">{errors.agreeTerms.message}</p>
                )}
              </div>
            </div>
          </div>

          {/* Right: Cart summary */}
          <div className="lg:col-span-1">
            <div className="bg-white border border-gray-200 rounded-xl p-5 sticky top-20 space-y-4">
              <div className="flex items-center justify-between">
                <h2 className="font-semibold text-gray-800">
                  Keranjang Saya ({cart.tickets.length})
                </h2>
                <button
                  type="button"
                  onClick={() => { cart.clear(); router.push('/products') }}
                  className="text-gray-400 hover:text-red-500 transition-colors"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>

              {/* Cart items */}
              {cart.tickets.map(ticket => (
                <div key={ticket.variantId} className="text-sm text-gray-700 space-y-1 border-b border-gray-100 pb-3">
                  <p className="font-semibold text-gray-800 leading-snug">{ticket.variantLabel.split('[')[0].trim()}</p>
                  <p className="text-gray-500 text-xs">{ticket.variantLabel}</p>
                  <p className="text-gray-500">
                    {ticket.qtyAdult > 0 && `${ticket.qtyAdult} X ADULT`}
                    {ticket.qtyAdult > 0 && ticket.qtyChild > 0 && ', '}
                    {ticket.qtyChild > 0 && `${ticket.qtyChild} X CHILD`}
                  </p>
                  {visitDate && <p className="text-gray-500">{visitDate}</p>}
                </div>
              ))}

              {/* Promo code */}
              <div>
                <p className="text-sm font-semibold text-gray-700 mb-2">Masukkan Kode Promo</p>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={voucherInput}
                    onChange={e => setVoucherInput(e.target.value.toUpperCase())}
                    placeholder="Kode Promo"
                    className="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                  />
                  <button
                    type="button"
                    onClick={() => { if (voucherInput) applyVoucher.mutate(voucherInput) }}
                    disabled={applyVoucher.isPending || !voucherInput}
                    className="px-3 py-2 bg-emerald-700 text-white rounded-lg text-sm font-medium hover:bg-emerald-800 disabled:opacity-60 transition-colors"
                  >
                    {applyVoucher.isPending ? <Loader2 className="w-4 h-4 animate-spin" /> : 'Terapkan'}
                  </button>
                </div>
                {voucherMsg && (
                  <p className={cn('text-xs mt-1.5', voucherMsg.ok ? 'text-emerald-600' : 'text-red-500')}>
                    {voucherMsg.text}
                  </p>
                )}
              </div>

              {/* Total */}
              <div className="pt-2 border-t border-gray-100">
                {discount > 0 && (
                  <div className="flex justify-between text-sm text-emerald-600 mb-1">
                    <span>Diskon</span>
                    <span>-{formatRupiah(discount)}</span>
                  </div>
                )}
                <div className="flex justify-between font-bold text-gray-900">
                  <span>Total</span>
                  <span className="text-emerald-700">{formatRupiah(total)}</span>
                </div>
              </div>

              {createOrder.isError && (
                <p className="text-xs text-red-500">
                  {(createOrder.error as Error)?.message ?? 'Gagal membuat pesanan. Coba lagi.'}
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Bottom actions */}
        <div className="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
          <button
            type="button"
            onClick={() => router.back()}
            className="px-6 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Kembali
          </button>
          <button
            type="submit"
            disabled={createOrder.isPending}
            className={cn(
              'px-8 py-2.5 rounded-lg text-sm font-semibold text-white transition-all flex items-center gap-2',
              createOrder.isPending
                ? 'bg-emerald-400 cursor-not-allowed'
                : 'bg-emerald-700 hover:bg-emerald-800',
            )}
          >
            {createOrder.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
            Lanjutkan Untuk Pembayaran
          </button>
        </div>
      </form>
    </div>
  )
}
