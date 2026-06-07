import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/lib/utils'

function getRange(current: number, total: number): (number | '...')[] {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  if (current <= 4) return [1, 2, 3, 4, 5, '...', total]
  if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total]
  return [1, '...', current - 1, current, current + 1, '...', total]
}

type PaginationProps = {
  page: number
  lastPage: number
  total?: number
  label?: string
  onChange: (page: number) => void
}

export function Pagination({ page, lastPage, total, label, onChange }: PaginationProps) {
  if (total == null && !lastPage) return null

  const safeLast = lastPage || 1

  return (
    <div className="flex items-center justify-between gap-4 text-sm">
      {/* Total count */}
      <span className="text-muted-foreground shrink-0">
        {total != null ? `${total.toLocaleString('id-ID')} ${label ?? 'item'}` : ''}
      </span>

      {/* Navigation — always visible, disabled when on single page */}
      <div className="flex items-center gap-1">
        <button
          onClick={() => onChange(Math.max(1, page - 1))}
          disabled={page === 1}
          className="p-1.5 rounded-md border border-border text-muted-foreground hover:bg-accent disabled:opacity-40 transition-colors"
          aria-label="Halaman sebelumnya"
        >
          <ChevronLeft size={15} />
        </button>

        {getRange(page, safeLast).map((item, i) =>
          item === '...' ? (
            <span key={`e-${i}`} className="px-2 py-1.5 text-muted-foreground select-none">…</span>
          ) : (
            <button
              key={item}
              onClick={() => onChange(item as number)}
              className={cn(
                'min-w-[34px] px-2 py-1.5 rounded-md border text-sm transition-colors',
                page === item
                  ? 'border-primary bg-primary text-primary-foreground font-semibold'
                  : 'border-border text-muted-foreground hover:bg-accent',
              )}
            >
              {item}
            </button>
          )
        )}

        <button
          onClick={() => onChange(Math.min(safeLast, page + 1))}
          disabled={page === safeLast}
          className="p-1.5 rounded-md border border-border text-muted-foreground hover:bg-accent disabled:opacity-40 transition-colors"
          aria-label="Halaman berikutnya"
        >
          <ChevronRight size={15} />
        </button>
      </div>
    </div>
  )
}
