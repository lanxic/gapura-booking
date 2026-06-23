'use client'

import { useAdminAuthStore, type AdminRole } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  Users, Search, Plus, X, Loader2, Eye, EyeOff,
  Mail, Shield, Calendar, CheckCircle, AlertCircle, UserX, ShieldOff,
} from 'lucide-react'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'
import { useState } from 'react'
import { cn } from '@/lib/utils'

// ── Constants ──────────────────────────────────────────────────────────────

const ROLE_LABELS: Record<string, string> = {
  super_admin: 'Super Admin',
  admin:       'Admin',
  scanner:     'Scanner',
}

const ROLE_COLORS: Record<string, string> = {
  super_admin: 'bg-violet-50 text-violet-700',
  admin:       'bg-blue-50 text-blue-700',
  scanner:     'bg-gray-100 text-gray-600',
}

const AVATAR_BG: Record<string, string> = {
  super_admin: 'bg-violet-500',
  admin:       'bg-blue-500',
  scanner:     'bg-gray-400',
}

const EDITABLE_ROLES: AdminRole[] = ['admin', 'scanner']

// ── Types ──────────────────────────────────────────────────────────────────

type User = {
  id: number
  name: string
  email: string
  role: AdminRole | { value: AdminRole }
  is_active: boolean
  created_at: string
  created_by?: number
}

function resolveRole(user: User): AdminRole {
  return typeof user.role === 'object' ? user.role.value : user.role
}

// ── Small shared UI ────────────────────────────────────────────────────────

function Avatar({ name, role, size = 'md' }: { name: string; role: string; size?: 'sm' | 'md' | 'lg' }) {
  const initials = name?.slice(0, 2)?.toUpperCase() || 'AU'
  const sz = size === 'lg' ? 'h-14 w-14 text-lg' : size === 'sm' ? 'h-7 w-7 text-[10px]' : 'h-9 w-9 text-xs'
  return (
    <div className={cn('shrink-0 rounded-full text-white font-bold flex items-center justify-center', sz, AVATAR_BG[role] ?? 'bg-emerald-500')}>
      {initials}
    </div>
  )
}

function PasswordInput({ value, onChange, placeholder }: { value: string; onChange: (v: string) => void; placeholder?: string }) {
  const [show, setShow] = useState(false)
  return (
    <div className="relative">
      <input
        type={show ? 'text' : 'password'}
        value={value}
        onChange={e => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full rounded-lg border border-input bg-background px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
      />
      <button type="button" onClick={() => setShow(v => !v)}
        className="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors">
        {show ? <EyeOff size={15} /> : <Eye size={15} />}
      </button>
    </div>
  )
}

function InfoRow({ icon: Icon, label, value }: { icon: React.ElementType; label: string; value: React.ReactNode }) {
  return (
    <div className="flex items-start gap-3">
      <Icon size={15} className="text-muted-foreground mt-0.5 shrink-0" />
      <div className="min-w-0">
        <p className="text-xs text-muted-foreground">{label}</p>
        <p className="text-sm font-medium text-foreground truncate">{value}</p>
      </div>
    </div>
  )
}

// ── Create modal ───────────────────────────────────────────────────────────

function CreateUserModal({ onClose, token }: { onClose: () => void; token: string }) {
  const queryClient = useQueryClient()
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'admin' as AdminRole })
  const [error, setError] = useState('')

  const mutation = useMutation({
    mutationFn: (payload: typeof form) => api.post<any>('/admin/users', payload, { token }),
    onSuccess: () => { queryClient.invalidateQueries({ queryKey: ['admin-users'] }); onClose() },
    onError: (e: any) => setError(e?.message ?? 'Failed to create user'),
  })

  const set = (k: keyof typeof form) => (v: string) => setForm(p => ({ ...p, [k]: v }))

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div className="w-full max-w-md mx-4 rounded-2xl border border-border bg-card shadow-2xl">
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h2 className="text-base font-semibold">Add User</h2>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors"><X size={18} /></button>
        </div>

        <div className="px-6 py-5 space-y-4">
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1">Full Name</label>
            <input value={form.name} onChange={e => set('name')(e.target.value)} placeholder="Budi Santoso"
              className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition" />
          </div>
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1">Email</label>
            <input type="email" value={form.email} onChange={e => set('email')(e.target.value)} placeholder="budi@example.com"
              className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition" />
          </div>
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1">Password</label>
            <PasswordInput value={form.password} onChange={set('password')} placeholder="Min. 8 characters" />
          </div>
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1">Role</label>
            <select value={form.role} onChange={e => set('role')(e.target.value)}
              className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition">
              {EDITABLE_ROLES.map(r => <option key={r} value={r}>{ROLE_LABELS[r]}</option>)}
            </select>
          </div>
          {error && <p className="text-sm text-destructive bg-destructive/10 rounded-lg px-3 py-2">{error}</p>}
        </div>

        <div className="flex justify-end gap-3 px-6 py-4 border-t border-border">
          <button onClick={onClose}
            className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">
            Cancel
          </button>
          <button onClick={() => mutation.mutate(form)} disabled={mutation.isPending}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors">
            {mutation.isPending && <Loader2 size={14} className="animate-spin" />}
            Create User
          </button>
        </div>
      </div>
    </div>
  )
}

