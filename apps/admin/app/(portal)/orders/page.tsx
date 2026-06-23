'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ShoppingBag, Search, Calendar, User, GripVertical, Receipt } from 'lucide-react'
import { useState, useEffect } from 'react'
import { cn } from '@/lib/utils'
import { OrderDetailModal } from '@/components/shared/OrderDetailModal'

const PAYMENT_STATUS_LABEL: Record<string, string> = {
  pending:  'Pending',
  success:  'Paid',
  failed:   'Failed',
  expired:  'Expired',
  refunded: 'Refunded',
}

const PAYMENT_STATUS_CLS: Record<string, string> = {
  pending:  'bg-amber-50 text-amber-700',
  success:  'bg-emerald-50 text-emerald-700',
  failed:   'bg-red-50 text-red-600',
  expired:  'bg-slate-50 text-slate-500',
  refunded: 'bg-gray-50 text-gray-500',
}

// ── Column definitions ────────────────────────────────────────────────────────

const COLUMNS = [
  { key: 'pending',          label: 'Pending',          headerCls: 'bg-amber-50 border-amber-200 text-amber-700',       dotCls: 'bg-amber-400' },
  { key: 'awaiting_payment', label: 'Awaiting Payment', headerCls: 'bg-orange-50 border-orange-200 text-orange-700',    dotCls: 'bg-orange-400' },
  { key: 'dp_paid',          label: 'Deposit Paid',     headerCls: 'bg-blue-50 border-blue-200 text-blue-700',          dotCls: 'bg-blue-500' },
  { key: 'paid',             label: 'Paid',             headerCls: 'bg-emerald-50 border-emerald-200 text-emerald-700', dotCls: 'bg-emerald-500' },
  { key: 'confirmed',        label: 'Confirmed',        headerCls: 'bg-green-50 border-green-200 text-green-700',       dotCls: 'bg-green-500' },
  { key: 'cancelled',        label: 'Cancelled',        headerCls: 'bg-red-50 border-red-200 text-red-700',             dotCls: 'bg-red-400' },
  { key: 'refunded',         label: 'Refunded',         headerCls: 'bg-gray-50 border-gray-200 text-gray-600',          dotCls: 'bg-gray-400' },
  { key: 'expired',          label: 'Expired',          headerCls: 'bg-slate-50 border-slate-200 text-slate-500',       dotCls: 'bg-slate-300' },
]

// ── OrderCard ─────────────────────────────────────────────────────────────────

function OrderCard({ order, statusKey, isDragging, onDragStart, onDragEnd, onSelect }: {
  order: any
  statusKey: string
  isDragging: boolean
  onDragStart: (e: React.DragEvent<HTMLDivElement>, order: any, fromStatus: string) => void
  onDragEnd: () => void
  onSelect: (id: number) => void
}) {
  return (
    <div
      draggable
      onDragStart={e => onDragStart(e, order, statusKey)}
      onDragEnd={onDragEnd}
      onClick={() => onSelect(order.id)}
      className={cn(
        'group bg-card border border-border rounded-xl p-3.5 space-y-2.5 select-none',
        'cursor-grab active:cursor-grabbing transition-all duration-150',
        isDragging
          ? 'opacity-40 scale-[0.97] shadow-none'
          : 'hover:shadow-md hover:border-primary/25',
      )}
    >
      <div className="flex items-start justify-between gap-2">
        <div className="flex items-center gap-1.5 min-w-0">
          <GripVertical
            size={12}
            className="shrink-0 text-muted-foreground/30 group-hover:text-muted-foreground/60 transition-colors"
          />
          <span className="font-mono text-[11px] font-semibold text-foreground tracking-wide truncate">
            {order.booking_code}
          </span>
        </div>
        <span className="text-xs font-bold text-foreground tabular-nums shrink-0">
          Rp {order.total?.toLocaleString('id-ID')}
        </span>
      </div>

      <div className="flex items-start gap-1.5">
        <User size={11} className="text-muted-foreground shrink-0 mt-0.5" />
        <div className="min-w-0">
          <p className="text-xs font-medium text-foreground truncate leading-tight">{order.customer_name}</p>
          <p className="text-[10px] text-muted-foreground truncate leading-tight">{order.customer_email}</p>
        </div>
      </div>

      <div className="flex items-center gap-1.5 text-[10px] text-muted-foreground">
        <Calendar size={10} className="shrink-0" />
        {new Date(order.created_at).toLocaleDateString('en-GB', {
          day: 'numeric', month: 'short', year: 'numeric',
        })}
      </div>

      {/* Invoice / payment terakhir */}
      {order.payments && order.payments.length > 0 && (() => {
        const last = order.payments[order.payments.length - 1]
        return (
          <div className="flex items-center justify-between gap-1.5 pt-1 border-t border-border/60">
            <div className="flex items-center gap-1 min-w-0">
              <Receipt size={10} className="shrink-0 text-muted-foreground/60" />
              <span className="font-mono text-[9px] text-muted-foreground truncate">
                {last.invoice_number ?? 'cash'}
              </span>
            </div>
            <span className={cn(
              'text-[9px] font-semibold px-1.5 py-0.5 rounded-full shrink-0',
              PAYMENT_STATUS_CLS[last.status] ?? 'bg-gray-50 text-gray-500',
            )}>
              {PAYMENT_STATUS_LABEL[last.status] ?? last.status}
            </span>
          </div>
        )
      })()}
    </div>
  )
}

