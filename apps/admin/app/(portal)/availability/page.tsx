'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { CalendarDays } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

export default function AvailabilityPage() {
  const token = useAdminAuthStore(s => s.token)
  const [productId, setProductId] = useState('')
  const [from, setFrom] = useState('')
  const [to, setTo] = useState('')
  const [page, setPage] = useState(1)

  const { data: productsData } = useQuery({
    queryKey: ['admin-products-list'],
    queryFn: () => api.get<any>('/admin/products?per_page=100', { token: token! }),
    enabled: !!token,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['admin-availability', productId, from, to, page],
    queryFn: () =>
      api.get<any>(
        `/admin/availability?product_id=${productId}&from=${from}&to=${to}&page=${page}`,
        { token: token! },
      ),
    enabled: !!token,
  })

  const rows = data?.data ?? []
  const meta = data?.meta ?? {}
  const products = productsData?.data ?? []

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <CalendarDays size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Ketersediaan</h1>
          <p className="text-sm text-muted-foreground">Pantau kuota dan slot waktu per produk</p>
        </div>
      </div>

      <div className="flex gap-3 flex-wrap">
        <select
          value={productId}
          onChange={e => { setProductId(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Produk</option>
          {products.map((p: any) => (
            <option key={p.id} value={p.id}>{p.name}</option>
          ))}
        </select>
        <input
          type="date"
          value={from}
          onChange={e => { setFrom(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        />
        <input
          type="date"
          value={to}
          onChange={e => { setTo(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Produk</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Tanggal</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Slot Waktu</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Total Kuota</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Terbooking</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Diblokir</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 6 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                  Tidak ada data ketersediaan.
                </td>
              </tr>
            ) : rows.map((row: any) => {
              const pct = row.total_quota > 0 ? Math.round((row.booked_qty / row.total_quota) * 100) : 0
              return (
                <tr
                  key={row.id}
                  className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
                >
                  <td className="px-4 py-3 font-medium text-foreground">{row.product?.name ?? row.product_name}</td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {new Date(row.date).toLocaleDateString('id-ID')}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">{row.time_slot ?? '—'}</td>
                  <td className="px-4 py-3 text-right">{row.total_quota}</td>
                  <td className="px-4 py-3 text-right">
                    <span className={cn(pct >= 90 ? 'text-red-600 font-semibold' : pct >= 70 ? 'text-amber-600' : '')}>
                      {row.booked_qty}
                    </span>
                    <span className="text-xs text-muted-foreground ml-1">({pct}%)</span>
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={cn(
                        'px-2 py-0.5 rounded-md text-xs font-medium',
                        row.is_blocked
                          ? 'bg-red-50 text-red-700'
                          : 'bg-emerald-50 text-emerald-700',
                      )}
                    >
                      {row.is_blocked ? 'Ya' : 'Tidak'}
                    </span>
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>

      {meta.lastPage > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Total: {meta.total} slot</span>
          <div className="flex gap-2">
            <button
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page === 1}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors"
            >
              Sebelumnya
            </button>
            <span className="px-3 py-1.5">{page} / {meta.lastPage}</span>
            <button
              onClick={() => setPage(p => Math.min(meta.lastPage, p + 1))}
              disabled={page === meta.lastPage}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors"
            >
              Berikutnya
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