// ── Detail / edit modal ────────────────────────────────────────────────────

function UserDetailModal({ user, onClose, token, currentUserId }: {
  user: User; onClose: () => void; token: string; currentUserId: number
}) {
  const queryClient = useQueryClient()
  const role = resolveRole(user)
  const isSelf = user.id === currentUserId
  const isSuper = role === 'super_admin'

  const [name, setName]           = useState(user.name)
  const [editRole, setEditRole]   = useState(role)
  const [isActive, setIsActive]   = useState(user.is_active)
  const [password, setPassword]   = useState('')
  const [saved, setSaved]         = useState(false)
  const [saveError, setSaveError] = useState('')

  const invalidate = () => queryClient.invalidateQueries({ queryKey: ['admin-users'] })

  const updateMutation = useMutation({
    mutationFn: () => api.put<any>(`/admin/users/${user.id}`, {
      name,
      role: editRole,
      is_active: isActive,
      ...(password ? { password } : {}),
    }, { token }),
    onSuccess: () => {
      invalidate()
      setSaved(true)
      setPassword('')
      setTimeout(() => setSaved(false), 3000)
    },
    onError: (e: any) => setSaveError(e?.message ?? 'Failed to save'),
  })

  const deactivateMutation = useMutation({
    mutationFn: () => api.delete<any>(`/admin/users/${user.id}`, { token }),
    onSuccess: () => { invalidate(); onClose() },
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
      <div className="w-full max-w-lg mx-4 rounded-2xl border border-border bg-card shadow-2xl">

        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-border">
          <h2 className="text-base font-semibold">User Details</h2>
          <button onClick={onClose} className="text-muted-foreground hover:text-foreground transition-colors"><X size={18} /></button>
        </div>

        <div className="px-6 py-5 space-y-6 max-h-[75vh] overflow-y-auto">

          {/* Identity card */}
          <div className="flex items-center gap-4">
            <Avatar name={user.name} role={role} size="lg" />
            <div className="min-w-0">
              <p className="text-base font-semibold text-foreground truncate">{user.name}</p>
              <span className={cn('inline-block mt-1 px-2 py-0.5 rounded-md text-xs font-medium', ROLE_COLORS[role] ?? 'bg-gray-100 text-gray-600')}>
                {ROLE_LABELS[role] ?? role}
              </span>
            </div>
            <span className={cn('ml-auto shrink-0 px-2 py-0.5 rounded-md text-xs font-medium',
              user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500')}>
              {user.is_active ? 'Active' : 'Inactive'}
            </span>
          </div>

          {/* Read-only info */}
          <div className="grid grid-cols-2 gap-3 rounded-xl border border-border bg-muted/20 px-4 py-3">
            <InfoRow icon={Mail}     label="Email"      value={user.email} />
            <InfoRow icon={Calendar} label="Joined"  value={new Date(user.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })} />
          </div>

          {/* Editable fields */}
          {!isSuper && (
            <div className="space-y-4">
              <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Edit User</p>

              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">Full Name</label>
                <input value={name} onChange={e => setName(e.target.value)}
                  className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition" />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-medium text-muted-foreground mb-1">Role</label>
                  <select value={editRole} onChange={e => setEditRole(e.target.value as AdminRole)}
                    className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition">
                    {EDITABLE_ROLES.map(r => <option key={r} value={r}>{ROLE_LABELS[r]}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-medium text-muted-foreground mb-1">Status</label>
                  <button type="button" onClick={() => setIsActive(v => !v)}
                    className={cn(
                      'w-full flex items-center justify-between px-3 py-2 rounded-lg border text-sm font-medium transition-colors',
                      isActive ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-border bg-background text-muted-foreground',
                    )}>
                    {isActive ? 'Active' : 'Inactive'}
                    <span className={cn('h-2 w-2 rounded-full', isActive ? 'bg-emerald-500' : 'bg-gray-300')} />
                  </button>
                </div>
              </div>

              <div>
                <label className="block text-xs font-medium text-muted-foreground mb-1">
                  New Password <span className="text-muted-foreground/60 font-normal">(leave blank to keep unchanged)</span>
                </label>
                <PasswordInput value={password} onChange={setPassword} placeholder="Min. 8 characters" />
              </div>

              {saveError && <p className="text-sm text-destructive bg-destructive/10 rounded-lg px-3 py-2">{saveError}</p>}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between gap-3 px-6 py-4 border-t border-border">
          {/* Deactivate — only if not self and not super admin */}
          {!isSuper && !isSelf && user.is_active ? (
            <button
              onClick={() => deactivateMutation.mutate()}
              disabled={deactivateMutation.isPending}
              className="flex items-center gap-2 px-3 py-2 rounded-lg border border-destructive/40 text-destructive text-sm font-medium hover:bg-destructive/10 disabled:opacity-50 transition-colors"
            >
              {deactivateMutation.isPending ? <Loader2 size={13} className="animate-spin" /> : <UserX size={13} />}
              Deactivate
            </button>
          ) : <span />}

          <div className="flex items-center gap-3">
            {saved && <span className="flex items-center gap-1 text-sm text-emerald-600"><CheckCircle size={13} /> Saved</span>}
            <button onClick={onClose}
              className="px-4 py-2 rounded-lg border border-border text-sm font-medium hover:bg-accent transition-colors">
              Close
            </button>
            {!isSuper && (
              <button
                onClick={() => { setSaveError(''); updateMutation.mutate() }}
                disabled={updateMutation.isPending}
                className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors"
              >
                {updateMutation.isPending && <Loader2 size={14} className="animate-spin" />}
                Save
              </button>
            )}
          </div>
        </div>

      </div>
    </div>
  )
}

const ALLOWED_ROLES: AdminRole[] = ['super_admin', 'admin']

// ── Page ───────────────────────────────────────────────────────────────────

export default function UsersPage() {
  const token       = useAdminAuthStore(s => s.token)!
  const currentUser = useAdminAuthStore(s => s.user)

  if (currentUser && !ALLOWED_ROLES.includes(currentUser.role)) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[60vh] gap-4 text-center">
        <div className="h-16 w-16 rounded-full bg-destructive/10 flex items-center justify-center">
          <ShieldOff size={32} className="text-destructive" />
        </div>
        <div>
          <h2 className="text-xl font-bold text-foreground">Access Denied</h2>
          <p className="text-sm text-muted-foreground mt-1">
            This page can only be accessed by <strong>Admin</strong> and <strong>Super Admin</strong>.
          </p>
        </div>
      </div>
    )
  }
  const [search, setSearch] = useState('')
  const [role,   setRole]   = useState('')
  const [page,   setPage]   = useState(1)
  const [selected, setSelected] = useState<User | null>(null)
  const [showCreate, setShowCreate] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', search, role, page],
    queryFn: () =>
      api.get<any>(`/admin/users?search=${search}&role=${role}&page=${page}`, { token }),
    enabled: !!token,
  })

  const users: User[] = data?.data ?? []
  const meta = data?.meta ?? {}

  return (
    <div className="space-y-6">
      <PageHeader
        icon={Users}
        title="Users"
        description="Manage admin and staff accounts"
        action={
          <button
            onClick={() => setShowCreate(true)}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-colors"
          >
            <Plus size={16} /> Add User
          </button>
        }
      />

      {/* Filter bar */}
      <div className="flex gap-3 flex-wrap">
        <div className="relative flex-1 min-w-48">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search name / email..."
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
      </div>

      <TableCard
        columns={['Name', 'Email', 'Role', 'Joined', 'Status']}
        isLoading={isLoading}
        isEmpty={users.length === 0}
        emptyMessage="No users."
      >
        {users.map(user => {
          const roleVal = resolveRole(user)
          return (
            <tr
              key={user.id}
              onClick={() => setSelected(user)}
              className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors cursor-pointer"
            >
              <td className="px-4 py-3">
                <div className="flex items-center gap-2.5">
                  <Avatar name={user.name} role={roleVal} size="sm" />
                  <span className="font-medium text-foreground">{user.name}</span>
                </div>
              </td>
              <td className="px-4 py-3 text-muted-foreground">{user.email}</td>
              <td className="px-4 py-3">
                <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium', ROLE_COLORS[roleVal] ?? 'bg-gray-100 text-gray-600')}>
                  {ROLE_LABELS[roleVal] ?? roleVal}
                </span>
              </td>
              <td className="px-4 py-3 text-muted-foreground text-xs">
                {new Date(user.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
              </td>
              <td className="px-4 py-3">
                <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium',
                  user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500')}>
                  {user.is_active ? 'Active' : 'Inactive'}
                </span>
              </td>
            </tr>
          )
        })}
      </TableCard>

      <Pagination
        page={page}
        lastPage={meta.lastPage}
        total={meta.total}
        label="users"
        onChange={setPage}
      />

      {/* Modals */}
      {showCreate && (
        <CreateUserModal token={token} onClose={() => setShowCreate(false)} />
      )}
      {selected && (
        <UserDetailModal
          user={selected}
          token={token}
          currentUserId={Number(currentUser?.id ?? 0)}
          onClose={() => setSelected(null)}
        />
      )}
    </div>
  )
}
