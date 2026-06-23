'use client'

import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useAdminAuthStore } from '@/store/auth'
import { formatRupiah } from 'ui'
import {
  LayoutDashboard, Calendar, BookOpen, TrendingUp,
  AlertCircle, Clock, Users,
} from 'lucide-react'

type DashboardStats = {
  summary: {
    bookings_today: number
    bookings_month: number
    revenue_today: number
    revenue_month: number
    pending_invoices: number
    active_activities: number
  }
  slots_today: {
    slot_id: number
    activity_name: string
    start_time: string
    capacity: number
    booked: number
  }[]
  recent_bookings: {
    booking_code: string
    guest_name: string
    activity_name: string
    slot_date: string
    slot_time: string
    pax: number
    status: string
    total_amount: number
    created_at: string
  }[]
  revenue_chart: {
    date: string
    revenue: number
    bookings: number
  }[]
}

const STATUS_COLOR: Record<string, string> = {
  confirmed: 'bg-emerald-50 text-emerald-700',
  attended:  'bg-blue-50 text-blue-700',
  cancelled: 'bg-red-50 text-red-600',
  no_show:   'bg-gray-100 text-gray-500',
}
const STATUS_LABEL: Record<string, string> = {
  confirmed: 'Dikonfirmasi',
  attended:  'Hadir',
  cancelled: 'Dibatalkan',
  no_show:   'Tidak Hadir',
}

