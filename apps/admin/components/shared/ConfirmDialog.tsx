'use client'

import { useEffect } from 'react'
import { AlertTriangle, Loader2 } from 'lucide-react'

interface ConfirmDialogProps {
  open: boolean
  title: string
  description: string
  confirmLabel?: string
  cancelLabel?: string
  loading?: boolean
  variant?: 'danger' | 'warning'
  onConfirm: () => void
  onClose: () => void
}

export function ConfirmDialog({
  open,
  title,
  description,
  confirmLabel = 'Hapus',
  cancelLabel = 'Batal',
  loading = false,
  variant = 'danger',
  onConfirm,
  onClose,
}: ConfirmDialogProps) {
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape' && !loading) onClose() }
    document.addEventListener('keydown', onKey)
    return () => document.removeEventListener('keydown', onKey)
  }, [open, loading, onClose])

  if (!open) return null

  const confirmCls = variant === 'danger'
    ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
    : 'bg-amber-500 text-white hover:bg-amber-600'

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
      onClick={e => { if (e.target === e.currentTarget && !loading) onClose() }}
    >
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" />

      <div className="relative z-10 w-full max-w-sm bg-card border border-border rounded-2xl shadow-2xl p-6 space-y-4">
        <div className="flex items-start gap-3">
          <div className={`shrink-0 p-2 rounded-full ${variant === 'danger' ? 'bg-destructive/10' : 'bg-amber-100'}`}>
            <AlertTriangle size={18} className={variant === 'danger' ? 'text-destructive' : 'text-amber-600'} />
          </div>
          <div className="space-y-1 min-w-0">
            <h2 className="text-base font-semibold text-foreground">{title}</h2>
            <p className="text-sm text-muted-foreground leading-relaxed">{description}</p>
          </div>
        </div>

        <div className="flex justify-end gap-2 pt-1">
          <button
            onClick={onClose}
            disabled={loading}
            className="px-4 py-2 rounded-lg text-sm font-medium border border-border text-foreground hover:bg-muted disabled:opacity-50 transition-colors"
          >
            {cancelLabel}
          </button>
          <button
            onClick={onConfirm}
            disabled={loading}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50 transition-colors ${confirmCls}`}
          >
            {loading && <Loader2 size={14} className="animate-spin" />}
            {confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
