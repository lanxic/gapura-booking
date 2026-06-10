import { clearSession } from './session'

const API_URL = process.env.NEXT_PUBLIC_ADMIN_API_URL ?? 'http://localhost:8000/v1'

type RequestOptions = RequestInit & { token?: string }

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { token, ...init } = options
  const isFormData = init.body instanceof FormData
  const headers: Record<string, string> = {
    ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
    ...(init.headers as Record<string, string>),
  }
  if (token) headers['Authorization'] = `Bearer ${token}`

  const res = await fetch(`${API_URL}${path}`, { ...init, headers })

  if (res.status === 401) {
    if (typeof window !== 'undefined') {
      clearSession()  // clears store + cookies, then redirects to /login
    }
    throw new Error('Unauthorized')
  }

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }))
    throw new Error(error.message ?? 'Request failed')
  }

  return res.json() as Promise<T>
}

export const api = {
  get: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: 'GET' }),
  post: <T>(path: string, body: unknown, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: 'POST', body: JSON.stringify(body) }),
  put: <T>(path: string, body: unknown, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: 'PUT', body: JSON.stringify(body) }),
  delete: <T>(path: string, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: 'DELETE' }),
  upload: <T>(path: string, formData: FormData, opts?: RequestOptions) =>
    request<T>(path, { ...opts, method: 'POST', body: formData }),
}
