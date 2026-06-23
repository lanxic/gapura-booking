'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { Percent, CheckCircle, Loader2, AlertCircle } from 'lucide-react'
import { useState } from 'react'

type PaymentPlan = {
  id: number
  code: string
  label: string
  percentage: number
  min_amount: number
  deadline_hours: number
  is_active: boolean
}

function formatRp(n: number) { return n > 0 ? 'Rp ' + n.toLocaleString('id-ID') : 'Tidak ada minimum' }

function PlanRow({ plan, token, onSaved }: { plan: PaymentPlan; token: string; onSaved: () => void }) {
  const [minAmount, setMinAmount] = useState(String(plan.min_amount))
  const [deadlineHours, setDeadlineHours] = useState(String(plan.deadline_hours))
  const [saved, setSaved] = useState(false)
  const qc = useQueryClient()

  const toggleMutation = useMutation({
    mutationFn: () =>
      api.put(`/admin/settings/payment-plans/${plan.code}`, { is_active: !plan.is_active }, { token }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['admin-payment-plans'] }); onSaved() },
  })

  const updateMutation = useMutation({
    mutationFn: () =>
      api.put(`/admin/settings/payment-plans/${plan.code}`, {
        min_amount: Number(minAmount),
        deadline_hours: Number(deadlineHours),
      }, { token }),
    onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 3000); qc.invalidateQueries({ queryKey: ['admin-payment-plans'] }) },
  })

  const isFull = plan.code === 'FULL'

  return (
    <div className={`bg-card border rounded-xl p-5 space-y-4 ${plan.is_active ? 'border-emerald-400' : 'border-border'}`}>
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className={`w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg ${plan.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'}`}>
            {plan.percentage}%
          </div>
          <div>
            <p className="font-bold text-foreground">{plan.label}</p>
            <p className="text-xs text-muted-foreground">Kode: {plan.code}</p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          {plan.is_active && <span className="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Aktif</span>}
          {!isFull && (
            <button
              onClick={() => toggleMutation.mutate()}
              disabled={toggleMutation.isPending}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${plan.is_active ? 'bg-emerald-500' : 'bg-gray-300'} disabled:opacity-50`}
            >
              <span className={`inline-block h-4 w-4 rounded-full bg-white shadow transform transition-transform ${plan.is_active ? 'translate-x-6' : 'translate-x-1'}`} />
            </button>
          )}
        </div>
      </div>

      {!isFull && (
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1.5">
              Minimum Amount (Rp)
            </label>
            <input
              type="number"
              value={minAmount}
              onChange={e => setMinAmount(e.target.value)}
              min="0"
              className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
            <p className="text-xs text-muted-foreground mt-1">{formatRp(Number(minAmount))}</p>
          </div>
          <div>
            <label className="block text-xs font-medium text-muted-foreground mb-1.5">
              Deadline Pelunasan (jam)
            </label>
            <input
              type="number"
              value={deadlineHours}
              onChange={e => setDeadlineHours(e.target.value)}
              min="1"
              className="w-full text-sm border border-border rounded-lg px-3 py-2 bg-background focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
            <p className="text-xs text-muted-foreground mt-1">{deadlineHours} jam setelah booking</p>
          </div>
        </div>
      )}

      {isFull && (
        <p className="text-xs text-muted-foreground">
          FULL payment tidak dapat dinonaktifkan — selalu tersedia sebagai opsi default.
        </p>
      )}

      {!isFull && (
        <div className="flex items-center justify-end gap-3">
          {saved && (
            <span className="flex items-center gap-1 text-sm text-emerald-600">
              <CheckCircle size={14} /> Tersimpan
            </span>
          )}
          <button
            onClick={() => updateMutation.mutate()}
            disabled={updateMutation.isPending}
            className="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition-colors disabled:opacity-50 flex items-center gap-2"
          >
            {updateMutation.isPending && <Loader2 size={14} className="animate-spin" />}
            Simpan
          </button>
        </div>
      )}
    </div>
  )
}

export default function PaymentPlansPage() {
  const token = useAdminAuthStore(s => s.token)!
  const qc = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['admin-payment-plans'],
    queryFn: () => api.get<{ data: PaymentPlan[] }>('/admin/settings/payment-plans', { token }),
  })

  const plans = data?.data ?? []

  return (
    <div className="space-y-6 max-w-2xl">
      <div className="flex items-center gap-3">
        <Percent size={24} className="text-muted-foreground" />
        <div>
          <h1 className="text-2xl font-bold text-foreground">Payment Plans</h1>
          <p className="text-sm text-muted-foreground">Konfigurasi opsi DP untuk booking aktivitas</p>
        </div>
      </div>

      <div className="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3 text-sm text-blue-700">
        <AlertCircle size={16} className="shrink-0 mt-0.5" />
        <p>Opsi DP hanya tersedia jika booking dilakukan ≥3 hari sebelum aktivitas. Booking kurang dari 3 hari otomatis menggunakan FULL payment.</p>
      </div>

      {isLoading ? (
        <div className="flex items-center gap-2 text-muted-foreground py-10">
          <Loader2 className="animate-spin w-5 h-5" /> Memuat...
        </div>
      ) : (
        <div className="space-y-4">
          {plans.map(p => (
            <PlanRow
              key={p.id}
              plan={p}
              token={token}
              onSaved={() => qc.invalidateQueries({ queryKey: ['admin-payment-plans'] })}
            />
          ))}
        </div>
      )}
    </div>
  )
}
