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

const schema = z
  .object({
    name: z.string().min(2, 'Nama minimal 2 karakter'),
    email: z.string().email('Email tidak valid'),
    phone: z
      .string()
      .min(9, 'Nomor telepon minimal 9 digit')
      .regex(/^[0-9+\-\s]+$/, 'Format nomor telepon tidak valid'),
    password: z.string().min(8, 'Password minimal 8 karakter'),
    passwordConfirm: z.string(),
  })
  .refine((d) => d.password === d.passwordConfirm, {
    message: 'Password tidak sama',
    path: ['passwordConfirm'],
  })

type FormValues = z.infer<typeof schema>

type RegisterResponse = {
  data: {
    token: string
    user: { id: string; name: string; email: string; role: 'customer' }
  }
}

export default function RegisterPage() {
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

  const registerMutation = useMutation({
    mutationFn: (values: FormValues) =>
      api.postSnake<RegisterResponse>('/auth/register', {
        name: values.name,
        email: values.email,
        phone: values.phone,
        password: values.password,
      }),
    onSuccess: (res) => {
      auth.setAuth(res.data.user, res.data.token)
      router.push(redirect)
    },
  })

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm">
        {/* Brand */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-emerald-700 mb-1">Amartha eTicket</h1>
          <p className="text-gray-500 text-sm">Buat akun baru</p>
        </div>

        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-gray-900 mb-5">Daftar Akun</h2>

          <form
            onSubmit={handleSubmit((v) => registerMutation.mutate(v))}
            className="space-y-4"
          >
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Nama Lengkap
              </label>
              <input
                {...register('name')}
                type="text"
                placeholder="Nama lengkap kamu"
                autoComplete="name"
                className={cn(
                  'w-full px-3 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                  errors.name ? 'border-red-400' : 'border-gray-200',
                )}
              />
              {errors.name && (
                <p className="text-xs text-red-500 mt-1">{errors.name.message}</p>
              )}
            </div>

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
                Nomor WhatsApp
              </label>
              <input
                {...register('phone')}
                type="tel"
                placeholder="08xxxxxxxxxx"
                autoComplete="tel"
                className={cn(
                  'w-full px-3 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                  errors.phone ? 'border-red-400' : 'border-gray-200',
                )}
              />
              {errors.phone && (
                <p className="text-xs text-red-500 mt-1">{errors.phone.message}</p>
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
                  placeholder="Min. 8 karakter"
                  autoComplete="new-password"
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

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Konfirmasi Password
              </label>
              <input
                {...register('passwordConfirm')}
                type={showPassword ? 'text' : 'password'}
                placeholder="Ulangi password"
                autoComplete="new-password"
                className={cn(
                  'w-full px-3 py-2.5 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500',
                  errors.passwordConfirm ? 'border-red-400' : 'border-gray-200',
                )}
              />
              {errors.passwordConfirm && (
                <p className="text-xs text-red-500 mt-1">{errors.passwordConfirm.message}</p>
              )}
            </div>

            {registerMutation.isError && (
              <div className="flex items-start gap-2 p-3 bg-red-50 border border-red-200 rounded-xl text-sm text-red-600">
                <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                <span>
                  {(registerMutation.error as Error)?.message ??
                    'Gagal mendaftar. Coba dengan email lain.'}
                </span>
              </div>
            )}

            <button
              type="submit"
              disabled={registerMutation.isPending}
              className={cn(
                'w-full py-3 rounded-xl font-semibold text-white flex items-center justify-center gap-2 transition-all mt-2',
                registerMutation.isPending
                  ? 'bg-emerald-400 cursor-not-allowed'
                  : 'bg-emerald-600 hover:bg-emerald-700',
              )}
            >
              {registerMutation.isPending ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" /> Mendaftar...
                </>
              ) : (
                'Daftar'
              )}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-5">
            Sudah punya akun?{' '}
            <a
              href={`/auth/login${redirect !== '/account' ? `?redirect=${redirect}` : ''}`}
              className="font-medium text-emerald-700 hover:underline"
            >
              Masuk di sini
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}
