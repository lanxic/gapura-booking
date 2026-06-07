import { cn } from '@/lib/utils'

export type ColDef =
  | string
  | { label: string; align?: 'left' | 'right' | 'center'; className?: string }

function resolveCol(col: ColDef) {
  if (typeof col === 'string') return { label: col, align: 'left' as const, className: '' }
  return { align: 'left' as const, className: '', ...col }
}

type TableCardProps = {
  columns: ColDef[]
  isLoading?: boolean
  isEmpty?: boolean
  emptyMessage?: string
  skeletonRows?: number
  children?: React.ReactNode
}

export function TableCard({
  columns,
  isLoading = false,
  isEmpty = false,
  emptyMessage = 'Tidak ada data.',
  skeletonRows = 5,
  children,
}: TableCardProps) {
  const cols = columns.map(resolveCol)

  return (
    <div className="rounded-xl border border-border bg-card overflow-hidden">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b border-border bg-muted/30">
            {cols.map((col, i) => (
              <th
                key={i}
                className={cn(
                  'px-4 py-3 font-medium text-muted-foreground',
                  col.align === 'right'  && 'text-right',
                  col.align === 'center' && 'text-center',
                  col.align === 'left'   && 'text-left',
                  col.className,
                )}
              >
                {col.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {isLoading ? (
            Array.from({ length: skeletonRows }).map((_, i) => (
              <tr key={i} className="border-b border-border">
                {cols.map((_, j) => (
                  <td key={j} className="px-4 py-3">
                    <div className="h-4 bg-muted rounded animate-pulse" />
                  </td>
                ))}
              </tr>
            ))
          ) : isEmpty ? (
            <tr>
              <td colSpan={cols.length} className="px-4 py-12 text-center text-muted-foreground">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            children
          )}
        </tbody>
      </table>
    </div>
  )
}
