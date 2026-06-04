import { NextRequest, NextResponse } from 'next/server'

type AdminRole = 'super_admin' | 'admin' | 'supervisor' | 'kasir' | 'scanner'

const ROLE_HOME: Record<AdminRole, string> = {
  super_admin: '/dashboard',
  admin: '/dashboard',
  supervisor: '/dashboard',
  kasir: '/dashboard',
  scanner: '/dashboard',
}

const LOGIN_ROUTES = ['/login']

export default function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl
  const token = request.cookies.get('admin_token')?.value
  const role = request.cookies.get('admin_role')?.value as AdminRole | undefined

  const isLoginRoute = LOGIN_ROUTES.some((r) => pathname.startsWith(r))

  if (!token && !isLoginRoute) {
    return NextResponse.redirect(new URL('/login', request.url))
  }

  if (token && role && isLoginRoute) {
    return NextResponse.redirect(new URL(ROLE_HOME[role] ?? '/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico|api).*)'],
}
