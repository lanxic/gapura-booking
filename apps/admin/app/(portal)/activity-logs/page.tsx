'use client'

import { useAdminAuthStore, type AdminRole } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery } from '@tanstack/react-query'
import { History, Search } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'

const ROLE_LABELS: Record<string, string> = {
  super_admin: 'Super Admin',
  admin:       'Admin',
  supervisor:  'Supervisor',
  kasir:       'Cashier',
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

const COLUMNS = ['User', 'Role', 'Action', 'Object', 'Time']

export default function ActivityLogsPage() {
  const token = useAdminAuthStore(s => s.token)
  const [search, setSearch] = useState('')
  const [role,   setRole]   = useState('')
  const [action, setAction] = useState('')
  const [page,   setPage]   = useState(1)

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
      <PageHeader
        icon={History}
        title="Activity Logs"
        description="Audit trail of all admin user activities"
      />

      {/* Filters */}
      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search user name..."
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
          <option value="">All Roles</option>
          {(Object.keys(ROLE_LABELS) as AdminRole[]).map(r => (
            <option key={r} value={r}>{ROLE_LABELS[r]}</option>
          ))}
        </select>
        <select
          value={action}
          onChange={e => { setAction(e.target.value); setPage(1) }}
          className="px-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">All Actions</option>
          {Object.keys(ACTION_COLORS).map(a => (
            <option key={a} value={a}>{a}</option>
          ))}
        </select>
      </div>

      <TableCard
        columns={COLUMNS}
        isLoading={isLoading}
        isEmpty={logs.length === 0}
        emptyMessage="No activity logs."
        skeletonRows={8}
      >
        {logs.map((log: any) => {
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
                  <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium', ROLE_COLORS[roleVal] ?? 'bg-gray-100 text-gray-600')}>
                    {ROLE_LABELS[roleVal] ?? roleVal}
                  </span>
                ) : <span className="text-muted-foreground">—</span>}
              </td>
              <td className="px-4 py-3">
                <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium', ACTION_COLORS[actionVal] ?? 'bg-gray-100 text-gray-600')}>
                  {actionVal || '—'}
                </span>
              </td>
              <td className="px-4 py-3 text-muted-foreground">
                {log.subject_type ?? '—'}
                {log.subject_id && <span className="text-xs ml-1 opacity-60">#{log.subject_id}</span>}
              </td>
              <td className="px-4 py-3 text-muted-foreground text-xs whitespace-nowrap">
                {new Date(log.created_at).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' })}
              </td>
            </tr>
          )
        })}
      </TableCard>

      <Pagination
        page={page}
        lastPage={meta.lastPage}
        total={meta.total}
        label="logs"
        onChange={setPage}
      />
    </div>
  )
}
