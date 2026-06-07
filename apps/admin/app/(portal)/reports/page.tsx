'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { BarChart2, TrendingUp, ShoppingBag, Banknote } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import { DateRangeFilter, rangeForPreset } from '@/components/shared/DateRangeFilter'

export default function ReportsPage() {
  const token = useAdminAuthStore(s => s.token)

  const defaultRange = rangeForPreset('this_month')
  const [applied, setApplied] = useState(defaultRange)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-reports-sales', applied.from, applied.to],
    queryFn: () =>
      api.get<any>(`/admin/reports/sales?from=${applied.from}&to=${applied.to}`, { token: token! }),
    enabled: !!token,
  })

  const summary = data?.summary ?? data?.data ?? {}
  const daily: any[] = data?.daily ?? []

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <BarChart2 size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Laporan Penjualan</h1>
          <p className="text-sm text-muted-foreground">Ringkasan pendapatan dan pesanan berdasarkan rentang tanggal</p>
        </div>
      </div>

      <DateRangeFilter onChange={setApplied} />

      {/* Summary Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <SummaryCard
          label="Total Pendapatan"
          icon={Banknote}
          color="text-emerald-600"
          bg="bg-emerald-50"
          isLoading={isLoading}
          value={
            summary.total_revenue != null
              ? `Rp ${Number(summary.total_revenue).toLocaleString('id-ID')}`
              : '—'
          }
        />
        <SummaryCard
          label="Total Pesanan"
          icon={ShoppingBag}
          color="text-blue-600"
          bg="bg-blue-50"
          isLoading={isLoading}
          value={summary.total_orders != null ? String(summary.total_orders) : '—'}
        />
        <SummaryCard
          label="Rata-rata per Pesanan"
          icon={TrendingUp}
          color="text-violet-600"
          bg="bg-violet-50"
          isLoading={isLoading}
          value={
            summary.total_revenue != null && summary.total_orders > 0
              ? `Rp ${Math.round(summary.total_revenue / summary.total_orders).toLocaleString('id-ID')}`
              : '—'
          }
        />
      </div>

      {/* Daily Table */}
      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <div className="px-4 py-3 border-b border-border bg-muted/30">
          <h2 className="text-sm font-semibold text-foreground">Rincian Harian</h2>
        </div>
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/10">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Tanggal</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Jumlah Pesanan</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Pendapatan</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 7 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 3 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : daily.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-8 text-center text-muted-foreground">
                  Tidak ada data untuk rentang tanggal ini.
                </td>
              </tr>
            ) : daily.map((row: any, i: number) => (
              <tr
                key={row.date ?? i}
                className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
              >
                <td className="px-4 py-3 text-muted-foreground">
                  {new Date(row.date).toLocaleDateString('id-ID', {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                  })}
                </td>
                <td className="px-4 py-3 text-right">{row.total_orders ?? 0}</td>
                <td className="px-4 py-3 text-right font-medium">
                  Rp {Number(row.total_revenue ?? 0).toLocaleString('id-ID')}
                </td>
              </tr>
            ))}
          </tbody>
          {daily.length > 0 && !isLoading && (
            <tfoot>
              <tr className="border-t-2 border-border bg-muted/20">
                <td className="px-4 py-3 font-semibold text-foreground">Total</td>
                <td className="px-4 py-3 text-right font-semibold">
                  {daily.reduce((s: number, r: any) => s + (r.total_orders ?? 0), 0)}
                </td>
                <td className="px-4 py-3 text-right font-semibold text-emerald-700">
                  Rp{' '}
                  {daily
                    .reduce((s: number, r: any) => s + Number(r.total_revenue ?? 0), 0)
                    .toLocaleString('id-ID')}
                </td>
              </tr>
            </tfoot>
          )}
        </table>
      </div>
    </div>
  )
}

function SummaryCard({
  label,
  icon: Icon,
  value,
  color,
  bg,
  isLoading,
}: {
  label: string
  icon: React.ElementType
  value: string
  color: string
  bg: string
  isLoading: boolean
}) {
  return (
    <div className="rounded-xl border border-border bg-card p-5 flex items-start gap-4">
      <div className={cn('p-2.5 rounded-lg', bg)}>
        <Icon size={20} className={color} />
      </div>
      <div className="min-w-0">
        <p className="text-sm text-muted-foreground">{label}</p>
        {isLoading ? (
          <div className="mt-1.5 h-7 w-32 bg-muted rounded animate-pulse" />
        ) : (
          <p className={cn('mt-1 text-2xl font-bold', color)}>{value}</p>
        )}
      </div>
    </div>
  )
}
