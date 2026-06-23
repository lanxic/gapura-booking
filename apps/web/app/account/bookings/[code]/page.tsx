'use client'

import { use, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import { useAuthStore } from '@/store/auth'
import type { Booking, ApiResponse } from '@/types'
import { Calendar, Clock, Users, ArrowLeft, Download, QrCode, BookOpen } from 'lucide-react'
import Link from 'next/link'

type QrResponse = { data: { booking_code: string; qr_svg: string; status: string } }

const STATUS_LABEL: Record<string, { label: string; color: string }> = {
  confirmed: { label: 'Dikonfirmasi', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  attended:  { label: 'Hadir',        color: 'bg-blue-50 text-blue-700 border-blue-200' },
  cancelled: { label: 'Dibatalkan',   color: 'bg-red-50 text-red-600 border-red-200' },
  no_show:   { label: 'Tidak Hadir',  color: 'bg-gray-100 text-gray-500 border-gray-200' },
}

export default function BookingDetailPage({ params }: { params: Promise<{ code: string }> }) {
  const { code } = use(params)
  const router = useRouter()
  const { token, isAuthenticated } = useAuthStore()

  useEffect(() => {
    if (!isAuthenticated()) router.replace(`/auth/login?redirect=/account/bookings/${code}`)
  }, [isAuthenticated, router, code])

  const { data: bookingData, isLoading, isError } = useQuery({
    queryKey: ['my-booking', code],
    queryFn: () => api.get<ApiResponse<Booking>>(`/me/bookings/${code}`, { token: token! }),
    enabled: !!token,
  })

  const { data: qrData } = useQuery({
    queryKey: ['booking-qr', code],
    queryFn: () => api.get<QrResponse>(`/me/bookings/${code}/qr`, { token: token! }),
    enabled: !!token && !!bookingData?.data && ['confirmed', 'attended'].includes(bookingData.data.status),
    retry: false,
  })

  if (!token) return null

  const booking  = bookingData?.data
  const qrSvg    = qrData?.data?.qr_svg
  const st       = booking ? (STATUS_LABEL[booking.status] ?? { label: booking.status, color: 'bg-gray-100 text-gray-500 border-gray-200' }) : null

  if (isLoading) {
    return (
      <main className="mx-auto max-w-lg px-4 py-10 space-y-4">
        <div className="h-6 bg-gray-200 rounded w-1/3 animate-pulse" />
        <div className="h-48 bg-gray-100 rounded-2xl animate-pulse" />
        <div className="h-32 bg-gray-100 rounded-2xl animate-pulse" />
      </main>
    )
  }

  if (isError || !booking) {
    return (
      <main className="mx-auto max-w-lg px-4 py-20 text-center text-gray-400">
        <BookOpen className="w-10 h-10 mx-auto mb-3 text-gray-300" />
        <p className="font-medium">Booking tidak ditemukan.</p>
        <Link href="/account" className="mt-3 inline-block text-sm text-emerald-600 font-semibold">
          ← Kembali
        </Link>
      </main>
    )
  }

  return (
    <main className="mx-auto max-w-lg px-4 py-8 space-y-4">
      <Link href="/account" className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
        <ArrowLeft className="w-4 h-4" /> Riwayat Booking
      </Link>

      {/* Header */}
      <div className="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
        <div className="flex items-start justify-between gap-3">
          <div>
            <p className="text-xs text-gray-400 font-mono">{booking.booking_code}</p>
            <h1 className="mt-1 text-lg font-bold text-gray-900 leading-snug">
              {booking.activity?.name ?? '—'}
            </h1>
          </div>
          {st && (
            <span className={`rounded-full border px-3 py-1 text-xs font-semibold flex-shrink-0 ${st.color}`}>
              {st.label}
            </span>
          )}
        </div>

        <div className="grid grid-cols-2 gap-3 text-sm">
          {booking.slot && (
            <>
              <div className="flex items-center gap-2 text-gray-600">
                <Calendar className="w-4 h-4 text-gray-400 flex-shrink-0" />
                <span>
                  {new Date(booking.slot.date).toLocaleDateString('id-ID', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                  })}
                </span>
              </div>
              <div className="flex items-center gap-2 text-gray-600">
                <Clock className="w-4 h-4 text-gray-400 flex-shrink-0" />
                <span>{booking.slot.start_time} – {booking.slot.end_time}</span>
              </div>
            </>
          )}
          <div className="flex items-center gap-2 text-gray-600">
            <Users className="w-4 h-4 text-gray-400 flex-shrink-0" />
            <span>{booking.pax} Peserta</span>
          </div>
        </div>

        {booking.addons.length > 0 && (
          <div className="border-t border-gray-100 pt-3">
            <p className="text-xs font-semibold text-gray-500 mb-2">Add-on</p>
            <div className="space-y-1">
              {booking.addons.map((a, i) => (
                <div key={i} className="flex justify-between text-sm text-gray-600">
                  <span>{a.name} ×{a.qty}</span>
                  <span>{formatRupiah(a.price * a.qty)}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>

      {/* QR Code */}
      {qrSvg && (
        <div className="rounded-2xl border border-gray-200 bg-white p-5 text-center">
          <p className="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-3">
            Tunjukkan QR ini ke petugas
          </p>
          <div
            className="mx-auto w-48 h-48"
            dangerouslySetInnerHTML={{ __html: qrSvg }}
          />
          <p className="mt-3 text-xs font-mono text-gray-500">{booking.booking_code}</p>
        </div>
      )}

      {/* Waiting for confirmation */}
      {!qrSvg && booking.status === 'confirmed' && (
        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 flex items-start gap-3">
          <QrCode className="w-5 h-5 text-emerald-500 mt-0.5 flex-shrink-0" />
          <div>
            <p className="font-semibold text-emerald-800 text-sm">QR Code siap</p>
            <p className="text-xs text-emerald-600 mt-0.5">
              QR code tersedia. Jika tidak muncul, coba refresh halaman.
            </p>
          </div>
        </div>
      )}

      {/* Invoice summary */}
      {booking.invoice && (
        <div className="rounded-2xl border border-gray-200 bg-white p-5">
          <p className="text-xs font-semibold text-gray-500 mb-3">Ringkasan Pembayaran</p>
          <div className="space-y-2 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>No. Invoice</span>
              <span className="font-mono">{booking.invoice.invoice_code}</span>
            </div>
            <div className="flex justify-between text-gray-600">
              <span>Plan</span>
              <span>{booking.invoice.payment_plan}</span>
            </div>
            {booking.invoice.paid_at && (
              <div className="flex justify-between text-gray-600">
                <span>Dibayar</span>
                <span>{new Date(booking.invoice.paid_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
              </div>
            )}
            <div className="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
              <span>Total</span>
              <span>{formatRupiah(booking.invoice.total_amount)}</span>
            </div>
          </div>
        </div>
      )}
    </main>
  )
}
