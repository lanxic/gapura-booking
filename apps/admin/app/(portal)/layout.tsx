'use client'

import { useAdminAuthStore, type AdminRole, type Permission } from '@/store/auth'
import { clearSession } from '@/lib/session'
import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import {
  LayoutDashboard, Percent, Users, Settings, History,
  LogOut, Menu, Loader2, ChevronsUpDown, UserCircle,
  ChevronLeft, ChevronRight, ChevronDown, Ticket,
  MapPin, BookOpen, CreditCard, Tag, Globe, Scale,
  Users2, FileText,
} from 'lucide-react'
import { useState, useEffect, useRef } from 'react'
import { cn } from '@/lib/utils'

type NavChild = { href: string; label: string; icon: React.ElementType }
type NavItem =
  | { href: string;     label: string; icon: React.ElementType; permissions?: Permission[]; children?: undefined }
  | { href?: undefined; label: string; icon: React.ElementType; permissions?: Permission[]; children: NavChild[] }

type NavGroup = {
  label: string
  items: NavItem[]
}

const navGroups: NavGroup[] = [
  {
    label: 'Overview',
    items: [
      { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    ],
  },
  {
    label: 'Activity Booking',
    items: [
      { href: '/activities', label: 'Aktivitas',    icon: MapPin,    permissions: ['activities.manage'] },
      { href: '/bookings',   label: 'Bookings',     icon: BookOpen,  permissions: ['bookings.view'] },
      { href: '/invoices',   label: 'Invoice',      icon: FileText,  permissions: ['bookings.view'] },
      { href: '/customers',  label: 'Pelanggan',    icon: Users2,    permissions: ['bookings.view'] },
      { href: '/offers',     label: 'Offer & Promo', icon: Tag,      permissions: ['offers.manage'] },
    ],
  },
  {
    label: 'Pengaturan',
    items: [
      { href: '/users', label: 'Users', icon: Users, permissions: ['users.manage'] },
      {
        label: 'Settings', icon: Settings, permissions: ['settings.manage'],
        children: [
          { href: '/settings/general',          label: 'General',          icon: Globe },
          { href: '/settings/payment-gateways', label: 'Metode Pembayaran', icon: CreditCard },
          { href: '/settings/payment-plans',    label: 'Payment Plans',    icon: Percent },
          { href: '/settings/legal',            label: 'Legal',            icon: Scale },
        ],
      },
      { href: '/activity-logs', label: 'Activity Logs', icon: History, permissions: ['activity_logs.view'] },
    ],
  },
]

const ROLE_LABELS: Record<AdminRole, string> = {
  super_admin: 'Super Admin',
  admin:       'Admin',
  scanner:     'Scanner',
}

function NavLink({ href, label, icon: Icon, pathname, onClick, isCollapsed, indent }: {
  href: string
  label: string
  icon: React.ElementType
  pathname: string
  onClick?: () => void
  isCollapsed?: boolean
  indent?: boolean
}) {
  const isActive = pathname === href || (href !== '/dashboard' && pathname.startsWith(href + '/'))
    || pathname === href

  return (
    <Link
      href={href}
      onClick={onClick}
      title={isCollapsed ? label : undefined}
      className={cn(
        'flex items-center rounded-lg text-sm font-medium transition-all duration-200',
        isCollapsed ? 'justify-center w-10 h-10 mx-auto' : 'gap-3 py-2 mx-3',
        !isCollapsed && (indent ? 'pl-9 pr-3' : 'px-3'),
        isActive
          ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400'
          : 'text-muted-foreground hover:text-foreground hover:bg-accent/50',
      )}
    >
      <Icon size={indent ? 15 : 18} className="shrink-0" />
      {!isCollapsed && <span className="truncate">{label}</span>}
    </Link>
  )
}

function CollapsibleNavItem({ item, pathname, onClick, isCollapsed }: {
  item: NavItem & { children: NavChild[] }
  pathname: string
  onClick?: () => void
  isCollapsed?: boolean
}) {
  const anyChildActive = item.children.some(c => pathname === c.href || pathname.startsWith(c.href + '/'))
  const [open, setOpen] = useState(anyChildActive)
  const Icon = item.icon

  useEffect(() => { if (anyChildActive) setOpen(true) }, [anyChildActive])

  if (isCollapsed) {
    return (
      <div title={item.label} className="flex justify-center">
        <div className={cn(
          'flex items-center justify-center w-10 h-10 rounded-lg text-muted-foreground',
          anyChildActive && 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
        )}>
          <Icon size={18} />
        </div>
      </div>
    )
  }

  return (
    <div>
      <button
        onClick={() => setOpen(v => !v)}
        className={cn(
          'w-full flex items-center gap-3 px-3 py-2 mx-3 rounded-lg text-sm font-medium transition-all duration-200',
          'max-w-[calc(100%-1.5rem)]',
          anyChildActive
            ? 'text-foreground hover:bg-accent/50'
            : 'text-muted-foreground hover:text-foreground hover:bg-accent/50',
        )}
      >
        <Icon size={18} className="shrink-0" />
        <span className="flex-1 truncate text-left">{item.label}</span>
        <ChevronDown size={14} className={cn('shrink-0 transition-transform duration-200', open && 'rotate-180')} />
      </button>
      {open && (
        <div className="mt-0.5 space-y-0.5">
          {item.children.map(child => (
            <NavLink key={child.href} href={child.href} label={child.label} icon={child.icon}
              pathname={pathname} onClick={onClick} indent />
          ))}
        </div>
      )}
    </div>
  )
}

function UserMenu({ user, onLogout, isCollapsed }: {
  user: { name: string; email: string; role: string; avatarUrl?: string | null }
  onLogout: () => void
  isCollapsed?: boolean
}) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [])

  const initials = user.name?.slice(0, 2)?.toUpperCase() || 'AU'

  return (
    <div ref={ref} className="relative">
      <button
        onClick={() => setOpen(v => !v)}
        className={cn(
          'w-full flex items-center rounded-md hover:bg-accent transition-colors',
          isCollapsed ? 'justify-center py-2' : 'gap-3 px-3 py-2.5',
        )}
      >
        <div className="h-8 w-8 shrink-0 rounded-full bg-[#6b3fa0] text-white text-xs font-bold flex items-center justify-center overflow-hidden">
          {user.avatarUrl
            ? <img src={user.avatarUrl} alt={user.name} className="h-full w-full object-cover" />
            : initials
          }
        </div>
        {!isCollapsed && (
          <>
            <div className="flex-1 min-w-0 text-left">
              <p className="text-sm font-medium text-foreground truncate">{user.name}</p>
              <p className="text-xs text-muted-foreground truncate">{user.email}</p>
            </div>
            <ChevronsUpDown size={14} className="text-muted-foreground shrink-0" />
          </>
        )}
      </button>

      {open && (
        <div className={cn(
          'absolute z-50 w-52 rounded-lg border border-border bg-popover shadow-md py-1',
          isCollapsed ? 'left-12 bottom-0' : 'bottom-full mb-1 left-0',
        )}>
          <Link
            href="/profile"
            onClick={() => setOpen(false)}
            className="flex items-center gap-2 px-3 py-2 text-sm hover:bg-accent transition-colors"
          >
            <UserCircle size={15} /> Profile
          </Link>
          <div className="h-px bg-border my-1" />
          <button
            onClick={() => { setOpen(false); onLogout() }}
            className="w-full flex items-center gap-2 px-3 py-2 text-sm text-destructive hover:bg-destructive/10 transition-colors"
          >
            <LogOut size={15} /> Log Out
          </button>
        </div>
      )}
    </div>
  )
}

