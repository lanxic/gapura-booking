import React from 'react'
import { cn } from '../lib/utils'

type BadgeVariant = 'success' | 'pending' | 'danger' | 'draft'

type BadgeProps = {
  variant?: BadgeVariant
  children: React.ReactNode
  className?: string
}

const variantClasses: Record<BadgeVariant, string> = {
  success: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  danger:  'bg-red-100 text-red-800',
  draft:   'bg-gray-100 text-gray-700',
}

export function Badge({ variant = 'draft', children, className }: BadgeProps) {
  return (
    <span
      className={cn(
        'text-xs font-medium px-2.5 py-0.5 rounded-full',
        variantClasses[variant],
        className
      )}
    >
      {children}
    </span>
  )
}
