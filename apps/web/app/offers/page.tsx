'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { Offer, PaginatedResponse } from '@/types'
import { Tag, Calendar, ChevronRight } from 'lucide-react'
import Link from 'next/link'

function OfferCard({ offer }: { offer: Offer }) {
  const isPercent = offer.discount_type === 'percent'
  const discountLabel = isPercent
    ? `Hemat ${offer.discount_value}%`
    : `Potongan ${formatRupiah(offer.discount_value)}`

  const endDate = new Date(offer.end_date).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'long', year: 'numeric',
  })

  return (
    <Link
      href={`/offers/${offer.slug}`}
      className="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow flex flex-col"
    >
      {offer.image ? (
        <div className="relative h-40 overflow-hidden bg-gray-100">
          <img
            src={offer.image}
            alt={offer.title}
            className="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          {offer.badge && (
            <span className="absolute top-3 left-3 rounded-full bg-emerald-500 text-white text-xs font-bold px-3 py-1">
              {offer.badge}
            </span>
          )}
        </div>
      ) : (
        <div className="relative h-32 bg-gradient-to-br from-emerald-100 to-teal-200 flex items-center justify-center">
          <Tag className="w-10 h-10 text-emerald-400" />
          {offer.badge && (
            <span className="absolute top-3 left-3 rounded-full bg-emerald-500 text-white text-xs font-bold px-3 py-1">
              {offer.badge}
            </span>
          )}
        </div>
      )}

      <div className="flex flex-1 flex-col p-4">
        <span className="text-xs font-bold text-emerald-600 uppercase tracking-wide">
          {discountLabel}
        </span>
        <h3 className="mt-1 font-bold text-gray-900 leading-snug line-clamp-2">
          {offer.title}
        </h3>

        {offer.activities.length > 0 && (
          <p className="mt-2 text-xs text-gray-500 line-clamp-1">
            {offer.activities.map((a) => a.name).join(', ')}
          </p>
        )}

        <div className="mt-auto pt-3 flex items-center justify-between">
          <span className="flex items-center gap-1 text-xs text-gray-400">
            <Calendar className="w-3 h-3" /> s/d {endDate}
          </span>
          <span className="text-xs font-semibold text-emerald-600 flex items-center gap-1">
            Gunakan <ChevronRight className="w-3 h-3" />
          </span>
        </div>
      </div>
    </Link>
  )
}

function OfferCardSkeleton() {
  return (
    <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white animate-pulse">
      <div className="h-40 bg-gray-200" />
      <div className="p-4 space-y-2">
        <div className="h-3 bg-gray-200 rounded w-1/3" />
        <div className="h-4 bg-gray-200 rounded w-3/4" />
        <div className="h-3 bg-gray-200 rounded w-1/2" />
      </div>
    </div>
  )
}

export default function OffersPage() {
  const [page, setPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['offers', page],
    queryFn: () => api.get<PaginatedResponse<Offer>>(`/offers?page=${page}&per_page=12`),
    staleTime: 2 * 60 * 1000,
  })

  const offers   = data?.data ?? []
  const lastPage = data?.meta?.last_page ?? 1

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Promo &amp; Penawaran</h1>
        <p className="text-sm text-gray-500 mt-1">Hemat lebih banyak dengan promo spesial kami</p>
      </div>

      {isError && (
        <div className="text-center py-20 text-gray-400">
          <p className="font-medium">Gagal memuat promo.</p>
        </div>
      )}

      {isLoading && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => <OfferCardSkeleton key={i} />)}
        </div>
      )}

      {!isLoading && !isError && offers.length === 0 && (
        <div className="text-center py-20 text-gray-400">
          <Tag className="w-10 h-10 mx-auto mb-3 text-gray-300" />
          <p className="font-medium">Belum ada promo aktif.</p>
        </div>
      )}

      {!isLoading && offers.length > 0 && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {offers.map((o) => <OfferCard key={o.id} offer={o} />)}
          </div>

          {lastPage > 1 && (
            <div className="mt-8 flex justify-center gap-2">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 disabled:opacity-40 hover:bg-gray-50 transition-colors"
              >
                Sebelumnya
              </button>
              <span className="px-4 py-2 text-sm text-gray-500">
                {page} / {lastPage}
              </span>
              <button
                onClick={() => setPage((p) => Math.min(lastPage, p + 1))}
                disabled={page === lastPage}
                className="px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 disabled:opacity-40 hover:bg-gray-50 transition-colors"
              >
                Berikutnya
              </button>
            </div>
          )}
        </>
      )}
    </main>
  )
}
