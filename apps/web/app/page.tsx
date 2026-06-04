'use client'

import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn } from 'ui'
import type { Product, ApiResponse } from '@/types'
import { Zap, LayoutGrid, AlignJustify } from 'lucide-react'

// ─── Grid card ────────────────────────────────────────────────────────────────
function GridCard({ product }: { product: Product }) {
  const thumb = product.cloudinaryThumbnailUrl ?? product.cloudinaryImageUrl

  return (
    <a
      href={`/products/${product.slug}`}
      className="group bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition-shadow duration-200"
    >
      {/* Image */}
      <div className="h-44 bg-gradient-to-br from-emerald-100 to-teal-200 overflow-hidden flex-shrink-0">
        {thumb ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={thumb}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full bg-gradient-to-br from-emerald-200 to-teal-300" />
        )}
      </div>

      {/* Content */}
      <div className="p-3 flex flex-col flex-1 justify-between gap-2">
        <div>
          <p className="text-emerald-700 font-semibold text-sm leading-snug line-clamp-2">
            {product.name}
          </p>
          {product.instantConfirmation && (
            <p className="flex items-center gap-1 text-emerald-600 text-xs mt-1">
              <Zap className="w-3 h-3 fill-emerald-500 text-emerald-500" />
              Konfirmasi Instan
            </p>
          )}
        </div>
        <div className="flex justify-end">
          <span className="bg-emerald-800 text-white text-xs font-semibold px-4 py-1.5 rounded-md hover:bg-emerald-700 transition-colors">
            Beli Sekarang
          </span>
        </div>
      </div>
    </a>
  )
}

// ─── List card ────────────────────────────────────────────────────────────────
function ListCard({ product }: { product: Product }) {
  const thumb = product.cloudinaryThumbnailUrl ?? product.cloudinaryImageUrl

  return (
    <a
      href={`/products/${product.slug}`}
      className="group bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden flex hover:shadow-md transition-shadow duration-200"
    >
      {/* Thumbnail */}
      <div className="w-36 flex-shrink-0 bg-gradient-to-br from-emerald-100 to-teal-200 overflow-hidden">
        {thumb ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={thumb}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full bg-gradient-to-br from-emerald-200 to-teal-300" />
        )}
      </div>

      {/* Content */}
      <div className="flex-1 p-4 flex flex-col justify-between min-w-0 gap-2">
        <div>
          <p className="text-emerald-700 font-semibold text-sm leading-snug line-clamp-1">
            {product.name}
          </p>
          {product.instantConfirmation && (
            <p className="flex items-center gap-1 text-emerald-600 text-xs mt-1 mb-2">
              <Zap className="w-3 h-3 fill-emerald-500 text-emerald-500" />
              Konfirmasi Instan
            </p>
          )}
          {product.location && (
            <p className="text-xs text-gray-500 mb-1 line-clamp-1">{product.location}</p>
          )}
          {product.description && (
            <p className="text-xs text-gray-600 line-clamp-3 leading-relaxed">
              {product.description}
            </p>
          )}
        </div>
        <div className="flex justify-end">
          <span className="bg-emerald-800 text-white text-xs font-semibold px-4 py-1.5 rounded-md hover:bg-emerald-700 transition-colors">
            Beli Sekarang
          </span>
        </div>
      </div>
    </a>
  )
}

// ─── Skeleton ─────────────────────────────────────────────────────────────────
function GridSkeleton() {
  return (
    <div className="bg-white rounded-lg border border-gray-100 overflow-hidden animate-pulse">
      <div className="h-44 bg-gray-200" />
      <div className="p-3 space-y-2">
        <div className="h-3.5 bg-gray-200 rounded w-3/4" />
        <div className="h-3 bg-gray-200 rounded w-1/2" />
        <div className="flex justify-end mt-2">
          <div className="h-7 bg-gray-200 rounded w-24" />
        </div>
      </div>
    </div>
  )
}

// ─── Page ─────────────────────────────────────────────────────────────────────
export default function HomePage() {
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid')

  const { data, isLoading, isError } = useQuery({
    queryKey: ['products'],
    queryFn: () => api.get<ApiResponse<Product[]>>('/products'),
  })

  const products = data?.data ?? []

  // Use the first product's gallery/main image as the hero fallback
  const heroImage =
    products.find(p => p.cloudinaryGalleryUrls.length > 0)?.cloudinaryGalleryUrls[0] ??
    products.find(p => p.cloudinaryImageUrl)?.cloudinaryImageUrl ??
    null

  return (
    <>
      {/* ── Hero ── */}
      <div className="w-full overflow-hidden" style={{ maxHeight: '420px' }}>
        {heroImage ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={heroImage}
            alt="Taman Safari Bali"
            className="w-full object-cover object-center"
            style={{ height: '420px' }}
          />
        ) : (
          <div
            className="w-full bg-gradient-to-b from-emerald-900 via-emerald-700 to-emerald-500"
            style={{ height: '420px' }}
          />
        )}
      </div>

      {/* ── Products section ── */}
      <div className="max-w-5xl mx-auto px-4 py-10">
        {/* Section title */}
        <div className="text-center mb-8">
          <p className="text-emerald-700 text-lg font-semibold italic">Taman Safari Bali</p>
          <h2 className="text-2xl font-bold text-gray-900 mt-0.5">Tiket Umum</h2>
          <div className="w-12 h-0.5 bg-emerald-800 mx-auto mt-2" />
        </div>

        {/* Grid / List toggle */}
        <div className="flex justify-end mb-4">
          <div className="flex items-center gap-1 border border-gray-200 rounded-md p-0.5 bg-white">
            <button
              onClick={() => setViewMode('grid')}
              className={cn(
                'p-1.5 rounded transition-colors',
                viewMode === 'grid'
                  ? 'bg-emerald-700 text-white'
                  : 'text-gray-400 hover:text-gray-600',
              )}
              aria-label="Grid view"
            >
              <LayoutGrid className="w-4 h-4" />
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={cn(
                'p-1.5 rounded transition-colors',
                viewMode === 'list'
                  ? 'bg-emerald-700 text-white'
                  : 'text-gray-400 hover:text-gray-600',
              )}
              aria-label="List view"
            >
              <AlignJustify className="w-4 h-4" />
            </button>
          </div>
        </div>

        {/* Error */}
        {isError && (
          <div className="text-center py-16 text-gray-500">
            <p className="font-medium">Gagal memuat produk.</p>
            <p className="text-sm mt-1">Silakan refresh halaman.</p>
          </div>
        )}

        {/* Loading */}
        {isLoading && (
          <div className={cn(
            viewMode === 'grid'
              ? 'grid grid-cols-2 sm:grid-cols-3 gap-4'
              : 'flex flex-col gap-3',
          )}>
            {Array.from({ length: 6 }).map((_, i) => (
              <GridSkeleton key={i} />
            ))}
          </div>
        )}

        {/* Empty */}
        {!isLoading && !isError && products.length === 0 && (
          <div className="text-center py-16 text-gray-500">
            <p className="font-medium">Belum ada produk tersedia.</p>
          </div>
        )}

        {/* Product list */}
        {!isLoading && products.length > 0 && (
          viewMode === 'grid' ? (
            <div className="grid grid-cols-2 sm:grid-cols-3 gap-4">
              {products.map(p => <GridCard key={p.id} product={p} />)}
            </div>
          ) : (
            <div className="flex flex-col gap-3">
              {products.map(p => <ListCard key={p.id} product={p} />)}
            </div>
          )
        )}
      </div>
    </>
  )
}