// ── KanbanColumn ──────────────────────────────────────────────────────────────

function KanbanColumn({ statusKey, label, headerCls, dotCls, search, token,
  isDragOver, draggingId, onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop, onSelect,
}: {
  statusKey: string
  label: string
  headerCls: string
  dotCls: string
  search: string
  token: string
  isDragOver: boolean
  draggingId: number | null
  onDragStart: (e: React.DragEvent<HTMLDivElement>, order: any, fromStatus: string) => void
  onDragEnd: () => void
  onDragOver: (e: React.DragEvent<HTMLDivElement>) => void
  onDragLeave: (e: React.DragEvent<HTMLDivElement>) => void
  onDrop: (e: React.DragEvent<HTMLDivElement>) => void
  onSelect: (id: number) => void
}) {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-orders-kanban', statusKey, search],
    queryFn:  () => api.get<any>(
      `/admin/orders?status=${statusKey}&search=${encodeURIComponent(search)}&per_page=50`,
      { token },
    ),
    enabled: !!token,
  })

  const orders: any[] = data?.data ?? []
  const total: number  = data?.meta?.total ?? 0

  return (
    <div className="w-[272px] shrink-0 flex flex-col h-full gap-2.5">
      {/* Header */}
      <div className={cn(
        'flex items-center justify-between px-3 py-2.5 rounded-xl border transition-all duration-150',
        headerCls,
        isDragOver && 'scale-[1.02] shadow-sm',
      )}>
        <div className="flex items-center gap-2">
          <span className={cn('w-2 h-2 rounded-full shrink-0', dotCls)} />
          <span className="text-xs font-semibold leading-none">{label}</span>
        </div>
        <span className="text-xs font-bold tabular-nums">
          {isLoading ? '…' : total}
        </span>
      </div>

      {/* Drop zone */}
      <div
        onDragOver={onDragOver}
        onDragLeave={onDragLeave}
        onDrop={onDrop}
        className={cn(
          'flex-1 min-h-0 flex flex-col gap-2 overflow-y-auto rounded-xl p-1 -m-1 transition-colors duration-150',
          isDragOver && 'bg-primary/5 ring-2 ring-inset ring-primary/20',
        )}
      >
        {isLoading
          ? Array.from({ length: 3 }).map((_, i) => (
              <div key={i} className="h-[88px] rounded-xl bg-muted animate-pulse" />
            ))
          : orders.length === 0
            ? (
              <div className={cn(
                'flex items-center justify-center py-10 rounded-xl border-2 border-dashed transition-colors duration-150',
                isDragOver ? 'border-primary/40 text-primary/60' : 'border-border text-muted-foreground/40',
              )}>
                <span className="text-[11px]">{isDragOver ? 'Drop here' : 'Empty'}</span>
              </div>
            )
            : orders.map((order: any) => (
              <OrderCard
                key={order.id}
                order={order}
                statusKey={statusKey}
                isDragging={draggingId === order.id}
                onDragStart={onDragStart}
                onDragEnd={onDragEnd}
                onSelect={onSelect}
              />
            ))
        }
        {!isLoading && total > orders.length && (
          <p className="text-[10px] text-center text-muted-foreground pb-1 tabular-nums">
            +{total - orders.length} more orders
          </p>
        )}
      </div>
    </div>
  )
}

