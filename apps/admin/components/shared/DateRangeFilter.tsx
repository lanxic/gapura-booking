'use client'

import { useRef, useState } from 'react'
import { CalendarDays } from 'lucide-react'
import { cn } from '@/lib/utils'

// ── helpers ────────────────────────────────────────────────────────────────

export const fmtIso = (d: Date) => d.toISOString().slice(0, 10)

export type Preset = 'week' | 'month' | 'this_month' | 'custom'

export function rangeForPreset(preset: Exclude<Preset, 'custom'>) {
  const today = new Date()
  if (preset === 'week') {
    const from = new Date(today); from.setDate(today.getDate() - 6)
    return { from: fmtIso(from), to: fmtIso(today) }
  }
  if (preset === 'month') {
    const from = new Date(today); from.setDate(today.getDate() - 29)
    return { from: fmtIso(from), to: fmtIso(today) }
  }
  return { from: fmtIso(new Date(today.getFullYear(), today.getMonth(), 1)), to: fmtIso(today) }
}

// ── DateInput ──────────────────────────────────────────────────────────────

export function DateInput({ value, onChange, min, max }: {
  value: string; onChange: (v: string) => void; min?: string; max?: string
}) {
  const ref = useRef<HTMLInputElement>(null)
  const display = value ? value.split('-').reverse().join('/') : ''

  return (
    <div
      className="relative flex items-center gap-2 px-3 py-1.5 rounded-lg border border-input bg-background cursor-pointer hover:border-ring transition-colors"
      onClick={() => ref.current?.showPicker?.()}
    >
      <CalendarDays size={14} className="text-muted-foreground shrink-0" />
      <span className={cn('text-sm w-24 select-none', display ? 'text-foreground' : 'text-muted-foreground')}>
        {display || 'dd/mm/yyyy'}
      </span>
      <input
        ref={ref}
        type="date"
        value={value}
        min={min}
        max={max}
        onChange={e => onChange(e.target.value)}
        className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
        tabIndex={-1}
      />
    </div>
  )
}

// ── DateRangeFilter ────────────────────────────────────────────────────────

const PRESETS: { id: Preset; label: string }[] = [
  { id: 'week',       label: '7 Hari' },
  { id: 'month',      label: '30 Hari' },
  { id: 'this_month', label: 'Bulan Ini' },
  { id: 'custom',     label: 'Custom' },
]

export function DateRangeFilter({
  defaultPreset = 'this_month',
  onChange,
}: {
  defaultPreset?: Preset
  onChange: (range: { from: string; to: string }) => void
}) {
  const initRange = defaultPreset !== 'custom'
    ? rangeForPreset(defaultPreset as Exclude<Preset, 'custom'>)
    : { from: fmtIso(new Date()), to: fmtIso(new Date()) }

  const [preset, setPreset] = useState<Preset>(defaultPreset)
  const [from,   setFrom]   = useState(initRange.from)
  const [to,     setTo]     = useState(initRange.to)

  const handlePreset = (p: Preset) => {
    setPreset(p)
    if (p !== 'custom') {
      const range = rangeForPreset(p as Exclude<Preset, 'custom'>)
      setFrom(range.from)
      setTo(range.to)
      onChange(range)
    }
  }

  return (
    <div className="rounded-xl border border-border bg-card px-4 py-3 flex flex-wrap items-center gap-3">
      {/* Preset chips */}
      <div className="flex gap-1.5">
        {PRESETS.map(p => (
          <button
            key={p.id}
            type="button"
            onClick={() => handlePreset(p.id)}
            className={cn(
              'px-3 py-1.5 rounded-lg text-sm font-medium transition-colors',
              preset === p.id
                ? 'bg-primary text-primary-foreground'
                : 'border border-border text-muted-foreground hover:bg-muted/50 hover:text-foreground',
            )}
          >
            {p.label}
          </button>
        ))}
      </div>

      {/* Custom pickers */}
      {preset === 'custom' && (
        <>
          <div className="w-px h-5 bg-border" />
          <div className="flex items-center gap-2 flex-wrap">
            <DateInput value={from} onChange={setFrom} max={to} />
            <span className="text-sm text-muted-foreground">—</span>
            <DateInput value={to} onChange={setTo} min={from} />
            <button
              type="button"
              onClick={() => onChange({ from, to })}
              className="px-3 py-1.5 rounded-lg bg-primary text-primary-foreground text-sm font-medium hover:bg-primary/90 transition-colors"
            >
              Terapkan
            </button>
          </div>
        </>
      )}

      {/* Active range label */}
      {preset !== 'custom' && (
        <span className="text-xs text-muted-foreground ml-auto">
          {new Date(from).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
          {' – '}
          {new Date(to).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}
        </span>
      )}
    </div>
  )
}
