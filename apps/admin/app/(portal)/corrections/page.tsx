'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { ClipboardList, Search } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'
import { DateRangeFilter, rangeForPreset } from '@/components/shared/DateRangeFilter'

const STATUS_COLORS: Record<string, string> = {
  pending:  'bg-yellow-50 text-yellow-700',
  approved: 'bg-emerald-50 text-emerald-700',
  rejected: 'bg-red-50 text-red-700',
}

const STATUS_LABELS: Record<string, string> = {
  pending:  'Menunggu',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

export default function CorrectionsPage() {
  const token    = useAdminAuthStore(s => s.token)
  const can      = useAdminAuthStore(s => s.can)
  const [search,    setSearch]    = useState('')
  const [status,    setStatus]    = useState('')
  const [page,      setPage]      = useState(1)
  const [dateRange, setDateRange] = useState(rangeForPreset('this_month'))

  const canReview = can('corrections.review')
  const endpoint  = canReview ? '/admin/corrections' : '/corrections/mine'

  const columns = [
    'Booking Code',
    ...(canReview ? ['Diajukan Oleh'] : []),
    'Alasan',
    'Status',
    'Tanggal',
  ]

  const { data, isLoading } = useQuery({
    queryKey: ['admin-corrections', endpoint, search, status, dateRange.from, dateRange.to, page],
    queryFn: () =>
      api.get<any>(
        `${endpoint}?search=${search}&status=${status}&from=${dateRange.from}&to=${dateRange.to}&page=${page}`,
        { token: token! },
      ),
    enabled: !!token,
  })

  const corrections = data?.data ?? []
  const meta        = data?.meta ?? {}

  return (
    <div className="space-y-6">
      <PageHeader
        icon={ClipboardList}
        title={canReview ? 'Koreksi' : 'Permintaan Koreksi Saya'}
        description={
          canReview
            ? 'Tinjau dan kelola permintaan koreksi tiket'
            : 'Lihat status permintaan koreksi yang Anda ajukan'
        }
      />

      <DateRangeFilter onChange={range => { setDateRange(range); setPage(1) }} />

      {/* Filters */}
      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari booking code / nama..."
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
          <option value="pending">Menunggu</option>
          <option value="approved">Disetujui</option>
          <option value="rejected">Ditolak</option>
        </select>
      </div>

      <TableCard
        columns={columns}
        isLoading={isLoading}
        isEmpty={corrections.length === 0}
        emptyMessage="Tidak ada permintaan koreksi."
      >
        {corrections.map((item: any) => {
          const statusVal = item.status?.value ?? item.status ?? 'pending'
          return (
            <tr
              key={item.id}
              className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
            >
              <td className="px-4 py-3 font-mono font-medium">
                {item.order?.booking_code ?? item.booking_code ?? '—'}
              </td>
              {canReview && (
                <td className="px-4 py-3">
                  <p className="font-medium text-foreground">{item.requester?.name ?? item.submitted_by ?? '—'}</p>
                  <p className="text-xs text-muted-foreground">{item.requester?.role ?? ''}</p>
                </td>
              )}
              <td className="px-4 py-3 text-muted-foreground max-w-xs truncate">
                {item.reason ?? '—'}
              </td>
              <td className="px-4 py-3">
                <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium uppercase tracking-wide', STATUS_COLORS[statusVal] ?? 'bg-gray-50 text-gray-700')}>
                  {STATUS_LABELS[statusVal] ?? statusVal}
                </span>
              </td>
              <td className="px-4 py-3 text-muted-foreground text-xs">
                {new Date(item.created_at).toLocaleDateString('id-ID')}
              </td>
            </tr>
          )
        })}
      </TableCard>

      <Pagination
        page={page}
        lastPage={meta.lastPage}
        total={meta.total}
        label="koreksi"
        onChange={setPage}
      />
    </div>
  )
}