// ── Page ──────────────────────────────────────────────────────────────────────

export default function OrdersPage() {
  const token = useAdminAuthStore(s => s.token)
  const qc    = useQueryClient()

  const [searchInput,     setSearchInput]     = useState('')
  const [search,          setSearch]          = useState('')
  const [dragOver,        setDragOver]        = useState<string | null>(null)
  const [dragging,        setDragging]        = useState<{ id: number; fromStatus: string } | null>(null)
  const [selectedOrderId, setSelectedOrderId] = useState<number | null>(null)

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput), 400)
    return () => clearTimeout(t)
  }, [searchInput])

  const moveMutation = useMutation({
    mutationFn: ({ id, status }: { id: number; status: string }) =>
      api.put<any>(`/admin/orders/${id}/status`, { status }, { token: token! }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['admin-orders-kanban'] })
    },
  })

  const handleDragStart = (e: React.DragEvent<HTMLDivElement>, order: any, fromStatus: string) => {
    e.dataTransfer.effectAllowed = 'move'
    setDragging({ id: order.id, fromStatus })
  }

  const handleDrop = (e: React.DragEvent<HTMLDivElement>, toStatus: string) => {
    e.preventDefault()
    if (!dragging || dragging.fromStatus === toStatus) {
      setDragging(null)
      setDragOver(null)
      return
    }
    moveMutation.mutate({ id: dragging.id, status: toStatus })
    setDragging(null)
    setDragOver(null)
  }

  return (
    <>
    <div className="h-full flex flex-col gap-6">

      {/* Header */}
      <div className="flex items-start justify-between gap-4 flex-wrap shrink-0">
        <div className="flex items-center gap-3">
          <ShoppingBag size={24} className="text-muted-foreground" />
          <div>
            <h1 className="text-2xl font-bold text-foreground">Orders</h1>
            <p className="text-sm text-muted-foreground">Drag cards to change order status</p>
          </div>
        </div>

        <div className="relative w-72">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <input
            type="text"
            placeholder="Search booking code / name / email…"
            value={searchInput}
            onChange={e => setSearchInput(e.target.value)}
            className="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
          />
        </div>
      </div>

      {/* Kanban board */}
      <div className="flex-1 min-h-0 flex gap-3 overflow-x-auto pb-4 -mx-1 px-1">
        {COLUMNS.map(col => (
          <KanbanColumn
            key={col.key}
            statusKey={col.key}
            label={col.label}
            headerCls={col.headerCls}
            dotCls={col.dotCls}
            search={search}
            token={token!}
            isDragOver={dragOver === col.key}
            draggingId={dragging?.id ?? null}
            onDragStart={handleDragStart}
            onDragEnd={() => setDragging(null)}
            onDragOver={e => { e.preventDefault(); setDragOver(col.key) }}
            onDragLeave={() => setDragOver(null)}
            onDrop={e => handleDrop(e, col.key)}
            onSelect={setSelectedOrderId}
          />
        ))}
      </div>

    </div>

    <OrderDetailModal
      orderId={selectedOrderId}
      token={token!}
      onClose={() => setSelectedOrderId(null)}
    />
    </>
  )
}
