'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation } from '@tanstack/react-query'
import { useRouter, useSearchParams } from 'next/navigation'
import { api } from '@/lib/api'
import { cn } from 'ui'
import { useAuthStore } from '@/store/auth'
import { Loader2, AlertCircle, Eye, EyeOff, MailCheck, CheckCircle } from 'lucide-react'
import { useState } from 'react'

const schema = z.object({
  email: z.string().email('Invalid email address'),
  password: z.string().min(6, 'Password must be at least 6 characters'),
})

type FormValues = z.infer<typeof schema>

type LoginResponse = {
  data: {
    token: string
    user: { id: string; name: string; email: string; role: 'customer' }
  }
}

export default function LoginPage() {
  const router = useRouter()
  const searchParams = useSearchParams()
  const auth = useAuthStore()
  const redirect    = searchParams.get('redirect') ?? '/account'
  const justVerified = searchParams.get('verified') === '1'

  const [showPassword, setShowPassword]         = useState(false)
  const [emailNotVerified, setEmailNotVerified] = useState(false)
  const [lastEmail, setLastEmail]               = useState('')

  const {
    register,
    handleSubmit,
    getValues,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const login = useMutation({
    mutationFn: (values: FormValues) =>
      api.postSnake<LoginResponse>('/auth/customer/login', values),
    onSuccess: (res) => {
      auth.setAuth(res.data.user, res.data.token)
      router.push(redirect)
    },
    onError: (err: any) => {
      if (err?.code === 'EMAIL_NOT_VERIFIED') {
        setEmailNotVerified(true)
        setLastEmail(getValues('email'))
      } else {
        setEmailNotVerified(false)
      }
    },
  })

  const resendMutation = useMutation({
    mutationFn: (email: string) =>
      api.postSnake('/auth/customer/resend-verification', { email }),
  })

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm">
        {/* Logo / Brand */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-emerald-700 mb-1">Amartha eTicket</h1>
          <p className="text-gray-500 text-sm">Sign in to continue</p>
        </div>

        {/* Banner email baru saja diverifikasi */}
        {justVerified && (
          <div className="flex items-start gap-2 p-3 mb-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
            <CheckCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
            <span>Email berhasil diverifikasi! Silakan masuk.</span>
          </div>
        )}

        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-gray-900 mb-5">Sign In</h2>

          <form onSubmit={handleSubmit((v) => { setEmailNotVerified(false); login.mutate(v) })} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Email
              </label>
              <input
                {...register('email')}
                type="email"
                placeholder="email@example.com"
                autoComplete="email"
                className={cn(
                  'w-full px-3 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                  errors.email ? 'border-red-400' : 'border-gray-200',
                )}
              />
              {errors.email && (
                <p className="text-xs text-red-500 mt-1">{errors.email.message}</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Password
              </label>
              <div className="relative">
                <input
                  {...register('password')}
                  type={showPassword ? 'text' : 'password'}
                  placeholder="Enter your password"
                  autoComplete="current-password"
                  className={cn(
                    'w-full px-3 py-2.5 pr-10 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                    errors.password ? 'border-red-400' : 'border-gray-200',
                  )}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
              {errors.password && (
                <p className="text-xs text-red-500 mt-1">{errors.password.message}</p>
              )}
            </div>

            {/* Error: email belum verifikasi */}
            {emailNotVerified && (
              <div className="p-3 bg-amber-50 border border-amber-200 rounded-xl text-sm">
                <div className="flex items-start gap-2 text-amber-700 mb-2">
                  <MailCheck className="w-4 h-4 flex-shrink-0 mt-0.5" />
                  <span>
                    Email Anda belum diverifikasi. Silakan cek kotak masuk
                    {lastEmail ? <> email <strong>{lastEmail}</strong></> : ' email Anda'}.
                  </span>
                </div>
                {resendMutation.isSuccess ? (
                  <p className="text-xs text-emerald-600 font-medium pl-6">Email verifikasi baru telah dikirim.</p>
                ) : (
                  <button
                    type="button"
                    onClick={() => resendMutation.mutate(lastEmail)}
                    disabled={resendMutation.isPending}
                    className="text-xs font-medium text-amber-700 underline pl-6 disabled:opacity-50"
                  >
                    {resendMutation.isPending ? 'Mengirim...' : 'Kirim ulang email verifikasi'}
                  </button>
                )}
              </div>
            )}

            {/* Error umum */}
            {login.isError && !emailNotVerified && (
              <div className="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
                <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>
                  {(login.error as Error)?.message ?? 'Email atau password salah.'}
                </span>
              </div>
            )}

            <button
              type="submit"
              disabled={login.isPending}
              className={cn(
                'w-full py-3 rounded-xl font-semibold text-white flex items-center justify-center gap-2 transition-all mt-2',
                login.isPending
                  ? 'bg-emerald-400 cursor-not-allowed'
                  : 'bg-emerald-600 hover:bg-emerald-700',
              )}
            >
              {login.isPending ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" /> Signing in...
                </>
              ) : (
                'Sign In'
              )}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-5">
            Don&apos;t have an account?{' '}
            <a
              href={`/auth/register${redirect !== '/account' ? `?redirect=${redirect}` : ''}`}
              className="font-medium text-emerald-700 hover:underline"
            >
              Register now
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}
