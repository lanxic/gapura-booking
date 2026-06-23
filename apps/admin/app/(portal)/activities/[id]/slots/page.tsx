'use client'

import { use, useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { useAdminAuthStore } from '@/store/auth'
import { formatRupiah } from 'ui'
import { ArrowLeft, Calendar, Users, Loader2, ChevronLeft, ChevronRight } from 'lucide-react'
import Link from 'next/link'

type Slot = {
  id: number
  date: string
  start_time: string
  end_time: string
  capacity: number
  booked_count: number
  price: number
  status: string
  remaining_capacity: number
}

const STATUS_COLOR: Record<string, string> = {
  available: 'bg-emerald-50 text-emerald-700 border-emerald-200',
  full:      'bg-red-50 text-red-600 border-red-200',
  cancelled: 'bg-gray-100 text-gray-500 border-gray-200',
}

const STATUS_LABEL: Record<string, string> = {
  available: 'Tersedia',
  full:      'Penuh',
  cancelled: 'Dibatalkan',
}

function getMonthRange(year: number, month: number) {
  const start = new Date(year, month, 1)
  const end   = new Date(year, month + 1, 0)
  return {
    from: start.toISOString().split('T')[0],
    to:   end.toISOString().split('T')[0],
  }
}

function parseDate(iso: string): Date {
  // Slice to YYYY-MM-DD to handle "2026-06-23 00:00:00" or "2026-06-23T00:00:00.000000Z"
  return new Date(iso.slice(0, 10) + 'T00:00:00')
}

function formatDate(iso: string) {
  return parseDate(iso).toLocaleDateString('id-ID', {
    weekday: 'short', day: 'numeric', month: 'short',
  })
}

export default function SlotsPage({ params }: { params: Promise<{ id: string }> }) {
  const { id } = use(params)
  const { token } = useAdminAuthStore()
  const qc = useQueryClient()

  const now = new Date()
  const [year, setYear]   = useState(now.getFullYear())
  const [month, setMonth] = useState(now.getMonth())

  const [editSlot, setEditSlot]   = useState<Slot | null>(null)
  const [editForm, setEditForm]   = useState({ capacity: '', price: '', status: '' })

  const { from, to } = getMonthRange(year, month)

  const { data: activityData } = useQuery({
    queryKey: ['activity-admin', id],
    queryFn: () => api.get<{ data: { name: string } }>(`/admin/activities/${id}`, { token: token! }),
    enabled: !!token,
  })

  const { data, isLoading } = useQuery({
    queryKey: ['slots-admin', id, from, to],
    queryFn: () => api.get<{ data: Slot[] }>(`/admin/activities/${id}/slots?from=${from}&to=${to}`, { token: token! }),
    enabled: !!token,
  })

  const slots = data?.data ?? []

  const updateSlot = useMutation({
    mutationFn: (slot: Slot) =>
      api.put(`/admin/slots/${slot.id}`, {
        capacity: parseInt(editForm.capacity) || slot.capacity,
        price:    parseFloat(editForm.price.replace(/[^0-9]/g, '')) || slot.price,
        status:   editForm.status || slot.status,
      }, { token: token! }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['slots-admin', id] })
      setEditSlot(null)
    },
  })

  const genSlots = useMutation({
    mutationFn: () => api.post(`/admin/activities/${id}/generate-slots`, { days: 30 }, { token: token! }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['slots-admin', id] }),
  })

  function prevMonth() {
    if (month === 0) { setYear((y) => y - 1); setMonth(11) }
    else setMonth((m) => m - 1)
  }
  function nextMonth() {
    if (month === 11) { setYear((y) => y + 1); setMonth(0) }
    else setMonth((m) => m + 1)
  }

  function openEdit(slot: Slot) {
    setEditSlot(slot)
    setEditForm({ capacity: String(slot.capacity), price: String(slot.price), status: slot.status })
  }

  const monthName = new Date(year, month, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Link href={`/activities/${id}`} className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
            <ArrowLeft className="w-5 h-5" />
          </Link>
          <div>
            <h1 className="text-xl font-bold text-foreground">Manajemen Slot</h1>
            <p className="text-sm text-muted-foreground">{activityData?.data?.name ?? '...'}</p>
          </div>
        </div>
        <button
          onClick={() => genSlots.mutate()}
          disabled={genSlots.isPending}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-semibold hover:bg-primary/90 transition-colors disabled:opacity-60"
        >
          {genSlots.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
          <Calendar className="w-4 h-4" /> Generate 30 Hari
        </button>
      </div>

      {/* Month nav */}
      <div className="flex items-center justify-between">
        <button onClick={prevMonth} className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <ChevronLeft className="w-5 h-5" />
        </button>
        <h2 className="font-semibold text-foreground capitalize">{monthName}</h2>
        <button onClick={nextMonth} className="p-2 rounded-lg hover:bg-gray-100 transition-colors">
          <ChevronRight className="w-5 h-5" />
        </button>
      </div>

      {isLoading && (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-16 bg-gray-100 rounded-xl animate-pulse" />
          ))}
        </div>
      )}

      {!isLoading && slots.length === 0 && (
        <div className="text-center py-16 text-muted-foreground">
          <Calendar className="w-10 h-10 mx-auto mb-3 text-gray-300" />
          <p className="font-medium">Tidak ada slot di bulan ini.</p>
          <p className="text-sm mt-1">Klik "Generate 30 Hari" untuk membuat slot otomatis.</p>
        </div>
      )}

      {slots.length > 0 && (
        <div className="space-y-2">
          {slots.map((slot) => (
            <div
              key={slot.id}
              className="flex items-center justify-between gap-4 rounded-xl border border-border bg-card px-4 py-3 hover:bg-accent/30 transition-colors"
            >
              <div className="flex items-center gap-4 min-w-0">
                <div className="flex-shrink-0 text-center w-12">
                  <p className="text-xs text-muted-foreground">
                    {parseDate(slot.date).toLocaleDateString('id-ID', { weekday: 'short' })}
                  </p>
                  <p className="text-base font-bold text-foreground">
                    {parseDate(slot.date).getDate()}
                  </p>
                </div>
                <div className="min-w-0">
                  <p className="font-medium text-foreground text-sm">
                    {slot.start_time} – {slot.end_time}
                  </p>
                  <div className="flex items-center gap-3 mt-0.5 text-xs text-muted-foreground">
                    <span className="flex items-center gap-1">
                      <Users className="w-3 h-3" />
                      {slot.booked_count}/{slot.capacity}
                    </span>
                    <span>{formatRupiah(slot.price)}</span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-3 flex-shrink-0">
                <span className={`rounded-full border px-2.5 py-0.5 text-xs font-semibold ${STATUS_COLOR[slot.status] ?? 'bg-gray-100 text-gray-500 border-gray-200'}`}>
                  {STATUS_LABEL[slot.status] ?? slot.status}
                </span>
                <button
                  onClick={() => openEdit(slot)}
                  className="text-xs font-medium text-primary hover:underline"
                >
                  Edit
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Edit Modal */}
      {editSlot && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
          <div className="bg-card rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-6 space-y-4">
            <h3 className="font-bold text-foreground">Edit Slot</h3>
            <p className="text-sm text-muted-foreground">
              {formatDate(editSlot.date)} · {editSlot.start_time}–{editSlot.end_time}
            </p>

            <div className="space-y-3">
              <div>
                <label className="block text-sm font-medium text-foreground mb-1">Kapasitas</label>
                <input
                  type="number" min="1"
                  value={editForm.capacity}
                  onChange={(e) => setEditForm((f) => ({ ...f, capacity: e.target.value }))}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-1">Harga (Rp)</label>
                <input
                  value={editForm.price}
                  onChange={(e) => setEditForm((f) => ({ ...f, price: e.target.value }))}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-foreground mb-1">Status</label>
                <select
                  value={editForm.status}
                  onChange={(e) => setEditForm((f) => ({ ...f, status: e.target.value }))}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20"
                >
                  <option value="available">Tersedia</option>
                  <option value="full">Penuh</option>
                  <option value="cancelled">Dibatalkan</option>
                </select>
              </div>
            </div>

            <div className="flex gap-3 pt-2">
              <button
                onClick={() => setEditSlot(null)}
                className="flex-1 py-2.5 text-sm font-medium border border-border rounded-lg hover:bg-accent transition-colors"
              >
                Batal
              </button>
              <button
                onClick={() => updateSlot.mutate(editSlot)}
                disabled={updateSlot.isPending}
                className="flex-1 flex items-center justify-center gap-2 py-2.5 bg-primary text-primary-foreground text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-60"
              >
                {updateSlot.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
                Simpan
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
