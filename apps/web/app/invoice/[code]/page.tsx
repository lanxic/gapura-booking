'use client'

import { use, useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import Link from 'next/link'
import { api } from '@/lib/api'
import { formatRupiah } from 'ui'
import type { ApiResponse, Invoice } from '@/types'
import {
  CheckCircle, Clock, XCircle, AlertCircle, Loader2,
  Copy, RefreshCw, Home, QrCode,
} from 'lucide-react'

const STATUS_CONFIG = {
  draft:    { icon: Clock,         color: 'text-gray-500',   bg: 'bg-gray-100',   label: 'Draft' },
  pending:  { icon: Clock,         color: 'text-amber-600',  bg: 'bg-amber-100',  label: 'Menunggu Pembayaran' },
  paid:     { icon: CheckCircle,   color: 'text-emerald-600',bg: 'bg-emerald-100',label: 'Pembayaran Berhasil' },
  expired:  { icon: XCircle,       color: 'text-gray-500',   bg: 'bg-gray-100',   label: 'Invoice Kadaluarsa' },
  failed:   { icon: AlertCircle,   color: 'text-red-600',    bg: 'bg-red-100',    label: 'Pembayaran Gagal' },
  refunded: { icon: RefreshCw,     color: 'text-blue-600',   bg: 'bg-blue-100',   label: 'Sudah Direfund' },
}

function CountdownTimer({ dueAt }: { dueAt: string }) {
  const [now, setNow] = useState(Date.now())
  // Update every second via simple polling on render — fine for short TTL
  const due = new Date(dueAt).getTime()
  const diff = Math.max(0, Math.floor((due - now) / 1000))
  const h = Math.floor(diff / 3600)
  const m = Math.floor((diff % 3600) / 60)
  const s = diff % 60

  // Re-render trigger — minimal approach
  if (typeof window !== 'undefined') {
    setTimeout(() => setNow(Date.now()), 1000)
  }

  if (diff === 0) return <span className="text-red-500 font-semibold">Waktu habis</span>

  return (
    <span className="font-mono font-bold text-amber-600">
      {h > 0 ? `${h}:` : ''}{String(m).padStart(2, '0')}:{String(s).padStart(2, '0')}
    </span>
  )
}

export default function InvoicePage({ params }: { params: Promise<{ code: string }> }) {
  const { code } = use(params)
  const [copied, setCopied] = useState(false)

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['invoice', code],
    queryFn: () => api.get<ApiResponse<Invoice>>(`/invoices/${code}`),
    refetchInterval: (query) => {
      // Poll every 5s saat pending, stop kalau sudah paid/expired/failed
      const status = query.state.data?.data?.status
      return status === 'pending' ? 5000 : false
    },
  })

  const invoice = data?.data
  const statusCfg = invoice ? STATUS_CONFIG[invoice.status] ?? STATUS_CONFIG.pending : null
  const StatusIcon = statusCfg?.icon ?? Clock

  const retryMutation = useMutation({
    mutationFn: () =>
      api.post<ApiResponse<{ payment_url: string }>>(`/invoices/${code}/retry-payment`, {}),
    onSuccess: (res) => {
      if (res.data.payment_url) window.location.href = res.data.payment_url
    },
  })

  const handleCopy = () => {
    navigator.clipboard.writeText(code).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-emerald-600" />
      </div>
    )
  }

  if (!invoice) {
    return (
      <div className="min-h-screen flex flex-col items-center justify-center text-gray-500 gap-4">
        <AlertCircle className="w-12 h-12 text-red-400" />
        <p>Invoice tidak ditemukan.</p>
        <Link href="/activities" className="text-emerald-600 underline text-sm">Kembali ke Aktivitas</Link>
      </div>
    )
  }

  return (
    <div className="max-w-lg mx-auto px-4 py-10">

      {/* Status card */}
      <div className={`rounded-2xl p-8 mb-6 text-center ${statusCfg?.bg ?? 'bg-gray-100'}`}>
        <div className={`w-16 h-16 rounded-full bg-white/80 flex items-center justify-center mx-auto mb-4`}>
          <StatusIcon className={`w-8 h-8 ${statusCfg?.color ?? 'text-gray-500'}`} />
        </div>
        <h1 className={`text-xl font-bold ${statusCfg?.color ?? 'text-gray-700'}`}>{statusCfg?.label}</h1>

        {invoice.status === 'pending' && (
          <p className="text-sm text-gray-600 mt-2">
            Selesaikan pembayaran dalam <CountdownTimer dueAt={invoice.due_at} />
          </p>
        )}
        {invoice.status === 'paid' && invoice.paid_at && (
          <p className="text-sm text-gray-600 mt-2">
            Dibayar pada {new Date(invoice.paid_at).toLocaleString('id-ID')}
          </p>
        )}
      </div>

      {/* Invoice details */}
      <div className="bg-white rounded-2xl border border-gray-200 divide-y divide-gray-100 mb-6">
        <div className="p-5">
          <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">Kode Invoice</p>
          <div className="flex items-center gap-2">
            <span className="font-mono font-bold text-gray-900 text-lg">{code}</span>
            <button onClick={handleCopy} className="p-1.5 rounded-md hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-700">
              <Copy className="w-4 h-4" />
            </button>
            {copied && <span className="text-xs text-emerald-600">Disalin!</span>}
          </div>
        </div>

        <div className="p-5 grid grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-gray-400 text-xs mb-0.5">Total</p>
            <p className="font-bold text-gray-900">{formatRupiah(invoice.total_amount)}</p>
          </div>
          <div>
            <p className="text-gray-400 text-xs mb-0.5">Bayar Sekarang</p>
            <p className="font-bold text-emerald-600">{formatRupiah(invoice.due_now)}</p>
          </div>
          {invoice.due_later > 0 && (
            <div>
              <p className="text-gray-400 text-xs mb-0.5">Bayar Nanti</p>
              <p className="font-semibold text-gray-700">{formatRupiah(invoice.due_later)}</p>
            </div>
          )}
          <div>
            <p className="text-gray-400 text-xs mb-0.5">Plan</p>
            <p className="font-semibold text-gray-700">{invoice.payment_plan}</p>
          </div>
        </div>

        {/* Booking code (muncul setelah paid) */}
        {invoice.status === 'paid' && invoice.booking_code && (
          <div className="p-5">
            <p className="text-xs text-gray-400 uppercase tracking-wider mb-1">Kode Booking</p>
            <div className="flex items-center gap-2">
              <QrCode className="w-5 h-5 text-emerald-600" />
              <span className="font-mono font-bold text-emerald-700 text-lg">{invoice.booking_code}</span>
            </div>
            <p className="text-xs text-gray-400 mt-1">Tunjukkan kode ini atau QR code e-ticket ke instruktur</p>
          </div>
        )}
      </div>

      {/* Actions */}
      <div className="space-y-3">
        {/* Retry payment */}
        {(invoice.status === 'pending' || invoice.status === 'failed') && (
          <button
            onClick={() => retryMutation.mutate()}
            disabled={retryMutation.isPending}
            className="w-full py-3 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-700 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
          >
            {retryMutation.isPending && <Loader2 className="w-4 h-4 animate-spin" />}
            {invoice.status === 'failed' ? 'Coba Bayar Lagi' : 'Lanjutkan Pembayaran'}
          </button>
        )}

        {/* Refresh status */}
        <button
          onClick={() => refetch()}
          disabled={isRefetching}
          className="w-full py-3 border border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
        >
          <RefreshCw className={`w-4 h-4 ${isRefetching ? 'animate-spin' : ''}`} />
          Cek Status Terbaru
        </button>

        <Link
          href="/activities"
          className="w-full py-3 flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-gray-800 transition-colors"
        >
          <Home className="w-4 h-4" /> Kembali ke Aktivitas
        </Link>
      </div>

      {retryMutation.isError && (
        <div className="mt-4 flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
          <AlertCircle className="w-4 h-4 shrink-0" />
          {(retryMutation.error as Error).message ?? 'Gagal melanjutkan pembayaran.'}
        </div>
      )}
    </div>
  )
}
