'use client'

import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { CheckCircle, Copy, Mail, MessageCircle, Home } from 'lucide-react'
import { useState } from 'react'

export default function BookingSuccessPage() {
  const searchParams = useSearchParams()
  const bookingCode = searchParams.get('code') ?? '-'

  const [copied, setCopied] = useState(false)

  function handleCopy() {
    navigator.clipboard.writeText(bookingCode).then(() => {
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    })
  }

  return (
    <div className="max-w-lg mx-auto px-4 py-16 text-center">
      {/* Success Icon */}
      <div className="flex justify-center mb-6">
        <div className="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center">
          <CheckCircle className="w-10 h-10 text-emerald-600" />
        </div>
      </div>

      <h1 className="text-2xl font-bold text-gray-900 mb-2">Pemesanan Berhasil!</h1>
      <p className="text-gray-500 mb-8">
        Pesanan kamu telah diterima. Tim kami akan segera memproses pembayaran dan
        mengirimkan konfirmasi ke email kamu.
      </p>

      {/* Booking Code */}
      <div className="bg-white rounded-2xl border-2 border-emerald-200 p-6 mb-8">
        <p className="text-sm text-gray-500 mb-2">Kode Booking</p>
        <div className="flex items-center justify-center gap-3">
          <span className="text-3xl font-mono font-bold text-emerald-700 tracking-widest">
            {bookingCode}
          </span>
          <button
            onClick={handleCopy}
            className="p-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600"
            title="Salin kode"
          >
            <Copy className="w-5 h-5" />
          </button>
        </div>
        {copied && (
          <p className="text-xs text-emerald-600 mt-2">Kode berhasil disalin!</p>
        )}
        <p className="text-xs text-gray-400 mt-3">
          Simpan kode ini untuk referensi pesanan kamu
        </p>
      </div>

      {/* Instructions */}
      <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-left mb-8">
        <h2 className="font-semibold text-amber-800 mb-3">Langkah Selanjutnya</h2>
        <ol className="space-y-3 text-sm text-amber-700">
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              1
            </span>
            <span>
              Cek email kamu untuk instruksi pembayaran. Pastikan kamu membayar sebelum
              waktu yang tertera.
            </span>
          </li>
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              2
            </span>
            <span>
              Setelah pembayaran dikonfirmasi, tiket digital dengan QR code akan dikirim
              ke email kamu.
            </span>
          </li>
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              3
            </span>
            <span>
              Tunjukkan QR code tiket saat check-in di lokasi wisata.
            </span>
          </li>
        </ol>
      </div>

      {/* Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <a
          href={`mailto:?subject=Kode Booking ${bookingCode}&body=Kode booking saya: ${bookingCode}`}
          className="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 transition-colors text-sm text-gray-600 hover:text-emerald-700"
        >
          <Mail className="w-5 h-5" />
          <span>Kirim Email</span>
        </a>
        <a
          href={`https://wa.me/?text=Kode booking saya: ${bookingCode}`}
          target="_blank"
          rel="noopener noreferrer"
          className="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 transition-colors text-sm text-gray-600 hover:text-emerald-700"
        >
          <MessageCircle className="w-5 h-5" />
          <span>WhatsApp</span>
        </a>
        <Link
          href="/account"
          className="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 transition-colors text-sm text-gray-600 hover:text-emerald-700"
        >
          <Home className="w-5 h-5" />
          <span>Lihat Pesanan</span>
        </Link>
      </div>

      <Link
        href="/"
        className="text-sm text-emerald-600 hover:underline"
      >
        Kembali ke Beranda
      </Link>
    </div>
  )
}
