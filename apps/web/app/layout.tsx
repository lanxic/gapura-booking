import type { Metadata } from 'next'
import { Geist, Geist_Mono } from 'next/font/google'
import Providers from './providers'
import './globals.css'

const geistSans = Geist({
  variable: '--font-geist-sans',
  subsets: ['latin'],
})

const geistMono = Geist_Mono({
  variable: '--font-geist-mono',
  subsets: ['latin'],
})

export const metadata: Metadata = {
  title: 'Amartha eTicket',
  description: 'Pesan tiket wisata dengan mudah dan cepat',
}

// ─── Server-side settings fetch ───────────────────────────────────────────────
type GeneralSettings = {
  app_name?: string | null
  logo_url?: string | null
  app_description?: string | null
  contact_email?: string | null
  contact_phone?: string | null
  contact_address?: string | null
  copyright_text?: string | null
  footer_bg_color?: string | null
  facebook_url?: string | null
  instagram_url?: string | null
  twitter_url?: string | null
  youtube_url?: string | null
  tripadvisor_url?: string | null
}

async function fetchGeneralSettings(): Promise<GeneralSettings> {
  try {
    const base = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/v1'
    const res = await fetch(`${base}/settings/general`, {
      cache: 'no-store', // always fetch fresh — settings change via admin portal
    })
    if (!res.ok) return {}
    const json = await res.json()
    return (json?.data ?? {}) as GeneralSettings
  } catch {
    return {}
  }
}

// ─── Social Media Icons (inline SVG) ─────────────────────────────────────────
function IconTripadvisor() {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
      <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 110-16 8 8 0 010 16zm-4-8a2 2 0 104 0 2 2 0 00-4 0zm8 0a2 2 0 104 0 2 2 0 00-4 0z" />
    </svg>
  )
}

function IconFacebook() {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
      <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
    </svg>
  )
}

function IconInstagram() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-4 h-4">
      <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
      <circle cx="12" cy="12" r="3.5" />
      <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
    </svg>
  )
}

function IconX() {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
      <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
    </svg>
  )
}

function IconYoutube() {
  return (
    <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
      <path d="M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58 2.78 2.78 0 001.95 1.95C5.12 20 12 20 12 20s6.88 0 8.59-.47a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z" />
    </svg>
  )
}

function IconMail() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-6 h-6">
      <rect x="2" y="4" width="20" height="16" rx="2" />
      <path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7" />
    </svg>
  )
}

function IconPhone() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-6 h-6">
      <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z" />
    </svg>
  )
}

function IconBook() {
  return (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="w-6 h-6">
      <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
    </svg>
  )
}

// ─── Logo ─────────────────────────────────────────────────────────────────────
function SiteLogo({ logoUrl, appName }: { logoUrl?: string | null; appName?: string | null }) {
  const name = appName || 'Amartha eTicket'

  return (
    <a href="/" className="flex items-center gap-2 select-none min-w-0">
      {logoUrl ? (
        // eslint-disable-next-line @next/next/no-img-element
        <img
          src={logoUrl}
          alt={name}
          className="h-9 w-auto max-w-[160px] object-contain"
        />
      ) : (
        /* Default text-based logo */
        <span className="text-lg font-bold text-emerald-800 tracking-tight whitespace-nowrap">
          {name}
        </span>
      )}
    </a>
  )
}

// ─── Header right actions ─────────────────────────────────────────────────────
function HeaderActions() {
  return (
    <div className="flex items-center gap-3">
      {/* Currency */}
      <div className="flex items-center gap-0.5 text-sm font-medium text-gray-700 cursor-pointer select-none hover:text-emerald-700 transition-colors">
        IDR
        <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
          <path d="M6 9l6 6 6-6" />
        </svg>
      </div>

      {/* Search */}
      <a
        href="/products"
        className="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:text-emerald-700 hover:border-emerald-400 transition-colors"
        aria-label="Cari produk"
      >
        <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
      </a>

      {/* Account */}
      <a
        href="/account"
        className="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:text-emerald-700 hover:border-emerald-400 transition-colors"
        aria-label="Akun saya"
      >
        <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
          <circle cx="12" cy="7" r="4" />
        </svg>
      </a>
    </div>
  )
}

