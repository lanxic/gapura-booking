'use client'

import { useSearchParams } from 'next/navigation'
import Link from 'next/link'
import { CheckCircle, Copy, Mail, MessageCircle, Home, CreditCard } from 'lucide-react'
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

      <h1 className="text-2xl font-bold text-gray-900 mb-2">Booking Confirmed!</h1>
      <p className="text-gray-500 mb-8">
        Your order has been received. Our team will process your payment and
        send a confirmation to your email shortly.
      </p>

      {/* Booking Code */}
      <div className="bg-white rounded-2xl border-2 border-emerald-200 p-6 mb-8">
        <p className="text-sm text-gray-500 mb-2">Booking Code</p>
        <div className="flex items-center justify-center gap-3">
          <span className="text-3xl font-mono font-bold text-emerald-700 tracking-widest">
            {bookingCode}
          </span>
          <button
            onClick={handleCopy}
            className="p-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-400 hover:text-gray-600"
            title="Copy code"
          >
            <Copy className="w-5 h-5" />
          </button>
        </div>
        {copied && (
          <p className="text-xs text-emerald-600 mt-2">Copied!</p>
        )}
        <p className="text-xs text-gray-400 mt-3">
          Save this code for your booking reference
        </p>
      </div>

      {/* Instructions */}
      <div className="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-left mb-8">
        <h2 className="font-semibold text-amber-800 mb-3">Next Steps</h2>
        <ol className="space-y-3 text-sm text-amber-700">
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              1
            </span>
            <span>
              Check your email for payment instructions. Make sure to complete
              your payment before the deadline.
            </span>
          </li>
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              2
            </span>
            <span>
              Once payment is confirmed, a digital ticket with a QR code will
              be sent to your email.
            </span>
          </li>
          <li className="flex gap-3">
            <span className="w-5 h-5 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
              3
            </span>
            <span>
              Show your QR code ticket when checking in at the venue.
            </span>
          </li>
        </ol>
      </div>

      {/* Pay Now CTA */}
      <Link
        href={`/payment?code=${bookingCode}`}
        className="flex items-center justify-center gap-2 w-full py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-xl transition-colors mb-4"
      >
        <CreditCard className="w-4 h-4" />
        Complete Payment Now
      </Link>

      {/* Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <a
          href={`mailto:?subject=Booking Code ${bookingCode}&body=My booking code: ${bookingCode}`}
          className="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-gray-200 hover:border-emerald-300 transition-colors text-sm text-gray-600 hover:text-emerald-700"
        >
          <Mail className="w-5 h-5" />
          <span>Send Email</span>
        </a>
        <a
          href={`https://wa.me/?text=My booking code: ${bookingCode}`}
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
          <span>My Orders</span>
        </Link>
      </div>

      <Link
        href="/"
        className="text-sm text-emerald-600 hover:underline"
      >
        Back to Home
      </Link>
    </div>
  )
}
