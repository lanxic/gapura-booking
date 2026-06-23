'use client'

import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { Activity, Offer, PaginatedResponse, ApiResponse } from '@/types'
import { MapPin, Clock, Users, ChevronRight, Tag } from 'lucide-react'
import Link from 'next/link'

// ─── Hero ─────────────────────────────────────────────────────────────────────
function Hero({ featuredImage }: { featuredImage?: string | null }) {
  return (
    <section className="relative overflow-hidden bg-gray-900 min-h-[360px] flex items-center">
      {featuredImage ? (
        <img
          src={featuredImage}
          alt=""
          className="absolute inset-0 h-full w-full object-cover opacity-40"
        />
      ) : (
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-900 via-teal-800 to-slate-900" />
      )}
      <div className="relative mx-auto max-w-5xl w-full px-4 py-20 text-center text-white">
        <p className="text-xs font-bold uppercase tracking-[0.25em] text-emerald-400 mb-3">
          Amartha eTicket
        </p>
        <h1 className="text-3xl font-bold tracking-tight sm:text-5xl leading-tight">
          Temukan Aktivitas<br className="hidden sm:block" /> Seru di Sekitarmu
        </h1>
        <p className="mt-4 text-base text-white/70 sm:text-lg max-w-2xl mx-auto">
          Yoga, cooking class, hiking, pottery & more — pesan sekarang, bayar mudah.
        </p>
        <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          <Link
            href="/activities"
            className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white px-7 py-3 rounded-xl font-semibold text-sm transition-colors"
          >
            Jelajahi Aktivitas <ChevronRight className="w-4 h-4" />
          </Link>
          <Link
            href="/offers"
            className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 px-7 py-3 rounded-xl font-semibold text-sm transition-colors"
          >
            <Tag className="w-4 h-4" /> Lihat Promo
          </Link>
        </div>
      </div>
    </section>
  )
}

// ─── Category pills ───────────────────────────────────────────────────────────
const CATEGORIES = [
  { label: 'Semua', value: '' },
  { label: 'Wellness', value: 'wellness' },
  { label: 'Kuliner', value: 'culinary' },
  { label: 'Alam', value: 'outdoor' },
  { label: 'Seni', value: 'art' },
  { label: 'Olahraga', value: 'sport' },
  { label: 'Air', value: 'water' },
]