function SidebarContent({ user, canView, pathname, onNavClick, onLogout, isCollapsed, onToggleCollapse }: {
  user: { name: string; email: string; role: string; avatarUrl?: string | null }
  canView: (permissions?: Permission[]) => boolean
  pathname: string
  onNavClick?: () => void
  onLogout: () => void
  isCollapsed?: boolean
  onToggleCollapse?: () => void
}) {
  const roleLabel = ROLE_LABELS[user.role as AdminRole] ?? 'Admin'

  return (
    <div className={cn(
      'flex flex-col h-full bg-sidebar border-r border-sidebar-border transition-all duration-300 relative',
      isCollapsed ? 'w-20' : 'w-64',
    )}>
      {onToggleCollapse && (
        <button
          onClick={onToggleCollapse}
          className={cn(
            'absolute -right-3 top-8 h-6 w-6 border border-border shadow-sm z-10 bg-background text-muted-foreground hover:text-foreground flex items-center justify-center transition-colors',
            isCollapsed ? 'rounded-full' : 'rounded-md',
          )}
        >
          {isCollapsed ? <ChevronRight size={14} /> : <ChevronLeft size={14} />}
        </button>
      )}

      {/* Logo */}
      <div className={cn(
        'pt-6 pb-5 border-b border-sidebar-border flex flex-col',
        isCollapsed ? 'px-2 items-center' : 'px-5 items-start',
      )}>
        <div className={cn('flex items-center gap-3 mb-4 w-full', isCollapsed ? 'justify-center' : '')}>
          <div className="w-9 h-9 shrink-0 bg-emerald-500 rounded-lg flex items-center justify-center">
            <Ticket size={18} className="text-white" />
          </div>
          {!isCollapsed && (
            <span className="font-bold text-xl text-foreground tracking-tight truncate">Amartha</span>
          )}
        </div>
        {!isCollapsed && (
          <span className="inline-flex w-full items-center justify-center text-[10px] font-semibold bg-blue-50 text-blue-600 border border-blue-200/50 rounded-md uppercase tracking-widest px-2 py-0.5 dark:bg-blue-500/10 dark:text-blue-400">
            {roleLabel}
          </span>
        )}
      </div>

      {/* Nav Groups */}
      <nav className="flex-1 py-5 space-y-6 overflow-y-auto overflow-x-hidden">
        {navGroups.map((group) => {
          const items = group.items.filter(item => canView(item.permissions))
          if (items.length === 0) return null
          return (
            <div key={group.label}>
              {isCollapsed ? (
                <div className="w-8 h-px bg-sidebar-border mx-auto mb-2" />
              ) : (
                <p className="px-5 mb-1.5 text-[11px] font-semibold text-muted-foreground uppercase tracking-wider truncate">
                  {group.label}
                </p>
              )}
              <div className="space-y-1">
                {items.map(item =>
                  item.children ? (
                    <CollapsibleNavItem
                      key={item.label}
                      item={item as NavItem & { children: NavChild[] }}
                      pathname={pathname}
                      onClick={onNavClick}
                      isCollapsed={isCollapsed}
                    />
                  ) : (
                    <NavLink
                      key={item.href}
                      href={item.href!}
                      label={item.label}
                      icon={item.icon}
                      pathname={pathname}
                      onClick={onNavClick}
                      isCollapsed={isCollapsed}
                    />
                  )
                )}
              </div>
            </div>
          )
        })}
      </nav>

      {/* User */}
      <div className="p-3 border-t border-sidebar-border">
        <UserMenu user={user} onLogout={onLogout} isCollapsed={isCollapsed} />
      </div>
    </div>
  )
}

