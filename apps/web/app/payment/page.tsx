'use client'

import { useSearchParams, useRouter } from 'next/navigation'
import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah, formatDate } from 'ui'
import { useAuthStore } from '@/store/auth'
import type { ApiResponse, Order } from '@/types'
import {
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  Loader2,
  RefreshCw,
  ExternalLink,
} from 'lucide-react'

type PaymentStatusType = 'pending' | 'success' | 'failed' | 'expired'

function StatusIcon({ status }: { status: string }) {
  if (status === 'paid' || status === 'confirmed' || status === 'dp_paid') {
    return <CheckCircle className="w-12 h-12 text-emerald-600" />
  }
  if (status === 'cancelled' || status === 'refunded') {
    return <XCircle className="w-12 h-12 text-red-500" />
  }
  if (status === 'expired') {
    return <AlertCircle className="w-12 h-12 text-orange-500" />
  }
  return <Clock className="w-12 h-12 text-amber-500" />
}

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'Menunggu Pembayaran',
    awaiting_payment: 'Menunggu Pembayaran',
    dp_paid: 'DP Terbayar',
    paid: 'Lunas',
    confirmed: 'Terkonfirmasi',
    cancelled: 'Dibatalkan',
    refunded: 'Dikembalikan',
    expired: 'Kadaluarsa',
  }
  return labels[status] ?? status
}

function statusColor(status: string): string {
  if (status === 'paid' || status === 'confirmed' || status === 'dp_paid') {
    return 'text-emerald-700 bg-emerald-50 border-emerald-200'
  }
  if (status === 'cancelled' || status === 'refunded') {
    return 'text-red-700 bg-red-50 border-red-200'
  }
  if (status === 'expired') {
    return 'text-orange-700 bg-orange-50 border-orange-200'
  }
  return 'text-amber-700 bg-amber-50 border-amber-200'
}

