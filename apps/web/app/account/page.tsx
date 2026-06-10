'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah } from 'ui'
import Link from 'next/link'
import { useAuthStore } from '@/store/auth'
import type { ApiResponse, Order } from '@/types'
import {
  User,
  LogOut,
  Package,
  ChevronRight,
  Clock,
  CheckCircle,
  XCircle,
  AlertCircle,
} from 'lucide-react'

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'Menunggu',
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

function statusBadge(status: string) {
  if (status === 'paid' || status === 'confirmed') {
    return 'bg-emerald-100 text-emerald-700'
  }
  if (status === 'dp_paid') {
    return 'bg-teal-100 text-teal-700'
  }
  if (status === 'cancelled' || status === 'refunded') {
    return 'bg-red-100 text-red-600'
  }
  if (status === 'expired') {
    return 'bg-orange-100 text-orange-600'
  }
  return 'bg-amber-100 text-amber-700'
}

function StatusIcon({ status }: { status: string }) {
  if (status === 'paid' || status === 'confirmed' || status === 'dp_paid') {
    return <CheckCircle className="w-4 h-4" />
  }
  if (status === 'cancelled' || status === 'refunded') {
    return <XCircle className="w-4 h-4" />
  }
  if (status === 'expired') {
    return <AlertCircle className="w-4 h-4" />
  }
  return <Clock className="w-4 h-4" />
}

export default function AccountPage() {
  const router = useRouter()
  const auth = useAuthStore()

  useEffect(() => {
    if (!auth.isAuthenticated()) {
      router.push('/auth/login?redirect=/account')
    }
  }, [auth, router])

  const { data, isLoading, isError } = useQuery({
    queryKey: ['customer-orders', auth.token],
    queryFn: () =>
      api.get<ApiResponse<Order[]>>('/customer/orders', {
        token: auth.token ?? undefined,
      }),
    enabled: !!auth.token,
  })

  function handleLogout() {
    auth.clear()
    router.push('/')
  }

  const orders = data?.data ?? []

  if (!auth.isAuthenticated()) {
    return null
  }

  return (
    <div className="max-w-2xl mx-auto px-4 py-8">
      {/* Profile Header */}
      <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center">
            <User className="w-6 h-6 text-emerald-700" />
          </div>
          <div>
            <p className="font-semibold text-gray-900">{auth.user?.name ?? 'Pelanggan'}</p>
            <p className="text-sm text-gray-500">{auth.user?.email ?? ''}</p>
          </div>
        </div>
        <button
          onClick={handleLogout}
          className="flex items-center gap-1.5 text-sm text-red-500 hover:text-red-700 transition-colors"
        >
          <LogOut className="w-4 h-4" />
          <span>Keluar</span>
        </button>
      </div>

      {/* Orders */}
      <div>
        <div className="flex items-center gap-2 mb-4">
          <Package className="w-5 h-5 text-gray-600" />
          <h2 className="text-lg font-semibold text-gray-900">Pesanan Saya</h2>
        </div>

        {isLoading && (
          <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, i) => (
              <div
                key={i}
                className="bg-white rounded-2xl border border-gray-100 p-5 animate-pulse"
              >
                <div className="flex justify-between mb-3">
                  <div className="h-4 bg-gray-200 rounded w-1/3" />
                  <div className="h-6 bg-gray-200 rounded-full w-1/5" />
                </div>
                <div className="h-3 bg-gray-200 rounded w-1/4 mb-2" />
                <div className="h-4 bg-gray-200 rounded w-1/2" />
              </div>
            ))}
          </div>
        )}

        {isError && (
          <div className="text-center py-12 text-gray-500">
            <AlertCircle className="w-8 h-8 mx-auto mb-2 text-gray-400" />
            <p className="font-medium">Gagal memuat pesanan.</p>
            <p className="text-sm mt-1">Silakan coba lagi nanti.</p>
          </div>
        )}

        {!isLoading && !isError && orders.length === 0 && (
          <div className="text-center py-16 bg-white rounded-2xl border border-gray-100">
            <Package className="w-10 h-10 mx-auto mb-3 text-gray-300" />
            <p className="font-medium text-gray-600">Belum ada pesanan</p>
            <p className="text-sm text-gray-400 mt-1">
              Yuk, buat pesanan pertamamu!
            </p>
            <Link
              href="/products"
              className="mt-4 inline-block bg-emerald-600 text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-emerald-700 transition-colors"
            >
              Lihat Produk
            </Link>
          </div>
        )}

        {!isLoading && orders.length > 0 && (
          <div className="space-y-3">
            {orders.map((order) => (
              <Link
                key={order.id}
                href={`/payment?orderId=${order.id}&code=${order.bookingCode}`}
                className="block bg-white rounded-2xl border border-gray-100 p-5 hover:border-emerald-200 hover:shadow-sm transition-all"
              >
                <div className="flex items-start justify-between mb-2">
                  <div>
                    <span className="font-mono font-bold text-emerald-700 text-base">
                      {order.bookingCode}
                    </span>
                  </div>
                  <span
                    className={cn(
                      'flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full',
                      statusBadge(order.status),
                    )}
                  >
                    <StatusIcon status={order.status} />
                    {statusLabel(order.status)}
                  </span>
                </div>

                <p className="text-sm text-gray-600 mb-1">{order.customerName}</p>

                <div className="flex items-center justify-between mt-3">
                  <div>
                    <p className="text-xs text-gray-400">Total</p>
                    <p className="font-semibold text-gray-800">{formatRupiah(order.total)}</p>
                    {order.paymentType === 'down_payment' && (
                      <p className="text-xs text-amber-600">
                        Sisa: {formatRupiah(order.remainingAmount)}
                      </p>
                    )}
                  </div>
                  <ChevronRight className="w-5 h-5 text-gray-400" />
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
