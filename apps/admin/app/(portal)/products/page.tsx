'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Ticket, Search, Plus, Pencil, Layers, Trash2, Loader2 } from 'lucide-react'
import { useState } from 'react'
import { useRouter } from 'next/navigation'
import { cn } from '@/lib/utils'
import { PageHeader } from '@/components/shared/PageHeader'
import { TableCard } from '@/components/shared/TableCard'
import { Pagination } from '@/components/shared/Pagination'

export default function ProductsPage() {
  const token       = useAdminAuthStore(s => s.token)
  const router      = useRouter()
  const queryClient = useQueryClient()

  const [search,     setSearch]     = useState('')
  const [page,       setPage]       = useState(1)
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
      <PageHeader
        icon={Ticket}
        title="Produk"
        description="Kelola produk tiket wisata"
        action={
          <button
            onClick={() => router.push('/products/new')}
            className="flex items-center gap-2 px-4 py-2 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 transition-colors"
          >
            <Plus size={16} /> Tambah Produk
          </button>
        }
      />

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

      <TableCard
        columns={[
          { label: '',            className: 'w-12' },
          'Nama Produk',
          { label: 'Deskripsi',   className: 'hidden md:table-cell' },
          'Varian',
          'Status',
          { label: 'Aksi', align: 'right' },
        ]}
        isLoading={isLoading}
        isEmpty={products.length === 0}
        emptyMessage='Belum ada produk. Klik "Tambah Produk" untuk memulai.'
        skeletonRows={5}
      >
        {products.map((p: any) => (
          <tr key={p.id} className="border-b border-border last:border-0 hover:bg-muted/20 transition-colors">
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
            <td className="px-4 py-3">
              <p className="font-medium text-foreground">{p.name}</p>
              <p className="text-xs text-muted-foreground font-mono">{p.slug}</p>
            </td>
            <td className="px-4 py-3 text-muted-foreground hidden md:table-cell max-w-xs">
              <p className="truncate">{p.description?.slice(0, 70)}{p.description?.length > 70 ? '…' : ''}</p>
            </td>
            <td className="px-4 py-3">
              <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                <Layers size={11} />
                {p.variants?.length ?? 0} varian
              </span>
            </td>
            <td className="px-4 py-3">
              <span className={cn('px-2 py-0.5 rounded-md text-xs font-medium',
                p.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500')}>
                {p.is_active ? 'Aktif' : 'Nonaktif'}
              </span>
            </td>
            <td className="px-4 py-3">
              <div className="flex items-center gap-1 justify-end">
                <button onClick={() => router.push(`/products/${p.id}`)} title="Edit Produk"
                  className="p-1.5 rounded-md text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                  <Pencil size={15} />
                </button>
                <button onClick={() => handleDelete(p.id, p.name)} disabled={deletingId === p.id} title="Hapus Produk"
                  className="p-1.5 rounded-md text-muted-foreground hover:text-destructive hover:bg-destructive/10 disabled:opacity-40 transition-colors">
                  {deletingId === p.id ? <Loader2 size={15} className="animate-spin" /> : <Trash2 size={15} />}
                </button>
              </div>
            </td>
          </tr>
        ))}
      </TableCard>

      <Pagination
        page={page}
        lastPage={meta.lastPage}
        total={meta.total}
        label="produk"
        onChange={setPage}
      />
    </div>
  )
}
