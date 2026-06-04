'use client'

import { useAdminAuthStore, type AdminRole } from '@/store/auth'
import { api } from '@/lib/api'
import { Camera, UserCircle, KeyRound, Save, Loader2, CheckCircle } from 'lucide-react'
import { useState } from 'react'
import { cn } from '@/lib/utils'

const ROLE_LABELS: Record<AdminRole, string> = {
  super_admin: 'Super Admin',
  admin:       'Admin',
  supervisor:  'Supervisor',
  kasir:       'Kasir',
  scanner:     'Scanner',
}

type Tab = 'info' | 'security'

export default function ProfilePage() {
  const user     = useAdminAuthStore(s => s.user)
  const token    = useAdminAuthStore(s => s.token)
  const setAuth  = useAdminAuthStore(s => s.setAuth)

  const [tab, setTab] = useState<Tab>('info')

  // Tab: Informasi Profil
  const [name, setName]         = useState(user?.name ?? '')
  const [bio, setBio]           = useState('')
  const [avatarUrl, setAvatarUrl] = useState(user?.avatarUrl ?? '')
  const [infoSaving, setInfoSaving]   = useState(false)
  const [infoSuccess, setInfoSuccess] = useState(false)
  const [infoError, setInfoError]     = useState('')

  // Tab: Keamanan
  const [currentPassword, setCurrentPassword] = useState('')
  const [newPassword, setNewPassword]         = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')
  const [secSaving, setSecSaving]   = useState(false)
  const [secSuccess, setSecSuccess] = useState(false)
  const [secError, setSecError]     = useState('')

  if (!user) return null

  const roleLabel = ROLE_LABELS[user.role as AdminRole] ?? user.role
  const initials  = user.name?.slice(0, 2)?.toUpperCase() || 'AU'

  const handleSaveInfo = async (e: React.FormEvent) => {
    e.preventDefault()
    setInfoError('')
    setInfoSuccess(false)
    setInfoSaving(true)
    try {
      await api.put(`/admin/users/${user.id}`, { name }, { token: token! })
      setAuth({ ...user, name }, token!)
      setInfoSuccess(true)
    } catch (err) {
      setInfoError(err instanceof Error ? err.message : 'Gagal menyimpan profil.')
    } finally {
      setInfoSaving(false)
    }
  }

  const handleSaveSecurity = async (e: React.FormEvent) => {
    e.preventDefault()
    setSecError('')
    setSecSuccess(false)

    if (newPassword !== confirmPassword) {
      setSecError('Password baru tidak cocok.')
      return
    }
    if (newPassword.length < 8) {
      setSecError('Password baru minimal 8 karakter.')
      return
    }

    setSecSaving(true)
    try {
      await api.put(`/admin/users/${user.id}`, {
        password:         newPassword,
        current_password: currentPassword,
      }, { token: token! })
      setSecSuccess(true)
      setCurrentPassword('')
      setNewPassword('')
      setConfirmPassword('')
    } catch (err) {
      setSecError(err instanceof Error ? err.message : 'Gagal mengubah password.')
    } finally {
      setSecSaving(false)
    }
  }

  return (
    <div className="max-w-2xl space-y-6">
      {/* Breadcrumb */}
      <nav className="flex items-center gap-1.5 text-sm text-muted-foreground">
        <span>Akun</span>
        <span>›</span>
        <span className="text-foreground font-medium">Profil Saya</span>
      </nav>

      <h1 className="text-2xl font-bold text-foreground -mt-2">Profil Saya</h1>

      {/* Avatar + Identity Card */}
      <div className="rounded-xl border border-border bg-card p-5 flex items-center gap-5">
        {/* Avatar with camera overlay */}
        <div className="relative group shrink-0">
          <div className="h-20 w-20 rounded-full bg-[#6b3fa0] text-white text-2xl font-bold flex items-center justify-center select-none overflow-hidden">
            {avatarUrl ? (
              <img src={avatarUrl} alt={user.name} className="h-full w-full object-cover" />
            ) : (
              initials
            )}
          </div>
          <button
            type="button"
            title="Ganti avatar"
            className="absolute inset-0 rounded-full bg-black/50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
          >
            <Camera size={18} className="text-white" />
            <span className="text-white text-[10px] font-medium mt-0.5">Ganti avatar</span>
          </button>
        </div>

        <div className="min-w-0">
          <p className="text-base font-semibold text-foreground leading-tight">{user.name}</p>
          <p className="text-sm text-muted-foreground mt-0.5">{user.email}</p>
          <span className="inline-flex mt-2 items-center px-2 py-0.5 rounded-md text-xs font-medium bg-muted text-muted-foreground border border-border">
            {roleLabel}
          </span>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 p-1 bg-muted rounded-lg w-fit">
        <button
          onClick={() => setTab('info')}
          className={cn(
            'flex items-center gap-1.5 px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
            tab === 'info'
              ? 'bg-background text-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground',
          )}
        >
          <UserCircle size={15} />
          Informasi Profil
        </button>
        <button
          onClick={() => setTab('security')}
          className={cn(
            'flex items-center gap-1.5 px-4 py-1.5 rounded-md text-sm font-medium transition-colors',
            tab === 'security'
              ? 'bg-background text-foreground shadow-sm'
              : 'text-muted-foreground hover:text-foreground',
          )}
        >
          <KeyRound size={15} />
          Keamanan
        </button>
      </div>

      {/* Tab: Informasi Profil */}
      {tab === 'info' && (
        <div className="rounded-xl border border-border bg-card p-6">
          <h2 className="text-base font-semibold text-foreground">Informasi Profil</h2>
          <p className="text-sm text-muted-foreground mt-0.5 mb-6">
            Perbarui nama tampilan, bio, dan foto profil Anda.
          </p>

          <form onSubmit={handleSaveInfo} className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">
                Nama Lengkap
              </label>
              <input
                type="text"
                value={name}
                onChange={e => setName(e.target.value)}
                required
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring transition"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">Email</label>
              <input
                type="email"
                value={user.email}
                readOnly
                className="w-full rounded-lg border border-input bg-muted px-3 py-2 text-sm text-muted-foreground cursor-not-allowed"
              />
              <p className="mt-1 text-xs text-muted-foreground">Email tidak dapat diubah.</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">Bio</label>
              <textarea
                value={bio}
                onChange={e => setBio(e.target.value.slice(0, 500))}
                rows={4}
                placeholder="Ceritakan sedikit tentang diri Anda..."
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition resize-none"
              />
              <p className="text-right text-xs text-muted-foreground mt-1">{bio.length}/500</p>
            </div>

            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">URL Avatar</label>
              <input
                type="text"
                value={avatarUrl}
                onChange={e => setAvatarUrl(e.target.value)}
                placeholder="https://... atau upload dengan klik foto di atas"
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition"
              />
            </div>

            {infoError && (
              <p className="text-sm text-destructive bg-destructive/10 rounded-lg px-3 py-2">
                {infoError}
              </p>
            )}
            {infoSuccess && (
              <div className="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">
                <CheckCircle size={15} /> Profil berhasil disimpan.
              </div>
            )}

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={infoSaving}
                className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {infoSaving ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
                {infoSaving ? 'Menyimpan...' : 'Simpan Profil'}
              </button>
            </div>
          </form>
        </div>
      )}

      {/* Tab: Keamanan */}
      {tab === 'security' && (
        <div className="rounded-xl border border-border bg-card p-6">
          <h2 className="text-base font-semibold text-foreground">Keamanan</h2>
          <p className="text-sm text-muted-foreground mt-0.5 mb-6">
            Ubah password untuk menjaga keamanan akun Anda.
          </p>

          <form onSubmit={handleSaveSecurity} className="space-y-5">
            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">
                Password Saat Ini
              </label>
              <input
                type="password"
                value={currentPassword}
                onChange={e => setCurrentPassword(e.target.value)}
                required
                placeholder="••••••••"
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">
                Password Baru
              </label>
              <input
                type="password"
                value={newPassword}
                onChange={e => setNewPassword(e.target.value)}
                required
                minLength={8}
                placeholder="Min. 8 karakter"
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-foreground mb-1.5">
                Konfirmasi Password Baru
              </label>
              <input
                type="password"
                value={confirmPassword}
                onChange={e => setConfirmPassword(e.target.value)}
                required
                placeholder="Ulangi password baru"
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring transition"
              />
            </div>

            {secError && (
              <p className="text-sm text-destructive bg-destructive/10 rounded-lg px-3 py-2">
                {secError}
              </p>
            )}
            {secSuccess && (
              <div className="flex items-center gap-2 text-sm text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">
                <CheckCircle size={15} /> Password berhasil diubah.
              </div>
            )}

            <div className="flex justify-end">
              <button
                type="submit"
                disabled={secSaving}
                className="flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {secSaving ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
                {secSaving ? 'Menyimpan...' : 'Simpan Password'}
              </button>
            </div>
          </form>
        </div>
      )}
    </div>
  )
}
