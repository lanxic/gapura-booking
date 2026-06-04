'use client'

import { useAdminAuthStore } from '@/store/auth'
import { api } from '@/lib/api'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { Scale, Save, Loader2, CheckCircle, AlertCircle } from 'lucide-react'
import { useState, useEffect } from 'react'

export default function SettingsLegalPage() {
  const token       = useAdminAuthStore(s => s.token)!
  const queryClient = useQueryClient()

  const [form, setForm] = useState({ privacy_policy: '', terms_of_service: '' })
  const [saved, setSaved] = useState(false)

  const { data, isLoading } = useQuery({
    queryKey: ['settings-legal'],
    queryFn: () => api.get<any>('/admin/settings/legal', { token }),
    enabled: !!token,
  })

  useEffect(() => {
    if (data?.data) {
      setForm({
        privacy_policy:   data.data.privacy_policy   ?? '',
        terms_of_service: data.data.terms_of_service ?? '',
      })
    }
  }, [data])

  const mutation = useMutation({
    mutationFn: (payload: typeof form) =>
      api.put<any>('/admin/settings/legal', payload, { token }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings-legal'] })
      setSaved(true)
      setTimeout(() => setSaved(false), 3000)
    },
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    setSaved(false)
    mutation.mutate(form)
  }

  return (
    <div className="space-y-6 max-w-3xl">
      <div className="flex items-center gap-3">
        <Scale size={22} className="text-muted-foreground" />
        <h1 className="text-2xl font-bold text-foreground">Legal</h1>
      </div>

      {isLoading && <div className="flex justify-center py-10"><Loader2 className="animate-spin text-muted-foreground" /></div>}
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="rounded-xl border border-border bg-card p-6 space-y-5">
          <div>
            <label className="block text-sm font-medium text-foreground mb-0.5">
              Kebijakan Privasi
            </label>
            <p className="text-xs text-muted-foreground mb-2">
              Isi kebijakan privasi yang ditampilkan kepada pengguna
            </p>
            <textarea
              value={form.privacy_policy}
              onChange={e => setForm(prev => ({ ...prev, privacy_policy: e.target.value }))}
              rows={10}
              placeholder="Tulis kebijakan privasi di sini..."
              className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition resize-none font-mono"
            />
          </div>

          <div className="h-px bg-border" />

          <div>
            <label className="block text-sm font-medium text-foreground mb-0.5">
              Syarat dan Ketentuan
            </label>
            <p className="text-xs text-muted-foreground mb-2">
              Isi syarat dan ketentuan penggunaan platform
            </p>
            <textarea
              value={form.terms_of_service}
              onChange={e => setForm(prev => ({ ...prev, terms_of_service: e.target.value }))}
              rows={10}
              placeholder="Tulis syarat dan ketentuan di sini..."
              className="w-full rounded-lg border border-input bg-background px-3 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring transition resize-none font-mono"
            />
          </div>
        </div>

        <div className="flex items-center gap-4">
          <button
            type="submit"
            disabled={mutation.isPending}
            className="flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-primary-foreground text-sm font-semibold hover:bg-primary/90 disabled:opacity-50 transition-colors"
          >
            {mutation.isPending ? <Loader2 size={15} className="animate-spin" /> : <Save size={15} />}
            Simpan
          </button>
          {saved && (
            <span className="flex items-center gap-1.5 text-sm text-emerald-600">
              <CheckCircle size={14} /> Tersimpan
            </span>
          )}
          {mutation.isError && (
            <span className="flex items-center gap-1.5 text-sm text-destructive">
              <AlertCircle size={14} /> {(mutation.error as Error)?.message ?? 'Gagal menyimpan'}
            </span>
          )}
        </div>
      </form>
    </div>
  )
}
