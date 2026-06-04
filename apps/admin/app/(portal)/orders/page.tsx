'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { ShoppingBag, Search } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

const STATUS_COLORS: Record<string, string> = {
  pending:          'bg-yellow-50 text-yellow-700',
  awaiting_payment: 'bg-orange-50 text-orange-700',
  dp_paid:          'bg-blue-50 text-blue-700',
  paid:             'bg-emerald-50 text-emerald-700',
  confirmed:        'bg-green-50 text-green-700',
  cancelled:        'bg-red-50 text-red-700',
  refunded:         'bg-gray-50 text-gray-700',
  expired:          'bg-gray-50 text-gray-500',
}

export default function OrdersPage() {
  const token = useAdminAuthStore(s => s.token)
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-orders', search, status, page],
    queryFn: () => api.get<any>(`/admin/orders?search=${search}&status=${status}&page=${page}`, { token: token! }),
    enabled: !!token,
  })

  const orders = data?.data ?? []
  const meta   = data?.meta ?? {}

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <ShoppingBag size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Pesanan</h1>
          <p className="text-sm text-muted-foreground">Kelola semua pesanan tiket</p>
        </div>
      </div>

      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari booking code / nama / email..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <select
          value={status}
          onChange={e => { setStatus(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Status</option>
          {Object.keys(STATUS_COLORS).map(s => (
            <option key={s} value={s}>{s.replace('_', ' ')}</option>
          ))}
        </select>
      </div>

      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Booking Code</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Pelanggan</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Total</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Status</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Tanggal</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 5 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : orders.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">Tidak ada pesanan.</td>
              </tr>
            ) : orders.map((order: any) => (
              <tr key={order.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                <td className="px-4 py-3 font-mono font-medium">{order.booking_code}</td>
                <td className="px-4 py-3">
                  <p className="font-medium text-foreground">{order.customer_name}</p>
                  <p className="text-xs text-muted-foreground">{order.customer_email}</p>
                </td>
                <td className="px-4 py-3">
                  Rp {order.total?.toLocaleString('id-ID')}
                </td>
                <td className="px-4 py-3">
                  <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium', STATUS_COLORS[order.status?.value ?? order.status] ?? 'bg-gray-50 text-gray-700')}>
                    {order.status?.value ?? order.status}
                  </span>
                </td>
                <td className="px-4 py-3 text-muted-foreground text-xs">
                  {new Date(order.created_at).toLocaleDateString('id-ID')}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {meta.lastPage > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Total: {meta.total} pesanan</span>
          <div className="flex gap-2">
            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors">
              Sebelumnya
            </button>
            <span className="px-3 py-1.5">{page} / {meta.lastPage}</span>
            <button onClick={() => setPage(p => Math.min(meta.lastPage, p + 1))} disabled={page === meta.lastPage}
              className="px-3 py-1.5 rounded-md border border-border hover:bg-accent disabled:opacity-40 transition-colors">
              Berikutnya
            </button>
          </div>
        </div>
      )}
    </div>
  )
}
