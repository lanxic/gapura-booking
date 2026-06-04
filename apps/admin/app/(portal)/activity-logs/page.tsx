'use client'

import { useAdminAuthStore, type AdminRole } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { History, Search } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

const ROLE_LABELS: Record<string, string> = {
  super_admin: 'Super Admin',
  admin:       'Admin',
  supervisor:  'Supervisor',
  kasir:       'Kasir',
  scanner:     'Scanner',
}

const ROLE_COLORS: Record<string, string> = {
  super_admin: 'bg-violet-50 text-violet-700',
  admin:       'bg-blue-50 text-blue-700',
  supervisor:  'bg-amber-50 text-amber-700',
  kasir:       'bg-cyan-50 text-cyan-700',
  scanner:     'bg-gray-100 text-gray-600',
}

const ACTION_COLORS: Record<string, string> = {
  create:  'bg-emerald-50 text-emerald-700',
  update:  'bg-blue-50 text-blue-700',
  delete:  'bg-red-50 text-red-700',
  login:   'bg-gray-100 text-gray-600',
  logout:  'bg-gray-100 text-gray-600',
  approve: 'bg-green-50 text-green-700',
  reject:  'bg-orange-50 text-orange-700',
}

export default function ActivityLogsPage() {
  const token = useAdminAuthStore(s => s.token)
  const [search, setSearch] = useState('')
  const [role, setRole] = useState('')
  const [action, setAction] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-activity-logs', search, role, action, page],
    queryFn: () =>
      api.get<any>(
        `/admin/activity-logs?search=${search}&role=${role}&action=${action}&page=${page}`,
        { token: token! },
      ),
    enabled: !!token,
  })

  const logs = data?.data ?? []
  const meta = data?.meta ?? {}

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-3">
        <History size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Log Aktivitas</h1>
          <p className="text-sm text-muted-foreground">Rekam jejak aktivitas semua pengguna admin</p>
        </div>
      </div>

      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Cari nama pengguna..."
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1) }}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
        <select
          value={role}
          onChange={e => { setRole(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Role</option>
          {(Object.keys(ROLE_LABELS) as AdminRole[]).map(r => (
            <option key={r} value={r}>{ROLE_LABELS[r]}</option>
          ))}
        </select>
        <select
          value={action}
          onChange={e => { setAction(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">Semua Aksi</option>
          {Object.keys(ACTION_COLORS).map(a => (
            <option key={a} value={a}>{a}</option>
          ))}
        </select>
      </div>

      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Pengguna</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Role</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Aksi</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Objek</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Waktu</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 8 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 5 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : logs.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                  Tidak ada log aktivitas.
                </td>
              </tr>
            ) : logs.map((log: any) => {
              const roleVal: string = log.user?.role?.value ?? log.user?.role ?? log.role ?? ''
              const actionVal: string = log.action ?? ''
              return (
                <tr
                  key={log.id}
                  className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors"
                >
                  <td className="px-4 py-3 font-medium text-foreground">
                    {log.user?.name ?? log.user_name ?? '—'}
                  </td>
                  <td className="px-4 py-3">
                    {roleVal ? (
                      <span
                        className={cn(
                          'px-2 py-0.5 rounded-md text-xs font-medium',
                          ROLE_COLORS[roleVal] ?? 'bg-gray-100 text-gray-600',
                        )}
                      >
                        {ROLE_LABELS[roleVal] ?? roleVal}
                      </span>
                    ) : (
                      <span className="text-muted-foreground">—</span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className={cn(
                        'px-2 py-0.5 rounded-md text-xs font-medium',
                        ACTION_COLORS[actionVal] ?? 'bg-gray-100 text-gray-600',
                      )}
                    >
                      {actionVal || '—'}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {log.subject_type ?? '—'}
                    {log.subject_id ? (
                      <span className="text-xs ml-1 opacity-60">#{log.subject_id}</span>
                    ) : null}
                  </td>
                  <td className="px-4 py-3 text-muted-foreground text-xs whitespace-nowrap">
                    {new Date(log.created_at).toLocaleString('id-ID', {
                      dateStyle: 'medium',
                      timeStyle: 'short',
                    })}
                  </td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>

      {meta.lastPage > 1 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>Total: {meta.total} log</span>
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
