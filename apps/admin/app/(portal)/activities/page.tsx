'use client'

import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { cn } from '@/lib/utils'
import {
  MapPin, Plus, Search, Clock, Users, Edit2, Trash2,
  CalendarDays, MoreHorizontal, Loader2, AlertCircle,
} from 'lucide-react'
import Link from 'next/link'

type Activity = {
  id: number
  name: string
  slug: string
  category: 'indoor' | 'outdoor'
  duration_minutes: number
  min_pax: number
  max_pax: number
  level: string | null
  base_price: number
  status: 'active' | 'inactive' | 'archived'
  deleted_at: string | null
  media?: { url: string; is_primary: boolean }[]
}

const STATUS_BADGE: Record<string, string> = {
  active:   'bg-emerald-50 text-emerald-700',
  inactive: 'bg-amber-50 text-amber-700',
  archived: 'bg-gray-100 text-gray-500',
}

const CATEGORY_BADGE: Record<string, string> = {
  indoor:  'bg-blue-50 text-blue-700',
  outdoor: 'bg-teal-50 text-teal-700',
}

function formatRp(n: number) {
  return 'Rp ' + n.toLocaleString('id-ID')
}

export default function ActivitiesAdminPage() {
  const token = useAdminAuthStore(s => s.token)!
  const qc = useQueryClient()
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('')
  const [status, setStatus] = useState('')
  const [page, setPage] = useState(1)
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-activities', { search, category, status, page }],
    queryFn: () => {
      const params = new URLSearchParams()
      if (search) params.set('search', search)
      if (category) params.set('category', category)
      if (status) params.set('status', status)
      params.set('page', String(page))
      return api.get<{ data: Activity[]; meta: { current_page: number; last_page: number; total: number } }>(
        `/admin/activities?${params}`, { token }
      )
    },
  })

  const archiveMutation = useMutation({
    mutationFn: (id: number) => api.delete(`/admin/activities/${id}`, { token }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-activities'] })
      setConfirmDelete(null)
    },
  })

  const generateSlotsMutation = useMutation({
    mutationFn: (id: number) =>
      api.post(`/admin/activities/${id}/generate-slots`, { days: 30 }, { token }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-activities'] }),
  })

  const activities = data?.data ?? []
  const meta = data?.meta

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <MapPin size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Activities</h1>
            <p className="text-sm text-muted-foreground">Kelola aktivitas indoor & outdoor</p>
          </div>
        </div>
        <Link
          href="/activities/new"
          className="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-semibold hover:bg-emerald-700 transition-colors"
        >
          <Plus size={16} /> Aktivitas Baru
        </Link>
      </div>

      {/* Filters */}
      <div className="bg-card border border-border rounded-xl p-4 flex flex-wrap gap-3 items-center">
        <div className="relative flex-1 min-w-[200px]">
          <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari aktivitas..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-4 py-2 text-sm border border-border rounded-lg bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <select
          value={category}
          onChange={e => { setCategory(e.target.value); setPage(1) }}
          className="text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Kategori</option>
          <option value="indoor">Indoor</option>
          <option value="outdoor">Outdoor</option>
        </select>
        <select
          value={status}
          onChange={e => { setStatus(e.target.value); setPage(1) }}
          className="text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">Semua Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      {/* Table */}
      <div className="bg-card border border-border rounded-xl overflow-hidden">
        {isLoading ? (
          <div className="flex items-center justify-center py-20 text-muted-foreground">
            <Loader2 className="animate-spin w-6 h-6 mr-2" /> Memuat...
          </div>
        ) : activities.length === 0 ? (
          <div className="text-center py-16 text-muted-foreground">
            <MapPin className="w-10 h-10 mx-auto mb-3 opacity-30" />
            <p>Belum ada aktivitas</p>
          </div>
        ) : (
          <table className="w-full text-sm">
            <thead className="bg-muted/40 border-b border-border">
              <tr>
                <th className="text-left px-5 py-3 font-medium text-muted-foreground">Aktivitas</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden md:table-cell">Kategori</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden lg:table-cell">Durasi</th>
                <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden lg:table-cell">Peserta</th>
                <th className="text-right px-4 py-3 font-medium text-muted-foreground">Harga</th>
                <th className="text-center px-4 py-3 font-medium text-muted-foreground">Status</th>
                <th className="px-4 py-3" />
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {activities.map(act => (
                <tr key={act.id} className="hover:bg-muted/20 transition-colors">
                  <td className="px-5 py-4">
                    <p className="font-semibold text-foreground line-clamp-1">{act.name}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">{act.slug}</p>
                  </td>
                  <td className="px-4 py-4 hidden md:table-cell">
                    <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full capitalize', CATEGORY_BADGE[act.category] ?? 'bg-gray-100 text-gray-600')}>
                      {act.category}
                    </span>
                  </td>
                  <td className="px-4 py-4 hidden lg:table-cell">
                    <span className="flex items-center gap-1 text-muted-foreground">
                      <Clock size={13} /> {act.duration_minutes} mnt
                    </span>
                  </td>
                  <td className="px-4 py-4 hidden lg:table-cell">
                    <span className="flex items-center gap-1 text-muted-foreground">
                      <Users size={13} /> {act.min_pax}–{act.max_pax}
                    </span>
                  </td>
                  <td className="px-4 py-4 text-right font-semibold text-foreground">
                    {formatRp(act.base_price)}
                  </td>
                  <td className="px-4 py-4 text-center">
                    <span className={cn('text-xs font-medium px-2 py-0.5 rounded-full capitalize', STATUS_BADGE[act.status] ?? '')}>
                      {act.status}
                    </span>
                  </td>
                  <td className="px-4 py-4">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => generateSlotsMutation.mutate(act.id)}
                        disabled={generateSlotsMutation.isPending}
                        title="Generate slots 30 hari"
                        className="p-2 rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                      >
                        <CalendarDays size={15} />
                      </button>
                      <Link
                        href={`/activities/${act.id}`}
                        className="p-2 rounded-lg hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                      >
                        <Edit2 size={15} />
                      </Link>
                      <button
                        onClick={() => setConfirmDelete(act.id)}
                        className="p-2 rounded-lg hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                      >
                        <Trash2 size={15} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex justify-center gap-2">
          <button disabled={page === 1} onClick={() => setPage(p => p - 1)} className="px-4 py-2 text-sm border border-border rounded-lg disabled:opacity-40 hover:bg-muted transition-colors">
            Sebelumnya
          </button>
          <span className="px-4 py-2 text-sm text-muted-foreground">{meta.current_page} / {meta.last_page}</span>
          <button disabled={page === meta.last_page} onClick={() => setPage(p => p + 1)} className="px-4 py-2 text-sm border border-border rounded-lg disabled:opacity-40 hover:bg-muted transition-colors">
            Berikutnya
          </button>
        </div>
      )}

      {/* Confirm delete dialog */}
      {confirmDelete !== null && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-card border border-border rounded-2xl p-6 max-w-sm w-full shadow-xl">
            <div className="flex items-center gap-3 mb-4 text-destructive">
              <AlertCircle size={20} />
              <h3 className="font-bold">Arsipkan Aktivitas?</h3>
            </div>
            <p className="text-sm text-muted-foreground mb-6">
              Aktivitas akan diarsipkan (soft delete) dan tidak akan muncul di webstore. Slot yang sudah dibooking tidak terpengaruh.
            </p>
            <div className="flex gap-3">
              <button onClick={() => setConfirmDelete(null)} className="flex-1 py-2 border border-border rounded-lg text-sm font-medium hover:bg-muted transition-colors">
                Batal
              </button>
              <button
                onClick={() => archiveMutation.mutate(confirmDelete)}
                disabled={archiveMutation.isPending}
                className="flex-1 py-2 bg-destructive text-destructive-foreground rounded-lg text-sm font-medium hover:opacity-90 transition-opacity flex items-center justify-center gap-2"
              >
                {archiveMutation.isPending && <Loader2 size={14} className="animate-spin" />}
                Arsipkan
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
