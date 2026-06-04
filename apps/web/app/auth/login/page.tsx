'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation } from '@tanstack/react-query'
import { useRouter, useSearchParams } from 'next/navigation'
import { api } from '@/lib/api'
import { cn } from 'ui'
import { useAuthStore } from '@/store/auth'
import { Loader2, AlertCircle, Eye, EyeOff } from 'lucide-react'
import { useState } from 'react'

const schema = z.object({
  email: z.string().email('Email tidak valid'),
  password: z.string().min(6, 'Password minimal 6 karakter'),
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
  const redirect = searchParams.get('redirect') ?? '/account'

  const [showPassword, setShowPassword] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const login = useMutation({
    mutationFn: (values: FormValues) =>
      api.post<LoginResponse>('/auth/login', values),
    onSuccess: (res) => {
      auth.setAuth(res.data.user, res.data.token)
      router.push(redirect)
    },
  })

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm">
        {/* Logo / Brand */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-emerald-700 mb-1">Amartha eTicket</h1>
          <p className="text-gray-500 text-sm">Masuk untuk melanjutkan</p>
        </div>

        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-gray-900 mb-5">Masuk ke Akun</h2>

          <form onSubmit={handleSubmit((v) => login.mutate(v))} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Email
              </label>
              <input
                {...register('email')}
                type="email"
                placeholder="email@contoh.com"
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
                  placeholder="Masukkan password"
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

            {login.isError && (
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
                  <Loader2 className="w-4 h-4 animate-spin" /> Masuk...
                </>
              ) : (
                'Masuk'
              )}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-5">
            Belum punya akun?{' '}
            <a
              href={`/auth/register${redirect !== '/account' ? `?redirect=${redirect}` : ''}`}
              className="font-medium text-emerald-700 hover:underline"
            >
              Daftar sekarang
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}
