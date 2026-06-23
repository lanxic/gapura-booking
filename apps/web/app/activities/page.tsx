'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import Link from 'next/link'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { Activity, PaginatedResponse } from '@/types'
import { Search, Clock, Users, MapPin, ChevronRight, SlidersHorizontal } from 'lucide-react'

const CATEGORIES = [
  { value: '', label: 'Semua' },
  { value: 'indoor', label: 'Indoor' },
  { value: 'outdoor', label: 'Outdoor' },
]

const LEVELS = [
  { value: '', label: 'Semua Level' },
  { value: 'beginner', label: 'Beginner' },
  { value: 'intermediate', label: 'Intermediate' },
  { value: 'advanced', label: 'Advanced' },
]

function ActivityCard({ activity }: { activity: Activity }) {
  const primaryMedia = activity.media?.find(m => m.is_primary) ?? activity.media?.[0]
  const levelColors = {
    beginner: 'bg-emerald-100 text-emerald-700',
    intermediate: 'bg-amber-100 text-amber-700',
    advanced: 'bg-red-100 text-red-700',
  }

  return (
    <Link
      href={`/activities/${activity.slug}`}
      className="group block rounded-2xl overflow-hidden border border-gray-200 hover:border-emerald-300 hover:shadow-lg transition-all duration-200 bg-white"
    >
      <div className="relative h-48 bg-gradient-to-br from-emerald-100 to-teal-200 overflow-hidden">
        {primaryMedia ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={primaryMedia.url}
            alt={activity.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <MapPin className="w-12 h-12 text-emerald-400" />
          </div>
        )}
        <div className="absolute top-3 left-3 flex gap-2">
          <span className="text-xs font-semibold px-2 py-1 rounded-full bg-white/90 text-gray-700 capitalize">
            {activity.category}
          </span>
          {activity.level && (
            <span className={`text-xs font-semibold px-2 py-1 rounded-full ${levelColors[activity.level] ?? 'bg-gray-100 text-gray-700'} capitalize`}>
              {activity.level}
            </span>
          )}
        </div>
      </div>

      <div className="p-4">
        <h3 className="font-semibold text-gray-900 text-base line-clamp-1 group-hover:text-emerald-600 transition-colors">
          {activity.name}
        </h3>
        {activity.description && (
          <p className="text-sm text-gray-500 mt-1 line-clamp-2">{activity.description}</p>
        )}
        <div className="flex items-center gap-4 mt-3 text-xs text-gray-500">
          <span className="flex items-center gap-1">
            <Clock className="w-3.5 h-3.5" />
            {activity.duration_minutes} menit
          </span>
          <span className="flex items-center gap-1">
            <Users className="w-3.5 h-3.5" />
            {activity.min_pax}–{activity.max_pax} orang
          </span>
        </div>
        <div className="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
          <div>
            <span className="text-xs text-gray-400">Mulai dari</span>
            <p className="text-lg font-bold text-emerald-600">{formatRupiah(activity.base_price)}</p>
          </div>
          <span className="flex items-center gap-1 text-sm font-medium text-emerald-600 group-hover:underline">
            Lihat Detail <ChevronRight className="w-4 h-4" />
          </span>
        </div>
      </div>
    </Link>
  )
}

export default function ActivitiesPage() {
  const [search, setSearch] = useState('')
  const [category, setCategory] = useState('')
  const [level, setLevel] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['activities', { search, category, level, page }],
    queryFn: () => {
      const params = new URLSearchParams()
      if (search) params.set('search', search)
      if (category) params.set('category', category)
      if (level) params.set('level', level)
      params.set('page', String(page))
      return api.get<PaginatedResponse<Activity>>(`/activities?${params}`)
    },
  })

  const activities = data?.data ?? []
  const meta = (data as { meta?: { current_page: number; last_page: number; total: number } })?.meta

  return (
    <div className="max-w-6xl mx-auto px-4 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Aktivitas</h1>
        <p className="text-gray-500 mt-1">Temukan pengalaman indoor & outdoor terbaik</p>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-2xl border border-gray-200 p-4 mb-6 flex flex-wrap gap-3 items-center">
        <SlidersHorizontal className="w-4 h-4 text-gray-400 shrink-0" />

        {/* Search */}
        <div className="relative flex-1 min-w-[200px]">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            placeholder="Cari aktivitas..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>

        {/* Category */}
        <div className="flex gap-1">
          {CATEGORIES.map(c => (
            <button
              key={c.value}
              onClick={() => { setCategory(c.value); setPage(1) }}
              className={`px-3 py-2 text-sm rounded-lg font-medium transition-colors ${
                category === c.value
                  ? 'bg-emerald-600 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              }`}
            >
              {c.label}
            </button>
          ))}
        </div>

        {/* Level */}
        <select
          value={level}
          onChange={e => { setLevel(e.target.value); setPage(1) }}
          className="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white"
        >
          {LEVELS.map(l => (
            <option key={l.value} value={l.value}>{l.label}</option>
          ))}
        </select>
      </div>

      {/* Grid */}
      {isLoading ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="rounded-2xl border border-gray-200 overflow-hidden animate-pulse">
              <div className="h-48 bg-gray-200" />
              <div className="p-4 space-y-3">
                <div className="h-4 bg-gray-200 rounded w-3/4" />
                <div className="h-3 bg-gray-200 rounded w-full" />
                <div className="h-3 bg-gray-200 rounded w-1/2" />
              </div>
            </div>
          ))}
        </div>
      ) : activities.length === 0 ? (
        <div className="text-center py-20 text-gray-400">
          <MapPin className="w-12 h-12 mx-auto mb-3 opacity-40" />
          <p className="text-lg font-medium">Tidak ada aktivitas ditemukan</p>
          <p className="text-sm mt-1">Coba ubah filter pencarian</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {activities.map(activity => (
            <ActivityCard key={activity.id} activity={activity} />
          ))}
        </div>
      )}

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="flex justify-center gap-2 mt-8">
          <button
            disabled={page === 1}
            onClick={() => setPage(p => p - 1)}
            className="px-4 py-2 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors"
          >
            Sebelumnya
          </button>
          <span className="px-4 py-2 text-sm text-gray-600">
            {meta.current_page} / {meta.last_page}
          </span>
          <button
            disabled={page === meta.last_page}
            onClick={() => setPage(p => p + 1)}
            className="px-4 py-2 text-sm border rounded-lg disabled:opacity-40 hover:bg-gray-50 transition-colors"
          >
            Berikutnya
          </button>
        </div>
      )}
    </div>
  )
}
