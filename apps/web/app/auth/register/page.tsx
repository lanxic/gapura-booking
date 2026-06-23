'use client'

import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useMutation } from '@tanstack/react-query'
import { useRouter, useSearchParams } from 'next/navigation'
import { api } from '@/lib/api'
import { cn } from 'ui'
import { Loader2, AlertCircle, Eye, EyeOff, MailCheck } from 'lucide-react'
import { useState } from 'react'

const schema = z
  .object({
    name: z.string().min(2, 'Name must be at least 2 characters'),
    email: z.string().email('Invalid email address'),
    phone: z
      .string()
      .min(9, 'Phone number must be at least 9 digits')
      .regex(/^[0-9+\-\s]+$/, 'Invalid phone number format'),
    password: z.string().min(8, 'Password must be at least 8 characters'),
    passwordConfirm: z.string(),
  })
  .refine((d) => d.password === d.passwordConfirm, {
    message: 'Passwords do not match',
    path: ['passwordConfirm'],
  })

type FormValues = z.infer<typeof schema>

type RegisterResponse = {
  message: string
}

export default function RegisterPage() {
  const searchParams = useSearchParams()
  const redirect = searchParams.get('redirect') ?? '/account'

  const [showPassword, setShowPassword] = useState(false)
  const [registeredEmail, setRegisteredEmail] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    getValues,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  const registerMutation = useMutation({
    mutationFn: (values: FormValues) =>
      api.postSnake<RegisterResponse>('/auth/customer/register', {
        name: values.name,
        email: values.email,
        phone: values.phone,
        password: values.password,
        passwordConfirmation: values.passwordConfirm,
      }),
    onSuccess: () => {
      setRegisteredEmail(getValues('email'))
    },
  })

  const resendMutation = useMutation({
    mutationFn: (email: string) =>
      api.postSnake('/auth/customer/resend-verification', { email }),
  })

  // ── Layar sukses: cek email ──────────────────────────────────────────────────
  if (registeredEmail) {
    return (
      <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div className="w-full max-w-sm text-center">
          <div className="flex items-center justify-center mb-6">
            <div className="w-20 h-20 rounded-full bg-emerald-50 flex items-center justify-center">
              <MailCheck className="w-10 h-10 text-emerald-600" />
            </div>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Cek Email Anda</h1>
          <p className="text-gray-500 text-sm mb-1">
            Link verifikasi telah dikirim ke:
          </p>
          <p className="text-emerald-700 font-semibold text-sm mb-6">{registeredEmail}</p>
          <p className="text-gray-500 text-sm mb-8">
            Klik link di email untuk mengaktifkan akun Anda. Link berlaku selama <strong>24 jam</strong>.
          </p>

          {resendMutation.isSuccess ? (
            <p className="text-sm text-emerald-600 font-medium mb-4">
              Email verifikasi baru telah dikirim.
            </p>
          ) : (
            <button
              onClick={() => resendMutation.mutate(registeredEmail)}
              disabled={resendMutation.isPending}
              className="text-sm text-gray-500 hover:text-emerald-700 transition-colors disabled:opacity-50"
            >
              {resendMutation.isPending ? 'Mengirim...' : 'Tidak menerima email? Kirim ulang'}
            </button>
          )}

          <div className="mt-6">
            <a
              href={`/auth/login${redirect !== '/account' ? `?redirect=${redirect}` : ''}`}
              className="text-sm font-medium text-emerald-700 hover:underline"
            >
              Kembali ke halaman login
            </a>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-[70vh] flex items-center justify-center px-4 py-12">
      <div className="w-full max-w-sm">
        {/* Brand */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-emerald-700 mb-1">Amartha eTicket</h1>
          <p className="text-gray-500 text-sm">Create a new account</p>
        </div>

        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-semibold text-gray-900 mb-5">Create Account</h2>

          <form
            onSubmit={handleSubmit((v) => registerMutation.mutate(v))}
            className="space-y-4"
          >
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Full Name
              </label>
              <input
                {...register('name')}
                type="text"
                placeholder="Your full name"
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
                WhatsApp Number
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
                  placeholder="Min. 8 characters"
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
                Confirm Password
              </label>
              <input
                {...register('passwordConfirm')}
                type={showPassword ? 'text' : 'password'}
                placeholder="Repeat password"
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
                    'Registration failed. Please try a different email.'}
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
                  <Loader2 className="w-4 h-4 animate-spin" /> Creating account...
                </>
              ) : (
                'Create Account'
              )}
            </button>
          </form>

          <p className="text-center text-sm text-gray-500 mt-5">
            Already have an account?{' '}
            <a
              href={`/auth/login${redirect !== '/account' ? `?redirect=${redirect}` : ''}`}
              className="font-medium text-emerald-700 hover:underline"
            >
              Sign in here
            </a>
          </p>
        </div>
      </div>
    </div>
  )
}