// ─── Footer ───────────────────────────────────────────────────────────────────
function Footer({ s }: { s: GeneralSettings }) {
  const socialLinks = [
    s.tripadvisor_url?.trim() && { icon: <IconTripadvisor />, href: s.tripadvisor_url!, label: 'Tripadvisor' },
    s.facebook_url?.trim()    && { icon: <IconFacebook />,   href: s.facebook_url!,    label: 'Facebook' },
    s.instagram_url?.trim()   && { icon: <IconInstagram />,  href: s.instagram_url!,   label: 'Instagram' },
    s.twitter_url?.trim()     && { icon: <IconX />,          href: s.twitter_url!,     label: 'X (Twitter)' },
    s.youtube_url?.trim()     && { icon: <IconYoutube />,    href: s.youtube_url!,     label: 'YouTube' },
  ].filter(Boolean) as { icon: React.ReactNode; href: string; label: string }[]

  const email     = s.contact_email?.trim()   || null
  const phone     = s.contact_phone?.trim()   || null
  const address   = s.contact_address?.trim() || null
  const appName   = s.app_name?.trim()        || 'Amartha eTicket'
  const tagline   = s.app_description?.trim() || null
  const copyright = s.copyright_text?.trim()  || `© ${new Date().getFullYear()} ${appName}. All rights reserved.`
  const bgColor = s.footer_bg_color?.trim() || '#1a1a2e'

  const contactCols = [
    email   && { icon: <IconMail />,  label: 'Hubungi Kami',  value: email,   href: `mailto:${email}` },
    phone   && { icon: <IconPhone />, label: 'Nomor Telepon', value: phone,   href: `tel:${phone.replace(/\s/g, '')}` },
    address && { icon: <IconBook />,  label: 'Alamat',        value: address, href: null },
  ].filter(Boolean) as { icon: React.ReactNode; label: string; value: string; href: string | null }[]

  return (
    <footer style={{ backgroundColor: bgColor }}>

      {/* ── Main body ── */}
      <div className="max-w-5xl mx-auto px-6 pt-14 pb-12">
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-12">

          {/* ── Brand column ── */}
          <div className="lg:col-span-1 space-y-5">
            {/* Logo / wordmark */}
            <div>
              {s.logo_url ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={s.logo_url}
                  alt={appName}
                  className="h-10 w-auto object-contain brightness-0 invert opacity-95 mb-3"
                />
              ) : (
                <p className="text-white font-bold text-lg tracking-tight mb-3">{appName}</p>
              )}
              {tagline && (
                <p className="text-white/45 text-[13px] leading-relaxed">{tagline}</p>
              )}
            </div>

            {/* Social icons */}
            {socialLinks.length > 0 && (
              <div className="flex flex-wrap gap-2">
                {socialLinks.map(({ icon, href, label }) => (
                  <a
                    key={label}
                    href={href}
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label={label}
                    className="w-8 h-8 rounded-full flex items-center justify-center transition-all text-white/50 hover:text-white"
                    style={{ background: 'rgba(255,255,255,0.07)' }}
                    onMouseEnter={e => (e.currentTarget.style.background = 'rgba(255,255,255,0.14)')}
                    onMouseLeave={e => (e.currentTarget.style.background = 'rgba(255,255,255,0.07)')}
                  >
                    {icon}
                  </a>
                ))}
              </div>
            )}
          </div>

          {/* ── Contact columns ── */}
          {contactCols.length > 0 && (
            <div className="lg:col-span-3 grid grid-cols-1 sm:grid-cols-3 gap-8 lg:pt-1">
              {contactCols.map(({ icon, label, value, href }) => (
                <div key={label}>
                  {/* Column header */}
                  <div className="flex items-center gap-2 mb-3">
                    <span className="text-white/30">{icon}</span>
                    <p className="text-[13px] font-semibold text-white tracking-wide">{label}</p>
                  </div>
                  {/* Thin accent line under header */}
                  <div className="w-6 h-px bg-emerald-500/50 mb-3" />
                  {/* Value */}
                  {href ? (
                    <a
                      href={href}
                      className="text-[13px] text-white/50 hover:text-white transition-colors leading-relaxed break-all"
                    >
                      {value}
                    </a>
                  ) : (
                    <p className="text-[13px] text-white/50 leading-relaxed whitespace-pre-line">{value}</p>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* ── Bottom bar ── */}
      <div style={{ borderTop: '1px solid rgba(255,255,255,0.06)', background: 'rgba(0,0,0,0.20)' }}>
        <div className="max-w-5xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p className="text-[12px] text-white/30 order-2 sm:order-1">{copyright}</p>
          <nav className="flex items-center gap-5 order-1 sm:order-2">
            <a href="/" className="text-[12px] text-white/30 hover:text-white/60 transition-colors">Beranda</a>
            <a href="/products" className="text-[12px] text-white/30 hover:text-white/60 transition-colors">Produk</a>
            <a href="/account" className="text-[12px] text-white/30 hover:text-white/60 transition-colors">Akun Saya</a>
          </nav>
        </div>
      </div>
    </footer>
  )
}

// ─── Root Layout ──────────────────────────────────────────────────────────────
export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  const settings = await fetchGeneralSettings()

  return (
    <html lang="id" className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}>
      <body className="min-h-full flex flex-col bg-gray-50 text-gray-900">
        <Providers>
          {/* Header */}
          <header className="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
            <div className="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
              <SiteLogo logoUrl={settings.logo_url} appName={settings.app_name} />
              <HeaderActions />
            </div>
          </header>

          {/* Page content */}
          <main className="flex-1">{children}</main>

          {/* Footer */}
          <Footer s={settings} />
        </Providers>
      </body>
    </html>
  )
}
