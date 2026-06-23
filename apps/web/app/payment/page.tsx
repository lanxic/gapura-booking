'use client'

import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { useEffect, useRef, useState } from 'react'
import { useQuery, useMutation } from '@tanstack/react-query'
import { api } from '@/lib/api'
import { cn, formatRupiah, formatDate } from 'ui'
import { useAuthStore } from '@/store/auth'
import type { ApiResponse, Order } from '@/types'
import {
  CheckCircle,
  XCircle,
  Clock,
  AlertCircle,
  Loader2,
  RefreshCw,
  ExternalLink,
  CreditCard,
  Banknote,
} from 'lucide-react'

type PaymentOptions = {
  full_payment: boolean
  down_payment: boolean
  dp_percentages: number[]
  midtrans_enabled: boolean
  doku_enabled: boolean
  cash_enabled: boolean
  midtrans_snap_url: string
}

type InitiateResponse = {
  data: {
    gateway: string
    snap_token?: string
    snap_url?: string
    payment_url?: string
    invoice_number: string
  }
}

function StatusIcon({ status }: { status: string }) {
  if (status === 'paid' || status === 'confirmed' || status === 'dp_paid')
    return <CheckCircle className="w-12 h-12 text-emerald-600" />
  if (status === 'cancelled' || status === 'refunded')
    return <XCircle className="w-12 h-12 text-red-500" />
  if (status === 'expired')
    return <AlertCircle className="w-12 h-12 text-orange-500" />
  return <Clock className="w-12 h-12 text-amber-500" />
}

function statusLabel(status: string) {
  const labels: Record<string, string> = {
    pending: 'Awaiting Payment',
    awaiting_payment: 'Awaiting Payment',
    dp_paid: 'Deposit Paid',
    paid: 'Paid',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
    refunded: 'Refunded',
    expired: 'Expired',
  }
  return labels[status] ?? status
}

function statusColor(status: string) {
  if (status === 'paid' || status === 'confirmed' || status === 'dp_paid')
    return 'text-emerald-700 bg-emerald-50 border-emerald-200'
  if (status === 'cancelled' || status === 'refunded')
    return 'text-red-700 bg-red-50 border-red-200'
  if (status === 'expired')
    return 'text-orange-700 bg-orange-50 border-orange-200'
  return 'text-amber-700 bg-amber-50 border-amber-200'
}