export default function PaymentPage() {
  const searchParams = useSearchParams()
  const router = useRouter()
  const auth = useAuthStore()

  const orderId = searchParams.get('order_id') ?? searchParams.get('orderId') ?? ''
  const bookingCode = searchParams.get('code') ?? ''
  const statusParam = searchParams.get('transaction_status') as PaymentStatusType | null

  const [pollingEnabled, setPollingEnabled] = useState(
    statusParam === 'pending' || statusParam === null,
  )

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['order', orderId],
    queryFn: () =>
      api.get<ApiResponse<Order>>(`/orders/${orderId}`, {
        token: auth.token ?? undefined,
      }),
    enabled: !!orderId,
    refetchInterval: pollingEnabled ? 5000 : false,
  })

  const order = data?.data

  useEffect(() => {
    if (
      order?.status === 'paid' ||
      order?.status === 'confirmed' ||
      order?.status === 'cancelled' ||
      order?.status === 'refunded' ||
      order?.status === 'expired'
    ) {
      setPollingEnabled(false)
    }
  }, [order?.status])

  if (isLoading) {
    return (
      <div className="max-w-lg mx-auto px-4 py-20 flex flex-col items-center gap-4 text-gray-500">
        <Loader2 className="w-8 h-8 animate-spin text-emerald-600" />
        <p>Memuat status pembayaran...</p>
      </div>
    )
  }

  if (!order && !isLoading) {
    return (
      <div className="max-w-lg mx-auto px-4 py-20 text-center text-gray-500">
        <AlertCircle className="w-10 h-10 mx-auto mb-3 text-gray-400" />
        <p className="font-medium">Data pesanan tidak ditemukan.</p>
        {bookingCode && (
          <p className="text-sm mt-1">Kode booking: <strong>{bookingCode}</strong></p>
        )}
        <a href="/" className="text-emerald-600 hover:underline text-sm mt-3 inline-block">
          Kembali ke Beranda
        </a>
      </div>
    )
  }

  const latestPayment = order?.payments?.[order.payments.length - 1]

  return (
    <div className="max-w-lg mx-auto px-4 py-12">
      {/* Status Card */}
      <div className="bg-white rounded-2xl border border-gray-200 p-8 text-center mb-6">
        <div className="flex justify-center mb-4">
          <div
            className={cn(
              'w-20 h-20 rounded-full flex items-center justify-center',
              order?.status === 'paid' || order?.status === 'confirmed' || order?.status === 'dp_paid'
                ? 'bg-emerald-100'
                : order?.status === 'cancelled' || order?.status === 'refunded'
                ? 'bg-red-100'
                : order?.status === 'expired'
                ? 'bg-orange-100'
                : 'bg-amber-100',
            )}
          >
            <StatusIcon status={order?.status ?? 'pending'} />
          </div>
        </div>

        <h1 className="text-xl font-bold text-gray-900 mb-2">
          {statusLabel(order?.status ?? 'pending')}
        </h1>

        <span
          className={cn(
            'inline-block px-3 py-1 rounded-full text-sm font-medium border',
            statusColor(order?.status ?? 'pending'),
          )}
        >
          {statusLabel(order?.status ?? 'pending')}
        </span>

        {(order?.status === 'pending' || order?.status === 'awaiting_payment') && pollingEnabled && (
          <p className="text-sm text-gray-400 mt-3 flex items-center justify-center gap-1">
            <RefreshCw className="w-3.5 h-3.5 animate-spin" />
            Memperbarui otomatis setiap 5 detik...
          </p>
        )}
      </div>

      {/* Order Details */}
      {order && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6 space-y-3">
          <h2 className="font-semibold text-gray-800">Detail Pesanan</h2>

          <div className="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p className="text-gray-400">Kode Booking</p>
              <p className="font-mono font-bold text-emerald-700">{order.bookingCode}</p>
            </div>
            <div>
              <p className="text-gray-400">Nama</p>
              <p className="font-medium text-gray-800">{order.customerName}</p>
            </div>
            <div>
              <p className="text-gray-400">Email</p>
              <p className="font-medium text-gray-800 truncate">{order.customerEmail}</p>
            </div>
            <div>
              <p className="text-gray-400">Telepon</p>
              <p className="font-medium text-gray-800">{order.customerPhone}</p>
            </div>
          </div>

          <div className="border-t border-gray-100 pt-3 space-y-1 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Subtotal</span>
              <span>{formatRupiah(order.subtotal)}</span>
            </div>
            {order.discount > 0 && (
              <div className="flex justify-between text-emerald-600">
                <span>Diskon</span>
                <span>-{formatRupiah(order.discount)}</span>
              </div>
            )}
            <div className="flex justify-between font-bold text-gray-900">
              <span>Total</span>
              <span>{formatRupiah(order.total)}</span>
            </div>
            {order.paymentType === 'down_payment' && (
              <>
                <div className="flex justify-between text-emerald-700 font-medium">
                  <span>DP ({order.dpPercent}%)</span>
                  <span>{formatRupiah(order.dpAmount ?? 0)}</span>
                </div>
                <div className="flex justify-between text-gray-500">
                  <span>Sisa Pelunasan</span>
                  <span>{formatRupiah(order.remainingAmount)}</span>
                </div>
              </>
            )}
          </div>

          {order.expiresAt && (
            <div className="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
              <Clock className="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span>
                Batas pembayaran: {formatDate(order.expiresAt)}
              </span>
            </div>
          )}
        </div>
      )}

      {/* Payment History */}
      {order?.payments && order.payments.length > 0 && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
          <h2 className="font-semibold text-gray-800 mb-3">Riwayat Pembayaran</h2>
          <div className="space-y-2">
            {order.payments.map((payment) => (
              <div
                key={payment.id}
                className="flex items-center justify-between p-3 rounded-xl bg-gray-50 text-sm"
              >
                <div>
                  <p className="font-medium text-gray-800">
                    {payment.paymentType === 'dp' ? 'Uang Muka' :
                     payment.paymentType === 'remaining' ? 'Pelunasan' : 'Pembayaran Penuh'}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">
                    via {payment.gateway}
                    {payment.paidAt && ` — ${formatDate(payment.paidAt)}`}
                  </p>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-800">{formatRupiah(payment.amount)}</p>
                  <span
                    className={cn('text-xs', {
                      'text-emerald-600': payment.status === 'success',
                      'text-amber-500': payment.status === 'pending',
                      'text-red-500': payment.status === 'failed' || payment.status === 'expired',
                      'text-gray-400': payment.status === 'refunded',
                    })}
                  >
                    {payment.status === 'success' ? 'Berhasil' :
                     payment.status === 'pending' ? 'Pending' :
                     payment.status === 'failed' ? 'Gagal' :
                     payment.status === 'expired' ? 'Kadaluarsa' : 'Dikembalikan'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Snap Token / Payment Link */}
      {latestPayment?.snapToken && latestPayment.status === 'pending' && (
        <div className="mb-6">
          <button
            onClick={() => {
              if (typeof window !== 'undefined' && (window as Window & { snap?: { pay: (token: string) => void } }).snap) {
                ;(window as Window & { snap?: { pay: (token: string) => void } }).snap!.pay(latestPayment.snapToken!)
              }
            }}
            className="w-full py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 flex items-center justify-center gap-2 transition-colors"
          >
            <ExternalLink className="w-4 h-4" /> Bayar Sekarang
          </button>
        </div>
      )}

      {/* Actions */}
      <div className="flex flex-col gap-3">
        <button
          onClick={() => { setPollingEnabled(false); refetch() }}
          disabled={isRefetching}
          className="w-full py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 flex items-center justify-center gap-2 transition-colors"
        >
          {isRefetching ? (
            <Loader2 className="w-4 h-4 animate-spin" />
          ) : (
            <RefreshCw className="w-4 h-4" />
          )}
          Refresh Status
        </button>
        <a
          href="/account"
          className="w-full py-2.5 text-center border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
        >
          Lihat Semua Pesanan
        </a>
        <a
          href="/"
          className="text-center text-sm text-emerald-600 hover:underline"
        >
          Kembali ke Beranda
        </a>
      </div>
    </div>
  )
}
