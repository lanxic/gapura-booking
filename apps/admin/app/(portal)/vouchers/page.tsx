'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { Percent, Search } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

export default function VouchersPage() {
  const token = useAdminAuthStore(s => s.token)
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-vouchers', search, page],
    queryFn: () =>
      api.get<any>(`/admin/vouchers?search=${search}&page=${page}`, { token: token! }),
    enabled: !!token,
  })

  const vouchers = data?.data ?? []
  const meta = data?.meta ?? {}

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <Percent size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Voucher</h1>
          <p className="text-sm text-muted-foreground">Kelola kode diskon dan promo</p>
        </div>
      </div>

      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari kode voucher..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Kode</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Jenis</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Diskon</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Berlaku Hingga</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Status</th>
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
            ) : vouchers.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                  Tidak ada voucher.
                </td>
              </tr>
            ) : vouchers.map((voucher: any) => {
              const isExpired = voucher.valid_until && new Date(voucher.valid_until) < new Date()
              return (
                <tr
                  key={voucher.id}
                  className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
                >
                  <td className="px-4 py-3 font-mono font-semibold tracking-wide text-foreground">
                    {voucher.code}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={cn(
                        'px-2 py-0.5 rounded-md text-xs font-medium',
                        voucher.type === 'percent'
                          ? 'bg-violet-50 text-violet-700'
                          : 'bg-blue-50 text-blue-700',
                      )}
                    >
                      {voucher.type === 'percent' ? 'Persen' : 'Nominal'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right font-medium">
                    {voucher.type === 'percent'
                      ? `${voucher.discount}%`
                      : `Rp ${Number(voucher.discount).toLocaleString('id-ID')}`}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {voucher.valid_until
                      ? new Date(voucher.valid_until).toLocaleDateString('id-ID')
                      : '—'}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={cn(
                        'px-2 py-0.5 rounded-md text-xs font-medium',
                        !voucher.is_active || isExpired
                          ? 'bg-gray-100 text-gray-500'
                          : 'bg-emerald-50 text-emerald-700',
                      )}
                    >
                      {isExpired ? 'Kadaluarsa' : voucher.is_active ? 'Aktif' : 'Nonaktif'}
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
          <span>Total: {meta.total} voucher</span>
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
