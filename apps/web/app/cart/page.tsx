'use client'

import { useRouter } from 'next/navigation'
import Link from 'next/link'
import { formatRupiah } from 'ui'
import { useCartStore } from '@/store/cart'
import { Trash2 } from 'lucide-react'

export default function CartPage() {
  const router = useRouter()
  const cart = useCartStore()

  const subtotal = cart.subtotal()
  const total = cart.total()

  if (cart.tickets.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center text-gray-500">
        <p className="text-lg font-medium">Keranjang kamu kosong.</p>
        <Link href="/products" className="text-emerald-600 hover:underline text-sm mt-2 inline-block">
          Lihat produk wisata
        </Link>
      </div>
    )
  }

  const visitDate = cart.selectedDate
    ? new Date(cart.selectedDate + 'T00:00:00').toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric',
      })
    : null

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Keranjang Saya</h1>
        <Link href="/" className="text-sm text-gray-500 border border-gray-300 px-4 py-1.5 rounded-lg hover:bg-gray-50 transition-colors">
          Kembali Ke Beranda
        </Link>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Cart items */}
        <div className="lg:col-span-2 space-y-4">
          {cart.tickets.map(ticket => {
            const ticketTotal = ticket.qtyAdult * ticket.unitPriceAdult + ticket.qtyChild * ticket.unitPriceChild
            return (
              <div key={ticket.variantId} className="bg-white border border-gray-200 rounded-xl p-5">
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-gray-800 text-base leading-snug">
                      {ticket.variantLabel}
                    </p>
                    <p className="text-sm text-gray-500 mt-1 break-all">{ticket.variantLabel}</p>

                    <div className="mt-3 space-y-1 text-sm text-gray-600">
                      <p>
                        <span className="font-medium">Kuantitas:</span>
                      </p>
                      {ticket.qtyAdult > 0 && (
                        <p>{ticket.qtyAdult} X ADULT</p>
                      )}
                      {ticket.qtyChild > 0 && (
                        <p>{ticket.qtyChild} X CHILD</p>
                      )}
                      {visitDate && (
                        <p>
                          <span className="font-medium">Tanggal Kunjungan:</span>
                          <br />{visitDate}
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                  <button
                    onClick={() => cart.removeTicket(ticket.variantId)}
                    className="text-gray-400 hover:text-red-500 transition-colors"
                    title="Hapus dari keranjang"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                  <span className="font-semibold text-gray-800">{formatRupiah(ticketTotal)}</span>
                </div>
              </div>
            )
          })}
        </div>

        {/* Summary */}
        <div className="lg:col-span-1">
          <div className="bg-white border border-gray-200 rounded-xl p-5 sticky top-20">
            <h2 className="font-semibold text-gray-800 mb-4">Ringkasan</h2>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between text-gray-600">
                <span>Sub Total ({cart.tickets.length} barang)</span>
                <span>{formatRupiah(subtotal)}</span>
              </div>
              {cart.voucher && (
                <div className="flex justify-between text-emerald-600">
                  <span>Diskon</span>
                  <span>-{formatRupiah(cart.voucher.discount)}</span>
                </div>
              )}
              <div className="flex justify-between font-bold text-gray-900 text-base pt-2 border-t border-gray-100">
                <span>Total</span>
                <span className="text-emerald-700">{formatRupiah(total)}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom actions */}
      <div className="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
        <button
          onClick={() => router.back()}
          className="px-6 py-2.5 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Kembali
        </button>
        <button
          onClick={() => router.push('/checkout')}
          className="px-8 py-2.5 rounded-lg bg-emerald-700 text-white text-sm font-semibold hover:bg-emerald-800 transition-colors"
        >
          Langkah Berikutnya
        </button>
      </div>
    </div>
  )
}