// ─── Activity Card ────────────────────────────────────────────────────────────
function ActivityCard({ activity }: { activity: Activity }) {
  const image = activity.media?.find((m) => m.is_primary)?.url ?? activity.media?.[0]?.url
  const levelColor: Record<string, string> = {
    beginner:     'bg-green-50 text-green-700',
    intermediate: 'bg-yellow-50 text-yellow-700',
    advanced:     'bg-red-50 text-red-700',
    all:          'bg-blue-50 text-blue-700',
  }
  const levelLabel: Record<string, string> = {
    beginner: 'Pemula', intermediate: 'Menengah', advanced: 'Mahir', all: 'Semua Level',
  }

  return (
    <Link
      href={`/activities/${activity.slug}`}
      className="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow flex flex-col"
    >
      <div className="relative h-44 bg-gray-100 overflow-hidden flex-shrink-0">
        {image ? (
          <img
            src={image}
            alt={activity.name}
            className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="h-full bg-gradient-to-br from-emerald-100 to-teal-200" />
        )}
        <span className="absolute top-3 left-3 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold text-gray-700 backdrop-blur">
          {activity.category}
        </span>
      </div>

      <div className="flex flex-1 flex-col p-4">
        <h3 className="font-bold text-gray-900 leading-snug line-clamp-2">{activity.name}</h3>

        <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500">
          <span className="flex items-center gap-1">
            <Clock className="w-3 h-3" /> {activity.duration_minutes} mnt
          </span>
          <span className="flex items-center gap-1">
            <Users className="w-3 h-3" /> {activity.min_pax}–{activity.max_pax} pax
          </span>
        </div>

        <div className="mt-auto pt-3 flex items-end justify-between">
          <div>
            <p className="text-[10px] text-gray-400 uppercase tracking-wide">Mulai dari</p>
            <p className="text-base font-bold text-gray-900">{formatRupiah(activity.base_price)}</p>
          </div>
          <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${levelColor[activity.level] ?? 'bg-gray-100 text-gray-600'}`}>
            {levelLabel[activity.level] ?? activity.level}
          </span>
        </div>
      </div>
    </Link>
  )
}

function ActivityCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white animate-pulse">
      <div className="h-44 bg-gray-200" />
      <div className="p-4 space-y-2">
        <div className="h-4 bg-gray-200 rounded w-3/4" />
        <div className="h-3 bg-gray-200 rounded w-1/2" />
        <div className="h-4 bg-gray-200 rounded w-1/3 mt-3" />
      </div>
    </div>
  )
}

// ─── Offer Banner ─────────────────────────────────────────────────────────────
function OfferBanners({ offers }: { offers: Offer[] }) {
  if (offers.length === 0) return null
  return (
    <section className="mx-auto max-w-5xl px-4 pb-2">
      <h2 className="text-lg font-bold text-gray-900 mb-3">Promo Spesial</h2>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {offers.slice(0, 3).map((offer) => (
          <Link
            key={offer.id}
            href={`/offers/${offer.slug}`}
            className="group relative overflow-hidden rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-4 hover:shadow-md transition-shadow"
          >
            {offer.badge && (
              <span className="absolute top-3 right-3 rounded-full bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5">
                {offer.badge}
              </span>
            )}
            <p className="text-xs font-semibold uppercase tracking-wide text-emerald-600 mb-1">
              {offer.discount_type === 'percent'
                ? `Hemat ${offer.discount_value}%`
                : `Potongan ${formatRupiah(offer.discount_value)}`}
            </p>
            <p className="font-bold text-gray-900 leading-snug line-clamp-2">{offer.title}</p>
            <p className="mt-2 text-xs text-gray-500">
              s/d {new Date(offer.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
            </p>
          </Link>
        ))}
      </div>
    </section>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────
export default function HomePage() {
  const { data: activitiesData, isLoading } = useQuery({
    queryKey: ['activities-featured'],
    queryFn: () => api.get<PaginatedResponse<Activity>>('/activities?per_page=6'),
    staleTime: 2 * 60 * 1000,
  })

  const { data: offersData } = useQuery({
    queryKey: ['offers-active'],
    queryFn: () => api.get<PaginatedResponse<Offer>>('/offers?per_page=3'),
    staleTime: 5 * 60 * 1000,
  })

  const activities = activitiesData?.data ?? []
  const offers     = offersData?.data ?? []
  const featuredImage = activities[0]?.media?.find((m) => m.is_primary)?.url

  return (
    <>
      <Hero featuredImage={featuredImage} />

      <main className="space-y-10 py-10">
        <OfferBanners offers={offers} />

        {/* Activity grid */}
        <section className="mx-auto max-w-5xl px-4">
          <div className="flex items-center justify-between mb-5">
            <div>
              <h2 className="text-xl font-bold text-gray-900">Aktivitas Populer</h2>
              <p className="text-sm text-gray-500 mt-0.5">Pilih aktivitas dan pesan langsung</p>
            </div>
            <Link
              href="/activities"
              className="text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
            >
              Lihat Semua <ChevronRight className="w-4 h-4" />
            </Link>
          </div>

          {isLoading ? (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {Array.from({ length: 6 }).map((_, i) => <ActivityCardSkeleton key={i} />)}
            </div>
          ) : activities.length === 0 ? (
            <div className="text-center py-20 text-gray-400">
              <p className="font-medium">Belum ada aktivitas tersedia.</p>
            </div>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {activities.map((a) => <ActivityCard key={a.id} activity={a} />)}
            </div>
          )}
        </section>

        {/* Why us */}
        <section className="bg-gray-50 py-12">
          <div className="mx-auto max-w-5xl px-4 text-center">
            <h2 className="text-xl font-bold text-gray-900 mb-8">Kenapa Booking di Sini?</h2>
            <div className="grid gap-6 sm:grid-cols-3">
              {[
                { icon: '⚡', title: 'Konfirmasi Instan', desc: 'Booking langsung dikonfirmasi. Tidak perlu menunggu balasan.' },
                { icon: '💳', title: 'Bayar Bertahap', desc: 'Opsi DP 30% atau 50% tersedia untuk aktivitas tertentu.' },
                { icon: '🛡️', title: 'Aman & Terpercaya', desc: 'Pembayaran diproses via Midtrans, data terlindungi.' },
              ].map((item) => (
                <div key={item.title} className="rounded-2xl bg-white border border-gray-200 p-6">
                  <div className="text-3xl mb-3">{item.icon}</div>
                  <h3 className="font-bold text-gray-900">{item.title}</h3>
                  <p className="mt-1 text-sm text-gray-500">{item.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </section>
      </main>
    </>
  )
}
