'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ticket, Search, Plus, Pencil, Layers, Trash2, Loader2, ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { cn } from '@/lib/utils'

function getPaginationRange(current: number, total: number): (number | '...')[] {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  if (current <= 4) return [1, 2, 3, 4, 5, '...', total]
  if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total]
  return [1, '...', current - 1, current, current + 1, '...', total]
}

export default function ProductsPage() {
  const token       = useAdminAuthStore(s => s.token)
  const router      = useRouter()
  const queryClient = useQueryClient()

  const [search, setSearch] = useState('')
  const [page, setPage]     = useState(1)
  const [deletingId, setDeletingId] = useState<number | null>(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-products', search, page],
    queryFn: () => api.get<any>(`/admin/products?search=${search}&page=${page}`, { token: token! }),
    enabled: !!token,
  })

  const deleteMutation = useMutation({
    mutationFn: (id: number) => api.delete<any>(`/admin/products/${id}`, { token: token! }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-products'] })
      setDeletingId(null)
    },
  })

  const products = data?.data ?? []
  const meta     = data?.meta ?? {}

  const handleDelete = (id: number, name: string) => {
    if (!confirm(`Hapus produk "${name}"? Tindakan ini tidak dapat dibatalkan.`)) return
    setDeletingId(id)
    deleteMutation.mutate(id)
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Ticket size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Produk</h1>
            <p className="text-sm text-muted-foreground">Kelola produk tiket wisata</p>
          </div>
        </div>
        <button
          onClick={() => router.push('/products/new')}
          className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-colors"
        >
          <Plus size={16} /> Tambah Produk
        </button>
      </div>

      {/* Search */}
      <div className="relative max-w-sm">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
        <input
          type="text"
          placeholder="Cari nama produk..."
          value={search}
          onChange={e => { setSearch(e.target.value); setPage(1) }}
          className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        />
      </div>

      {/* Table */}
      <div className="rounded-xl border border-border bg-card overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-border bg-muted/30">
              <th className="text-left px-4 py-3 font-medium text-muted-foreground w-12"></th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Nama Produk</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground hidden md:table-cell">Deskripsi</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Varian</th>
              <th className="text-left px-4 py-3 font-medium text-muted-foreground">Status</th>
              <th className="text-right px-4 py-3 font-medium text-muted-foreground">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              Array.from({ length: 5 }).map((_, i) => (
                <tr key={i} className="border-b border-border">
                  {Array.from({ length: 6 }).map((_, j) => (
                    <td key={j} className="px-4 py-3">
                      <div className="h-4 bg-muted rounded animate-pulse" />
                    </td>
                  ))}
                </tr>
              ))
            ) : products.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-12 text-center text-muted-foreground">
                  <Ticket size={32} className="mx-auto mb-2 opacity-30" />
                  Belum ada produk. Klik "Tambah Produk" untuk memulai.
                </td>
              </tr>
            ) : products.map((p: any) => (
              <tr key={p.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
                {/* Thumbnail */}
                <td className="px-4 py-3">
                  <div className="h-10 w-10 rounded-lg bg-muted overflow-hidden shrink-0">
                    {p.cloudinary_thumbnail_url ? (
                      <img src={p.cloudinary_thumbnail_url} alt={p.name} className="h-full w-full object-cover" />
                    ) : (
                      <div className="h-full w-full flex items-center justify-center">
                        <Ticket size={16} className="text-muted-foreground/50" />
                      </div>
                    )}
                  </div>
                </td>

                {/* Name */}
                <td className="px-4 py-3">
                  <p className="font-medium text-foreground">{p.name}</p>
                  <p className="text-xs text-muted-foreground font-mono">{p.slug}</p>
                </td>

                {/* Description */}
                <td className="px-4 py-3 text-muted-foreground hidden md:table-cell max-w-xs">
                  <p className="truncate">{p.description?.slice(0, 70)}{p.description?.length > 70 ? '...' : ''}</p>
                </td>

                {/* Variants count */}
                <td className="px-4 py-3">
                  <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                    <Layers size={11} />
                    {p.variants?.length ?? 0} varian
                  </span>
                </td>

                {/* Status */}
                <td className="px-4 py-3">
                  <span className={cn(
                    'px-2 py-0.5 rounded-md text-xs font-medium',
                    p.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500',
                  )}>
                    {p.is_active ? 'Aktif' : 'Nonaktif'}
                  </span>
                </td>

                {/* Actions */}
                <td className="px-4 py-3">
                  <div className="flex items-center gap-1 justify-end">
                    <button
                      onClick={() => router.push(`/products/${p.id}`)}
                      title="Edit Produk"
                      className="p-1.5 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors"
                    >
                      <Pencil size={15} />
                    </button>
                    <button
                      onClick={() => handleDelete(p.id, p.name)}
                      disabled={deletingId === p.id}
                      title="Hapus Produk"
                      className="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 disabled:opacity-40 transition-colors"
                    >
                      {deletingId === p.id
                        ? <Loader2 size={15} className="animate-spin" />
                        : <Trash2 size={15} />
                      }
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="flex items-center justify-between text-sm">
        <span className="text-muted-foreground">
          {meta.total
            ? `${meta.total} produk`
            : products.length > 0 ? `${products.length} produk` : ''}
        </span>

        {(meta.lastPage ?? 1) >= 1 && (
          <div className="flex items-center gap-1">
            <button
              onClick={() => setPage(p => Math.max(1, p - 1))}
              disabled={page === 1}
              className="p-1.5 rounded-md border border-border text-muted-foreground hover:bg-accent disabled:opacity-40 transition-colors"
            >
              <ChevronLeft size={15} />
            </button>

            {getPaginationRange(page, meta.lastPage ?? 1).map((item, i) =>
              item === '...' ? (
                <span key={`ellipsis-${i}`} className="px-2 py-1.5 text-muted-foreground select-none">…</span>
              ) : (
                <button
                  key={item}
                  onClick={() => setPage(item as number)}
                  className={cn(
                    'min-w-[34px] px-2 py-1.5 rounded-md border text-sm transition-colors',
                    page === item
                      ? 'border-primary bg-primary text-primary-foreground font-semibold'
                      : 'border-border text-muted-foreground hover:bg-accent',
                  )}
                >
                  {item}
                </button>
              )
            )}

            <button
              onClick={() => setPage(p => Math.min(meta.lastPage ?? 1, p + 1))}
              disabled={page === (meta.lastPage ?? 1)}
              className="p-1.5 rounded-md border border-border text-muted-foreground hover:bg-accent disabled:opacity-40 transition-colors"
            >
              <ChevronRight size={15} />
            </button>
          </div>
        )}
      </div>
    </div>
  )
}