export default function PaymentPage() {
  const searchParams = useSearchParams()
  const auth = useAuthStore()

  const bookingCode = searchParams.get('code') ?? ''
  const statusParam = searchParams.get('transaction_status')

  const [pollingEnabled, setPollingEnabled] = useState(
    statusParam === 'pending' || statusParam === null,
  )

  // Payment selection state
  const [selectedGateway, setSelectedGateway] = useState<'midtrans' | 'doku' | 'cash' | null>(null)
  const [selectedPayType, setSelectedPayType] = useState<'full' | 'down_payment'>('full')
  const [selectedDpPercent, setSelectedDpPercent] = useState<number>(30)

  // Snap state
  const [snapToken, setSnapToken] = useState<string | null>(null)
  const [snapUrl, setSnapUrl] = useState<string | null>(null)
  const snapScriptLoaded = useRef(false)

  // ── Fetch order ──────────────────────────────────────────────────────────────
  const { data: orderData, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ['order', bookingCode],
    queryFn: () =>
      api.get<ApiResponse<Order>>(`/orders/${bookingCode}`, {
        token: auth.token ?? undefined,
      }),
    enabled: !!bookingCode,
    refetchInterval: pollingEnabled ? 5000 : false,
  })

  // ── Fetch payment options ────────────────────────────────────────────────────
  const { data: optData } = useQuery({
    queryKey: ['payment-options'],
    queryFn: () => api.get<ApiResponse<PaymentOptions>>('/payment-options'),
  })

  const order = orderData?.data as unknown as Record<string, unknown> | undefined
  const opts = optData?.data

  const orderStatus = order?.status as string | undefined
  const canPay = orderStatus === 'pending' || orderStatus === 'awaiting_payment'

  // Stop polling when terminal status reached
  useEffect(() => {
    if (
      orderStatus === 'paid' || orderStatus === 'confirmed' ||
      orderStatus === 'cancelled' || orderStatus === 'refunded' || orderStatus === 'expired'
    ) {
      setPollingEnabled(false)
    }
  }, [orderStatus])

  // Default gateway selection once opts load
  useEffect(() => {
    if (!opts || selectedGateway) return
    if (opts.midtrans_enabled) setSelectedGateway('midtrans')
    else if (opts.doku_enabled) setSelectedGateway('doku')
    else if (opts.cash_enabled) setSelectedGateway('cash')
  }, [opts, selectedGateway])

  // ── Check for existing pending payment ──────────────────────────────────────
  const payments = order?.payments as Array<Record<string, unknown>> | undefined
  const pendingPayment = payments?.find(
    (p) => p.status === 'pending' && (p.snap_token || p.ref_id),
  )
  const existingSnapToken = pendingPayment?.snap_token as string | undefined
  const existingDokuUrl   = pendingPayment?.ref_id   as string | undefined

  // ── Initiate payment mutation ────────────────────────────────────────────────
  const initiate = useMutation({
    mutationFn: () =>
      api.post<InitiateResponse>(`/orders/${bookingCode}/pay`, {
        gateway: selectedGateway,
        ...(selectedPayType === 'down_payment' ? {
          payment_type: 'down_payment',
          dp_percent: selectedDpPercent,
        } : {}),
      }),
    onSuccess: (res) => {
      const d = res.data
      if (d.snap_token) {
        setSnapToken(d.snap_token)
        setSnapUrl(d.snap_url ?? opts?.midtrans_snap_url ?? null)
      } else if (d.payment_url) {
        window.location.href = d.payment_url
      }
    },
  })

  // ── Load and invoke Midtrans Snap ────────────────────────────────────────────
  useEffect(() => {
    const token = snapToken ?? existingSnapToken
    const url   = snapUrl ?? opts?.midtrans_snap_url
    if (!token || !url || snapScriptLoaded.current) return

    const existing = document.querySelector(`script[src="${url}"]`)
    if (existing) {
      invokeSnap(token)
      return
    }

    const script = document.createElement('script')
    script.src = url
    script.setAttribute('data-client-key', '')
    script.onload = () => {
      snapScriptLoaded.current = true
      invokeSnap(token)
    }
    document.head.appendChild(script)
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [snapToken, existingSnapToken, snapUrl, opts?.midtrans_snap_url])

  function invokeSnap(token: string) {
    const w = window as Window & { snap?: { pay: (t: string, opts?: Record<string, unknown>) => void } }
    w.snap?.pay(token, {
      onSuccess: () => { setPollingEnabled(true); refetch() },
      onPending: () => { setPollingEnabled(true); refetch() },
      onError:   () => refetch(),
      onClose:   () => refetch(),
    })
  }

  // ── Render ───────────────────────────────────────────────────────────────────

  if (isLoading) {
    return (
      <div className="max-w-lg mx-auto px-4 py-20 flex flex-col items-center gap-4 text-gray-500">
        <Loader2 className="w-8 h-8 animate-spin text-emerald-600" />
        <p>Loading payment status...</p>
      </div>
    )
  }

  if (!order && !isLoading) {
    return (
      <div className="max-w-lg mx-auto px-4 py-20 text-center text-gray-500">
        <AlertCircle className="w-10 h-10 mx-auto mb-3 text-gray-400" />
        <p className="font-medium">Order not found.</p>
        {bookingCode && (
          <p className="text-sm mt-1">Please check your email for order details.</p>
        )}
        <Link href="/" className="text-emerald-600 hover:underline text-sm mt-3 inline-block">Back to Home</Link>
      </div>
    )
  }

  const total          = order?.total as number
  const subtotal       = order?.subtotal as number
  const discount       = order?.discount as number
  const paymentType    = order?.payment_type as string
  const dpPercent      = order?.dp_percent as number | null
  const dpAmount       = order?.dp_amount as number | null
  const remainingAmt   = order?.remaining_amount as number
  const expiresAt      = order?.expires_at as string | null
  const orderId        = order?.id

  return (
    <div className="max-w-lg mx-auto px-4 py-12">

      {/* ── Status Card ──────────────────────────────────────────────────────── */}
      <div className="bg-white rounded-2xl border border-gray-200 p-8 text-center mb-6">
        <div className="flex justify-center mb-4">
          <div className={cn(
            'w-20 h-20 rounded-full flex items-center justify-center',
            orderStatus === 'paid' || orderStatus === 'confirmed' || orderStatus === 'dp_paid'
              ? 'bg-emerald-100'
              : orderStatus === 'cancelled' || orderStatus === 'refunded'
              ? 'bg-red-100'
              : orderStatus === 'expired'
              ? 'bg-orange-100'
              : 'bg-amber-100',
          )}>
            <StatusIcon status={orderStatus ?? 'pending'} />
          </div>
        </div>

        <h1 className="text-xl font-bold text-gray-900 mb-2">
          {statusLabel(orderStatus ?? 'pending')}
        </h1>

        <span className={cn(
          'inline-block px-3 py-1 rounded-full text-sm font-medium border',
          statusColor(orderStatus ?? 'pending'),
        )}>
          {statusLabel(orderStatus ?? 'pending')}
        </span>

        {canPay && pollingEnabled && (
          <p className="text-sm text-gray-400 mt-3 flex items-center justify-center gap-1">
            <RefreshCw className="w-3.5 h-3.5 animate-spin" />
            Auto-refreshing every 5 seconds...
          </p>
        )}
      </div>

      {/* ── Order Details ────────────────────────────────────────────────────── */}
      {order && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6 space-y-3">
          <h2 className="font-semibold text-gray-800">Order Details</h2>

          <div className="grid grid-cols-2 gap-3 text-sm">
            <div>
              <p className="text-gray-400">Order ID</p>
              <p className="font-mono font-bold text-gray-800">#{orderId as string}</p>
            </div>
            <div>
              <p className="text-gray-400">Name</p>
              <p className="font-medium text-gray-800">{order.customer_name as string}</p>
            </div>
          </div>

          <div className="border-t border-gray-100 pt-3 space-y-1 text-sm">
            <div className="flex justify-between text-gray-600">
              <span>Subtotal</span>
              <span>{formatRupiah(subtotal)}</span>
            </div>
            {discount > 0 && (
              <div className="flex justify-between text-emerald-600">
                <span>Discount</span>
                <span>-{formatRupiah(discount)}</span>
              </div>
            )}
            <div className="flex justify-between font-bold text-gray-900">
              <span>Total</span>
              <span>{formatRupiah(total)}</span>
            </div>
            {paymentType === 'down_payment' && (
              <>
                <div className="flex justify-between text-emerald-700 font-medium">
                  <span>DP ({dpPercent}%)</span>
                  <span>{formatRupiah(dpAmount ?? 0)}</span>
                </div>
                <div className="flex justify-between text-gray-500">
                  <span>Remaining Balance</span>
                  <span>{formatRupiah(remainingAmt)}</span>
                </div>
              </>
            )}
          </div>

          {expiresAt && canPay && (
            <div className="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
              <Clock className="w-4 h-4 flex-shrink-0 mt-0.5" />
              <span>Payment deadline: {formatDate(expiresAt)}</span>
            </div>
          )}
        </div>
      )}

      {/* ── Payment Method Selection ─────────────────────────────────────────── */}
      {canPay && opts && !existingSnapToken && !existingDokuUrl && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
          <h2 className="font-semibold text-gray-800 mb-4">Select Payment Method</h2>

          {/* Payment Type (Full / DP) */}
          {opts.full_payment && opts.down_payment && (
            <div className="mb-5">
              <p className="text-sm text-gray-500 mb-2">Payment Type</p>
              <div className="flex gap-2">
                {opts.full_payment && (
                  <button
                    type="button"
                    onClick={() => setSelectedPayType('full')}
                    className={cn(
                      'flex-1 py-2 rounded-lg border text-sm font-medium transition-colors',
                      selectedPayType === 'full'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                        : 'border-gray-200 text-gray-600 hover:border-gray-300',
                    )}
                  >
                    Full Payment
                  </button>
                )}
                {opts.down_payment && (
                  <button
                    type="button"
                    onClick={() => setSelectedPayType('down_payment')}
                    className={cn(
                      'flex-1 py-2 rounded-lg border text-sm font-medium transition-colors',
                      selectedPayType === 'down_payment'
                        ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                        : 'border-gray-200 text-gray-600 hover:border-gray-300',
                    )}
                  >
                    Down Payment (DP)
                  </button>
                )}
              </div>

              {selectedPayType === 'down_payment' && opts.dp_percentages?.length > 0 && (
                <div className="mt-3">
                  <p className="text-sm text-gray-500 mb-2">DP Percentage</p>
                  <div className="flex gap-2">
                    {opts.dp_percentages.map((pct) => (
                      <button
                        key={pct}
                        type="button"
                        onClick={() => setSelectedDpPercent(pct)}
                        className={cn(
                          'flex-1 py-2 rounded-lg border text-sm font-medium transition-colors',
                          selectedDpPercent === pct
                            ? 'border-emerald-600 bg-emerald-50 text-emerald-700'
                            : 'border-gray-200 text-gray-600 hover:border-gray-300',
                        )}
                      >
                        {pct}%
                        <span className="block text-xs font-normal text-gray-400">
                          {formatRupiah(Math.ceil(total * pct / 100))}
                        </span>
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Gateway Selection */}
          <p className="text-sm text-gray-500 mb-2">Payment Gateway</p>
          <div className="space-y-2">
            {opts.midtrans_enabled && (
              <button
                type="button"
                onClick={() => setSelectedGateway('midtrans')}
                className={cn(
                  'w-full flex items-center gap-3 p-4 rounded-xl border text-left transition-colors',
                  selectedGateway === 'midtrans'
                    ? 'border-emerald-600 bg-emerald-50'
                    : 'border-gray-200 hover:border-gray-300',
                )}
              >
                <CreditCard className={cn('w-5 h-5', selectedGateway === 'midtrans' ? 'text-emerald-600' : 'text-gray-400')} />
                <div>
                  <p className={cn('font-medium text-sm', selectedGateway === 'midtrans' ? 'text-emerald-700' : 'text-gray-800')}>
                    Midtrans
                  </p>
                  <p className="text-xs text-gray-400">Credit/debit card, bank transfer, e-wallet</p>
                </div>
                {selectedGateway === 'midtrans' && (
                  <CheckCircle className="w-4 h-4 text-emerald-600 ml-auto flex-shrink-0" />
                )}
              </button>
            )}

            {opts.doku_enabled && (
              <button
                type="button"
                onClick={() => setSelectedGateway('doku')}
                className={cn(
                  'w-full flex items-center gap-3 p-4 rounded-xl border text-left transition-colors',
                  selectedGateway === 'doku'
                    ? 'border-emerald-600 bg-emerald-50'
                    : 'border-gray-200 hover:border-gray-300',
                )}
              >
                <CreditCard className={cn('w-5 h-5', selectedGateway === 'doku' ? 'text-emerald-600' : 'text-gray-400')} />
                <div>
                  <p className={cn('font-medium text-sm', selectedGateway === 'doku' ? 'text-emerald-700' : 'text-gray-800')}>
                    DOKU
                  </p>
                  <p className="text-xs text-gray-400">Bank transfer, virtual account, e-wallet</p>
                </div>
                {selectedGateway === 'doku' && (
                  <CheckCircle className="w-4 h-4 text-emerald-600 ml-auto flex-shrink-0" />
                )}
              </button>
            )}

            {opts.cash_enabled && (
              <button
                type="button"
                onClick={() => setSelectedGateway('cash')}
                className={cn(
                  'w-full flex items-center gap-3 p-4 rounded-xl border text-left transition-colors',
                  selectedGateway === 'cash'
                    ? 'border-emerald-600 bg-emerald-50'
                    : 'border-gray-200 hover:border-gray-300',
                )}
              >
                <Banknote className={cn('w-5 h-5', selectedGateway === 'cash' ? 'text-emerald-600' : 'text-gray-400')} />
                <div>
                  <p className={cn('font-medium text-sm', selectedGateway === 'cash' ? 'text-emerald-700' : 'text-gray-800')}>
                    Cash / On-site
                  </p>
                  <p className="text-xs text-gray-400">Pay in person at the venue cashier</p>
                </div>
                {selectedGateway === 'cash' && (
                  <CheckCircle className="w-4 h-4 text-emerald-600 ml-auto flex-shrink-0" />
                )}
              </button>
            )}
          </div>

          {/* Cash info */}
          {selectedGateway === 'cash' && (
            <div className="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">
              Please bring your <strong>Order ID #{orderId as string}</strong> and pay at the venue cashier on your visit date. Your booking code will be sent to your email after payment is confirmed.
            </div>
          )}

          {initiate.isError && (
            <p className="text-xs text-red-500 mt-3">
              {(initiate.error as Error)?.message ?? 'Failed to initiate payment. Please try again.'}
            </p>
          )}

          {/* Pay button — not shown for cash */}
          {selectedGateway && selectedGateway !== 'cash' && (
            <button
              type="button"
              disabled={initiate.isPending}
              onClick={() => initiate.mutate()}
              className={cn(
                'mt-4 w-full py-3 rounded-xl text-white font-semibold flex items-center justify-center gap-2 transition-colors',
                initiate.isPending
                  ? 'bg-emerald-400 cursor-not-allowed'
                  : 'bg-emerald-700 hover:bg-emerald-800',
              )}
            >
              {initiate.isPending
                ? <><Loader2 className="w-4 h-4 animate-spin" /> Processing...</>
                : <><ExternalLink className="w-4 h-4" /> Proceed to Payment</>
              }
            </button>
          )}
        </div>
      )}

      {/* ── Existing Pending Midtrans Payment ───────────────────────────────── */}
      {canPay && existingSnapToken && (
        <div className="mb-6">
          <button
            type="button"
            onClick={() => {
              setSnapToken(existingSnapToken)
              setSnapUrl(opts?.midtrans_snap_url ?? null)
            }}
            className="w-full py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 flex items-center justify-center gap-2 transition-colors"
          >
            <ExternalLink className="w-4 h-4" /> Continue Payment (Midtrans)
          </button>
        </div>
      )}

      {/* ── Existing Pending DOKU Payment ───────────────────────────────────── */}
      {canPay && existingDokuUrl && (
        <div className="mb-6">
          <a
            href={existingDokuUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="w-full py-3 bg-emerald-600 text-white font-semibold rounded-xl hover:bg-emerald-700 flex items-center justify-center gap-2 transition-colors"
          >
            <ExternalLink className="w-4 h-4" /> Continue Payment (DOKU)
          </a>
        </div>
      )}

      {/* ── Payment History ──────────────────────────────────────────────────── */}
      {payments && payments.length > 0 && (
        <div className="bg-white rounded-2xl border border-gray-200 p-5 mb-6">
          <h2 className="font-semibold text-gray-800 mb-3">Payment History</h2>
          <div className="space-y-2">
            {payments.map((payment) => (
              <div
                key={payment.id as string}
                className="flex items-center justify-between p-3 rounded-xl bg-gray-50 text-sm"
              >
                <div>
                  <p className="font-medium text-gray-800">
                    {payment.payment_type === 'dp' ? 'Down Payment' :
                     payment.payment_type === 'remaining' ? 'Final Payment' : 'Full Payment'}
                  </p>
                  <p className="text-xs text-gray-400 mt-0.5">
                    via {payment.gateway as string}
                    {payment.paid_at && ` — ${formatDate(payment.paid_at as string)}`}
                  </p>
                </div>
                <div className="text-right">
                  <p className="font-semibold text-gray-800">{formatRupiah(payment.amount as number)}</p>
                  <span className={cn('text-xs', {
                    'text-emerald-600': payment.status === 'success',
                    'text-amber-500':   payment.status === 'pending',
                    'text-red-500':     payment.status === 'failed' || payment.status === 'expired',
                    'text-gray-400':    payment.status === 'refunded',
                  })}>
                    {payment.status === 'success' ? 'Success' :
                     payment.status === 'pending' ? 'Pending' :
                     payment.status === 'failed' ? 'Failed' :
                     payment.status === 'expired' ? 'Expired' : 'Refunded'}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── Actions ─────────────────────────────────────────────────────────── */}
      <div className="flex flex-col gap-3">
        <button
          onClick={() => { setPollingEnabled(false); refetch() }}
          disabled={isRefetching}
          className="w-full py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 flex items-center justify-center gap-2 transition-colors"
        >
          {isRefetching
            ? <Loader2 className="w-4 h-4 animate-spin" />
            : <RefreshCw className="w-4 h-4" />}
          Refresh Status
        </button>
        <Link
          href="/account"
          className="w-full py-2.5 text-center border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors"
        >
          View All Orders
        </Link>
        <Link href="/" className="text-center text-sm text-emerald-600 hover:underline">
          Back to Home
        </Link>
      </div>
    </div>
  )
}
