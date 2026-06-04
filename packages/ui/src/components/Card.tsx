import React from 'react'
import { cn } from '../lib/utils'

type CardProps = React.HTMLAttributes<HTMLDivElement> & {
  hoverable?: boolean
}

export function Card({ hoverable, className, children, ...props }: CardProps) {
  return (
    <div
      className={cn(
        'bg-white rounded-xl border border-gray-200 shadow-sm p-6',
        hoverable && 'hover:shadow-md hover:border-blue-200 transition cursor-pointer',
        className
      )}
      {...props}
    >
      {children}
    </div>
  )
}

export function CardHeader({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={cn('flex items-center justify-between mb-4', className)} {...props}>
      {children}
    </div>
  )
}

export function CardFooter({ className, children, ...props }: React.HTMLAttributes<HTMLDivElement>) {
  return (
    <div className={cn('flex items-center gap-3 pt-4 border-t border-gray-100', className)} {...props}>
      {children}
    </div>
  )
}