export default function PortalLayout({ children }: { children: React.ReactNode }) {
  const user = useAdminAuthStore(s => s.user)
  const token = useAdminAuthStore(s => s.token)
  const can = useAdminAuthStore(s => s.can)
  const pathname = usePathname()
  const router = useRouter()
  const [mounted, setMounted] = useState(false)
  const [sheetOpen, setSheetOpen] = useState(false)
  const [isCollapsed, setIsCollapsed] = useState(false)

  useEffect(() => { setMounted(true) }, [])

  useEffect(() => {
    if (mounted && !token) router.push('/login')
  }, [mounted, token, router])

  const canView = (permissions?: Permission[]) => {
    if (!permissions || permissions.length === 0) return true
    if (user?.role === 'super_admin') return true
    return permissions.some(p => can(p))
  }

  const handleLogout = () => {
    clearSession()  // clears Zustand store + cookies + redirects to /login
  }

  if (!mounted || !user) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-background">
        <Loader2 className="animate-spin h-8 w-8 text-primary" />
      </div>
    )
  }

  return (
    <div className="h-screen overflow-hidden bg-background flex">
      {/* Desktop Sidebar */}
      <aside className="hidden lg:block shrink-0 h-screen sticky top-0">
        <SidebarContent
          user={user}
          canView={canView}
          pathname={pathname}
          onLogout={handleLogout}
          isCollapsed={isCollapsed}
          onToggleCollapse={() => setIsCollapsed(v => !v)}
        />
      </aside>

      {/* Mobile Overlay */}
      {sheetOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          onClick={() => setSheetOpen(false)}
        />
      )}

      {/* Mobile Sidebar */}
      <aside className={cn(
        'fixed inset-y-0 left-0 z-50 lg:hidden transition-transform duration-300',
        sheetOpen ? 'translate-x-0' : '-translate-x-full',
      )}>
        <SidebarContent
          user={user}
          canView={canView}
          pathname={pathname}
          onNavClick={() => setSheetOpen(false)}
          onLogout={handleLogout}
        />
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <header className="lg:hidden h-14 bg-card border-b border-border flex items-center px-4 sticky top-0 z-30">
          <button
            onClick={() => setSheetOpen(true)}
            className="mr-3 p-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-md transition-colors"
          >
            <Menu size={20} />
          </button>
          <span className="font-bold text-base">Amartha</span>
        </header>

        <main className="flex-1 min-h-0 overflow-y-auto p-6">
          {children}
        </main>
      </div>
    </div>
  )
}
