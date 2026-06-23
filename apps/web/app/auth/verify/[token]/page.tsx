'use client'

import { useEffect, useState } from 'react'
import { useParams } from 'next/navigation'
import { CheckCircle, XCircle, Loader2 } from 'lucide-react'

type State = 'loading' | 'success' | 'invalid' | 'already'

export default function VerifyEmailPage() {
  const { token } = useParams<{ token: string }>()
  const [state, setState] = useState<State>('loading')

  useEffect(() => {
    if (!token) { setState('invalid'); return }

    const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8000/v1'

    // This endpoint returns a redirect — we catch the final URL via response.url
    fetch(`${apiUrl}/auth/customer/verify/${token}`, { redirect: 'follow' })
      .then(res => {
        const url = new URL(res.url)
        const verified = url.searchParams.get('verified')
        if (verified === '1')        setState('success')
        else if (verified === 'already') setState('already')
        else                         setState('invalid')
      })
      .catch(() => setState('invalid'))
  }, [token])

  const configs = {
    loading: {
      icon: <Loader2 className="w-12 h-12 text-emerald-600 animate-spin" />,
      title: 'Memverifikasi...',
      body: 'Harap tunggu sebentar.',
      bg: 'bg-emerald-50',
    },
    success: {
      icon: <CheckCircle className="w-12 h-12 text-emerald-600" />,
      title: 'Email Berhasil Diverifikasi!',
      body: 'Akun Anda telah aktif. Silakan login untuk mulai memesan aktivitas.',
      bg: 'bg-emerald-50',
    },
    already: {
      icon: <CheckCircle className="w-12 h-12 text-blue-500" />,
      title: 'Email Sudah Terverifikasi',
      body: 'Akun Anda sudah aktif sebelumnya. Silakan login.',
      bg: 'bg-blue-50',
    },
    invalid: {
      icon: <XCircle className="w-12 h-12 text-red-500" />,
      title: 'Link Tidak Valid atau Kadaluarsa',
      body: 'Link verifikasi tidak ditemukan atau sudah kadaluarsa (24 jam). Silakan daftar ulang atau minta kirim ulang verifikasi.',
      bg: 'bg-red-50',
    },
  }

  const c = configs[state]

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm text-center">
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-emerald-700 mb-1">Amartha eTicket</h1>
        </div>

        <div className={`${c.bg} rounded-2xl p-8 mb-6`}>
          <div className="flex justify-center mb-4">{c.icon}</div>
          <h2 className="text-xl font-bold text-gray-900 mb-2">{c.title}</h2>
          <p className="text-gray-500 text-sm leading-relaxed">{c.body}</p>
        </div>

        {state !== 'loading' && (
          <div className="space-y-3">
            <a
              href="/auth/login"
              className="block w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm transition-colors"
            >
              Masuk ke Akun
            </a>
            {state === 'invalid' && (
              <a
                href="/auth/register"
                className="block w-full py-3 rounded-xl border border-gray-200 text-gray-700 font-medium text-sm hover:bg-gray-50 transition-colors"
              >
                Daftar Ulang
              </a>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
