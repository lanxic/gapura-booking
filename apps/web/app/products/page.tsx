'use client'

import { useQuery } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah } from 'ui'
import type { Product, ApiResponse } from '@/types'
import { MapPin, Search } from 'lucide-react'
import { useState } from 'react'

function ProductCard({ product }: { product: Product }) {
  const lowestAdult =
    product.variants.length > 0
      ? Math.min(...product.variants.map((v) => v.priceAdult))
      : null

  return (
    <div
      role="button"
      tabIndex={0}
      className={cn(
        'group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden',
        'hover:shadow-md hover:-translate-y-0.5 transition-all duration-200',
      )}
    >
      <div className="relative h-44 bg-gradient-to-br from-emerald-100 to-teal-200">
        {product.cloudinaryThumbnailUrl ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={product.cloudinaryThumbnailUrl}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center">
            <MapPin className="w-10 h-10 text-emerald-400" />
          </div>
        )}
      </div>
      <div className="p-4">
        <h3 className="font-semibold text-gray-900 line-clamp-1 mb-1">{product.name}</h3>
        <p className="text-sm text-gray-500 line-clamp-2 mb-3">{product.description}</p>
        <div className="flex items-end justify-between">
          <div>
            {lowestAdult !== null ? (
              <>
                <span className="text-xs text-gray-400">Mulai dari</span>
                <p className="font-bold text-emerald-700">{formatRupiah(lowestAdult)}</p>
              </>
            ) : (
              <span className="text-sm text-gray-400">Hubungi kami</span>
            )}
          </div>
          <span className="bg-emerald-600 text-white text-sm px-3 py-1.5 rounded-full">
            Pesan
          </span>
        </div>
      </div>
    </div>
  )
}

export default function ProductsPage() {
  const [search, setSearch] = useState('')

  const { data, isLoading, isError } = useQuery({
    queryKey: ['products'],
    queryFn: () => api.get<ApiResponse<Product[]>>('/products'),
  })

  const products = (data?.data ?? []).filter((p) =>
    p.name.toLowerCase().includes(search.toLowerCase()),
  )

  return (
    <div className="max-w-5xl mx-auto px-4 py-10">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-1">Produk Wisata</h1>
        <p className="text-gray-500">Temukan pengalaman wisata yang sempurna untukmu</p>
      </div>

      {/* Search */}
      <div className="relative mb-8 max-w-md">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          type="text"
          placeholder="Cari produk wisata..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white"
        />
      </div>

      {isError && (
        <div className="text-center py-16 text-gray-500">
          <p className="font-medium">Gagal memuat data produk.</p>
          <p className="text-sm mt-1">Periksa koneksi internet Anda dan coba lagi.</p>
        </div>
      )}

      {isLoading && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-pulse">
              <div className="h-44 bg-gray-200" />
              <div className="p-4 space-y-3">
                <div className="h-4 bg-gray-200 rounded w-3/4" />
                <div className="h-3 bg-gray-200 rounded w-full" />
                <div className="h-3 bg-gray-200 rounded w-2/3" />
              </div>
            </div>
          ))}
        </div>
      )}

      {!isLoading && !isError && products.length === 0 && (
        <div className="text-center py-16 text-gray-500">
          <p className="font-medium">
            {search ? `Tidak ada produk dengan kata kunci "${search}"` : 'Belum ada produk tersedia'}
          </p>
        </div>
      )}

      {!isLoading && products.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {products.map((product) => (
            <ProductCard key={product.id} product={product} />
          ))}
        </div>
      )}
    </div>
  )
}
