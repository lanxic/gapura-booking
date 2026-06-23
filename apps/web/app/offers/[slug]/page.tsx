'use client'

import { use } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { Offer } from '@/types'
import Link from 'next/link'
import { Tag, Calendar, ArrowLeft, ChevronRight, Ticket, Loader2, AlertCircle } from 'lucide-react'

function OfferSkeleton() {
  return (
    <div className="max-w-3xl mx-auto px-4 py-10 space-y-6 animate-pulse">
      <div className="h-56 rounded-2xl bg-gray-200" />
      <div className="space-y-3">
        <div className="h-8 bg-gray-200 rounded w-2/3" />
        <div className="h-4 bg-gray-100 rounded w-1/3" />
        <div className="h-16 bg-gray-100 rounded" />
      </div>
    </div>
  )
}

export default function OfferDetailPage({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = use(params)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['offer', slug],
    queryFn: () => api.get<{ data: Offer & { description: string | null } }>(`/offers/${slug}`),
  })

  const offer = data?.data

  if (isLoading) return <OfferSkeleton />

  if (isError || !offer) {
    return (
      <div className="max-w-xl mx-auto px-4 py-20 text-center">
        <AlertCircle className="w-12 h-12 text-red-300 mx-auto mb-4" />
        <h1 className="text-xl font-bold text-gray-800 mb-2">Penawaran Tidak Ditemukan</h1>
        <p className="text-gray-500 mb-6">Penawaran ini mungkin sudah berakhir atau tidak tersedia.</p>
        <Link href="/offers" className="inline-flex items-center gap-2 text-sm text-emerald-600 hover:underline">
          <ArrowLeft size={15} /> Kembali ke Daftar Penawaran
        </Link>
      </div>
    )
  }

  const isPercent   = offer.discount_type === 'percent'
  const badgeLabel  = isPercent ? `Hemat ${offer.discount_value}%` : `Potongan ${formatRupiah(offer.discount_value)}`
  const startFmt    = new Date(offer.start_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
  const endFmt      = new Date(offer.end_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })

  return (
    <main className="max-w-3xl mx-auto px-4 py-10 space-y-8">
      {/* Back */}
      <Link href="/offers" className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
        <ArrowLeft size={14} /> Semua Penawaran
      </Link>

      {/* Hero image */}
      {offer.image ? (
        <div className="relative rounded-2xl overflow-hidden h-52 sm:h-72 bg-gray-100">
          <img src={offer.image} alt={offer.title} className="w-full h-full object-cover" />
          {offer.badge && (
            <span className="absolute top-4 left-4 rounded-full bg-emerald-500 text-white text-sm font-bold px-4 py-1.5 shadow-md">
              {offer.badge}
            </span>
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
          <div className="absolute bottom-4 left-4 right-4">
            <h1 className="text-2xl font-bold text-white drop-shadow-sm">{offer.title}</h1>
          </div>
        </div>
      ) : (
        <div className="rounded-2xl bg-gradient-to-br from-emerald-100 to-teal-200 h-40 sm:h-52 flex flex-col items-center justify-center gap-3">
          <Tag size={40} className="text-emerald-500" />
          <h1 className="text-2xl font-bold text-gray-800 px-4 text-center">{offer.title}</h1>
        </div>
      )}

      {/* Discount badge */}
      <div className="flex flex-wrap items-center gap-3">
        <span className="inline-flex items-center gap-2 rounded-full bg-amber-100 text-amber-700 text-sm font-bold px-4 py-2">
          <Ticket size={15} /> {badgeLabel}
        </span>
        <span className="flex items-center gap-1.5 text-sm text-gray-500">
          <Calendar size={14} />
          Berlaku {startFmt} – {endFmt}
        </span>
      </div>

      {/* Description */}
      {offer.description && (
        <div className="prose prose-sm prose-emerald max-w-none text-gray-600 leading-relaxed whitespace-pre-wrap">
          {offer.description}
        </div>
      )}

      {/* Aktivitas yang berlaku */}
      {offer.activities && offer.activities.length > 0 && (
        <section className="space-y-3">
          <h2 className="font-bold text-gray-800">Aktivitas yang Berlaku</h2>
          <div className="divide-y divide-gray-100 border border-gray-200 rounded-xl overflow-hidden">
            {offer.activities.map(activity => (
              <Link
                key={activity.slug}
                href={`/activities/${activity.slug}`}
                className="flex items-center justify-between px-4 py-3.5 hover:bg-emerald-50 transition-colors group"
              >
                <span className="font-medium text-gray-800 group-hover:text-emerald-700 transition-colors">
                  {activity.name}
                </span>
                <ChevronRight size={16} className="text-gray-400 group-hover:text-emerald-500" />
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* CTA */}
      <div className="rounded-2xl bg-emerald-50 border border-emerald-200 p-6 text-center space-y-3">
        <p className="text-sm text-emerald-700 font-medium">
          Gunakan penawaran ini saat checkout di halaman aktivitas
        </p>
        <Link
          href="/activities"
          className="inline-flex items-center gap-2 bg-emerald-600 text-white rounded-xl px-6 py-3 text-sm font-semibold hover:bg-emerald-700 transition-colors"
        >
          Jelajahi Aktivitas <ChevronRight size={16} />
        </Link>
      </div>
    </main>
  )
}