function StatCard({
  label, value, sub, icon: Icon, color,
}: {
  label: string
  value: string
  sub?: string
  icon: React.ElementType
  color: string
}) {
  return (
    <div className="rounded-xl border border-border bg-card p-5">
      <div className="flex items-center justify-between">
        <p className="text-sm text-muted-foreground">{label}</p>
        <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${color}`}>
          <Icon className="w-4 h-4" />
        </div>
      </div>
      <p className="mt-3 text-2xl font-bold text-foreground">{value}</p>
      {sub && <p className="mt-0.5 text-xs text-muted-foreground">{sub}</p>}
    </div>
  )
}

export default function DashboardPage() {
  const { token } = useAdminAuthStore()

  const { data, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => api.get<{ data: DashboardStats }>('/admin/dashboard/stats', { token: token! }),
    enabled: !!token,
    refetchInterval: 60_000,
  })

  const stats = data?.data?.summary
  const slots = data?.data?.slots_today ?? []
  const recent = data?.data?.recent_bookings ?? []
  const chart  = data?.data?.revenue_chart ?? []

  const maxRevenue = Math.max(...chart.map((d) => d.revenue), 1)

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <LayoutDashboard size={22} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Dashboard</h1>
          <p className="text-sm text-muted-foreground">Ringkasan aktivitas hari ini</p>
        </div>
      </div>

      {/* Stat cards */}
      {isLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-28 rounded-xl border border-border bg-card animate-pulse" />
          ))}
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <StatCard
            label="Booking Hari Ini" icon={BookOpen} color="bg-emerald-100 text-emerald-600"
            value={String(stats?.bookings_today ?? 0)}
            sub={`${stats?.bookings_month ?? 0} bulan ini`}
          />
          <StatCard
            label="Pendapatan Hari Ini" icon={TrendingUp} color="bg-blue-100 text-blue-600"
            value={formatRupiah(stats?.revenue_today ?? 0)}
            sub={`${formatRupiah(stats?.revenue_month ?? 0)} bulan ini`}
          />
          <StatCard
            label="Invoice Menunggu" icon={AlertCircle} color="bg-amber-100 text-amber-600"
            value={String(stats?.pending_invoices ?? 0)}
            sub="belum dibayar"
          />
          <StatCard
            label="Aktivitas Aktif" icon={Calendar} color="bg-violet-100 text-violet-600"
            value={String(stats?.active_activities ?? 0)}
            sub="tersedia untuk booking"
          />
          <StatCard
            label="Slot Hari Ini" icon={Clock} color="bg-teal-100 text-teal-600"
            value={String(slots.length)}
            sub="sesi terjadwal"
          />
          <StatCard
            label="Peserta Hari Ini" icon={Users} color="bg-rose-100 text-rose-600"
            value={String(slots.reduce((s, sl) => s + sl.booked, 0))}
            sub={`dari ${slots.reduce((s, sl) => s + sl.capacity, 0)} kapasitas`}
          />
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
        {/* Revenue chart — last 7 days */}
        <div className="rounded-xl border border-border bg-card p-5">
          <h2 className="font-semibold text-foreground mb-4">Pendapatan 7 Hari Terakhir</h2>
          {isLoading ? (
            <div className="h-40 bg-gray-100 rounded-lg animate-pulse" />
          ) : (
            <div className="flex items-end gap-2 h-40">
              {chart.map((d) => (
                <div key={d.date} className="flex-1 flex flex-col items-center gap-1">
                  <p className="text-[10px] text-muted-foreground">{formatRupiah(d.revenue)}</p>
                  <div
                    className="w-full bg-emerald-500 rounded-t-sm transition-all"
                    style={{ height: `${Math.max(4, (d.revenue / maxRevenue) * 120)}px` }}
                    title={`${d.date}: ${formatRupiah(d.revenue)}`}
                  />
                  <p className="text-[10px] text-muted-foreground">
                    {new Date(d.date).toLocaleDateString('id-ID', { weekday: 'short' })}
                  </p>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Slots today */}
        <div className="rounded-xl border border-border bg-card p-5">
          <h2 className="font-semibold text-foreground mb-4">Slot Hari Ini</h2>
          {isLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 3 }).map((_, i) => <div key={i} className="h-10 bg-gray-100 rounded animate-pulse" />)}
            </div>
          ) : slots.length === 0 ? (
            <p className="text-sm text-muted-foreground text-center py-8">Tidak ada slot hari ini.</p>
          ) : (
            <div className="space-y-2">
              {slots.map((sl) => {
                const fill = sl.capacity > 0 ? (sl.booked / sl.capacity) * 100 : 0
                return (
                  <div key={sl.slot_id} className="space-y-1">
                    <div className="flex items-center justify-between text-sm">
                      <span className="font-medium text-foreground truncate">{sl.activity_name}</span>
                      <span className="text-xs text-muted-foreground flex-shrink-0 ml-2">{sl.start_time}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all ${fill >= 100 ? 'bg-red-400' : 'bg-emerald-400'}`}
                          style={{ width: `${Math.min(fill, 100)}%` }}
                        />
                      </div>
                      <span className="text-xs text-muted-foreground flex-shrink-0">
                        {sl.booked}/{sl.capacity}
                      </span>
                    </div>
                  </div>
                )
              })}
            </div>
          )}
        </div>
      </div>

      {/* Recent bookings */}
      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <div className="px-5 py-4 border-b border-border">
          <h2 className="font-semibold text-foreground">Booking Terbaru</h2>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border/50 bg-muted/30">
              <th className="px-4 py-2.5 text-left font-medium text-muted-foreground">Kode</th>
              <th className="px-4 py-2.5 text-left font-medium text-muted-foreground">Tamu</th>
              <th className="px-4 py-2.5 text-left font-medium text-muted-foreground hidden md:table-cell">Aktivitas</th>
              <th className="px-4 py-2.5 text-left font-medium text-muted-foreground hidden lg:table-cell">Total</th>
              <th className="px-4 py-2.5 text-left font-medium text-muted-foreground">Status</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border/30">
                  <td colSpan={5} className="px-4 py-3">
                    <div className="h-4 bg-gray-100 rounded animate-pulse" />
                  </td>
                </tr>
              ))
            ) : recent.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-12 text-center text-muted-foreground">
                  Belum ada booking.
                </td>
              </tr>
            ) : (
              recent.map((b) => (
                <tr key={b.booking_code} className="border-b border-border/30 hover:bg-muted/10 transition-colors">
                  <td className="px-4 py-3 font-mono text-xs text-foreground">{b.booking_code}</td>
                  <td className="px-4 py-3">
                    <p className="font-medium text-foreground">{b.guest_name}</p>
                    <p className="text-xs text-muted-foreground">{b.pax} pax</p>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground hidden md:table-cell">
                    <p>{b.activity_name}</p>
                    <p className="text-xs">{b.slot_date} {b.slot_time}</p>
                  </td>
                  <td className="px-4 py-3 font-medium text-foreground hidden lg:table-cell">
                    {formatRupiah(b.total_amount)}
                  </td>
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-2.5 py-0.5 text-xs font-semibold ${STATUS_COLOR[b.status] ?? 'bg-gray-100 text-gray-500'}`}>
                      {STATUS_LABEL[b.status] ?? b.status}
                    </span>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  )
}
