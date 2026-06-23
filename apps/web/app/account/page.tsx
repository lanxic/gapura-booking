'use client'

import { useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import { useAuthStore } from '@/store/auth'
import type { Booking, PaginatedResponse } from '@/types'
import { Calendar, Clock, Users, ChevronRight, LogOut, BookOpen } from 'lucide-react'
import Link from 'next/link'

const STATUS_LABEL: Record<string, { label: string; color: string }> = {
  confirmed: { label: 'Dikonfirmasi', color: 'bg-emerald-50 text-emerald-700' },
  attended:  { label: 'Hadir',        color: 'bg-blue-50 text-blue-700' },
  cancelled: { label: 'Dibatalkan',   color: 'bg-red-50 text-red-600' },
  no_show:   { label: 'Tidak Hadir',  color: 'bg-gray-100 text-gray-500' },
}

function BookingRow({ booking }: { booking: Booking }) {
  const st = STATUS_LABEL[booking.status] ?? { label: booking.status, color: 'bg-gray-100 text-gray-500' }

  return (
    <Link
      href={`/account/bookings/${booking.booking_code}`}
      className="flex items-start gap-4 p-4 hover:bg-gray-50 transition-colors rounded-xl border border-gray-200 bg-white"
    >
      {booking.activity?.image ? (
        <img
          src={booking.activity.image}
          alt={booking.activity.name}
          className="w-16 h-16 rounded-xl object-cover flex-shrink-0"
        />
      ) : (
        <div className="w-16 h-16 rounded-xl bg-emerald-100 flex-shrink-0 flex items-center justify-center">
          <BookOpen className="w-6 h-6 text-emerald-400" />
        </div>
      )}

      <div className="flex-1 min-w-0">
        <div className="flex items-start justify-between gap-2">
          <p className="font-semibold text-gray-900 leading-snug line-clamp-1">
            {booking.activity?.name ?? '—'}
          </p>
          <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold flex-shrink-0 ${st.color}`}>
            {st.label}
          </span>
        </div>

        <div className="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-gray-500">
          {booking.slot && (
            <span className="flex items-center gap-1">
              <Calendar className="w-3 h-3" />
              {new Date(booking.slot.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
            </span>
          )}
          {booking.slot && (
            <span className="flex items-center gap-1">
              <Clock className="w-3 h-3" /> {booking.slot.start_time}
            </span>
          )}
          <span className="flex items-center gap-1">
            <Users className="w-3 h-3" /> {booking.pax} pax
          </span>
        </div>

        <div className="mt-2 flex items-center justify-between">
          <p className="text-xs text-gray-400 font-mono">{booking.booking_code}</p>
          {booking.invoice && (
            <p className="text-sm font-bold text-gray-900">
              {formatRupiah(booking.invoice.total_amount)}
            </p>
          )}
        </div>
      </div>

      <ChevronRight className="w-4 h-4 text-gray-400 flex-shrink-0 mt-1" />
    </Link>
  )
}

export default function AccountPage() {
  const router   = useRouter()
  const { user, token, clear, isAuthenticated } = useAuthStore()

  useEffect(() => {
    if (!isAuthenticated()) router.replace('/auth/login?redirect=/account')
  }, [isAuthenticated, router])

  const { data, isLoading } = useQuery({
    queryKey: ['my-bookings'],
    queryFn: () => api.get<PaginatedResponse<Booking>>('/me/bookings', { token: token! }),
    enabled: !!token,
  })

  const bookings = data?.data ?? []

  if (!user) return null

  return (
    <main className="mx-auto max-w-2xl px-4 py-10">
      {/* Profile header */}
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center gap-3">
          <div className="w-12 h-12 rounded-full bg-emerald-500 flex items-center justify-center text-white text-lg font-bold">
            {user.name.charAt(0).toUpperCase()}
          </div>
          <div>
            <p className="font-bold text-gray-900">{user.name}</p>
            <p className="text-sm text-gray-500">{user.email}</p>
          </div>
        </div>
        <button
          onClick={() => { clear(); router.push('/') }}
          className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-red-500 transition-colors"
        >
          <LogOut className="w-4 h-4" /> Keluar
        </button>
      </div>

      <h2 className="font-bold text-gray-900 mb-4">Riwayat Booking</h2>

      {isLoading && (
        <div className="space-y-3">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="h-24 bg-gray-100 rounded-xl animate-pulse" />
          ))}
        </div>
      )}

      {!isLoading && bookings.length === 0 && (
        <div className="text-center py-20 text-gray-400">
          <BookOpen className="w-10 h-10 mx-auto mb-3 text-gray-300" />
          <p className="font-medium">Belum ada booking.</p>
          <Link href="/activities" className="mt-3 inline-block text-sm text-emerald-600 font-semibold hover:underline">
            Jelajahi Aktivitas
          </Link>
        </div>
      )}

      {bookings.length > 0 && (
        <div className="space-y-3">
          {bookings.map((b) => <BookingRow key={b.booking_code} booking={b} />)}
        </div>
      )}
    </main>
  )
}
