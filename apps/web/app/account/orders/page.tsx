'use client'

import { useRouter } from 'next/navigation'
import { useEffect } from 'react'

// Redirect to /account since orders list lives there
export default function AccountOrdersPage() {
  const router = useRouter()
  useEffect(() => {
    router.replace('/account')
  }, [router])
  return null
}
