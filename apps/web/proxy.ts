import { NextRequest, NextResponse } from 'next/server'

const PROTECTED_ROUTES = ['/account']
const AUTH_ROUTES = ['/auth/login', '/auth/register']

export default function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl
  const token = request.cookies.get('customer_token')?.value

  const isProtected = PROTECTED_ROUTES.some((r) => pathname.startsWith(r))
  const isAuthRoute = AUTH_ROUTES.some((r) => pathname.startsWith(r))

  if (isProtected && !token) {
    const loginUrl = new URL('/auth/login', request.url)
    loginUrl.searchParams.set('redirect', pathname)
    return NextResponse.redirect(loginUrl)
  }

  if (isAuthRoute && token) {
    return NextResponse.redirect(new URL('/account/orders', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/account/:path*', '/auth/:path*'],
}
