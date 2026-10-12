const BASE = (import.meta.env.VITE_API_URL as string | undefined)?.replace(/\/$/, '') || 'http://localhost:8000/api'
const TOKEN_KEY = 'osn.token'

export const tokenStore = {
  get(): string | null {
    try { return localStorage.getItem(TOKEN_KEY) } catch { return null }
  },
  set(t: string) { try { localStorage.setItem(TOKEN_KEY, t) } catch { /* ignore */ } },
  clear() { try { localStorage.removeItem(TOKEN_KEY) } catch { /* ignore */ } },
}

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>
  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
  /** Pesan paling berguna untuk ditampilkan ke pengguna. */
  get friendly(): string {
    const first = Object.values(this.errors)[0]?.[0]
    if (this.status === 0) return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda lalu coba lagi.'
    if (this.status === 403) return this.message || 'Anda tidak punya akses ke konten ini.'
    if (this.status === 429) return 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.'
    return first || this.message || 'Terjadi kesalahan. Coba lagi.'
  }
}

let onUnauthorized: (() => void) | null = null
export const setUnauthorizedHandler = (fn: () => void) => { onUnauthorized = fn }

export async function api<T = any>(path: string, opts: { method?: string; body?: any; form?: FormData; signal?: AbortSignal } = {}): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const token = tokenStore.get()
  if (token) headers.Authorization = `Bearer ${token}`
  let body: BodyInit | undefined
  if (opts.form) body = opts.form
  else if (opts.body !== undefined) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(opts.body) }

  let res: Response
  try {
    res = await fetch(BASE + path, { method: opts.method || 'GET', headers, body, signal: opts.signal })
  } catch (e: any) {
    if (e?.name === 'AbortError') throw e
    throw new ApiError(0, 'Jaringan bermasalah')
  }
  const data = res.status === 204 ? null : await res.json().catch(() => null)
  if (!res.ok) {
    if (res.status === 401 && token) { tokenStore.clear(); onUnauthorized?.() }
    throw new ApiError(res.status, data?.message || res.statusText, data?.errors || {})
  }
  return data as T
}

export const get = <T = any>(p: string) => api<T>(p)
export const post = <T = any>(p: string, body?: any) => api<T>(p, { method: 'POST', body: body ?? {} })
export const put = <T = any>(p: string, body?: any) => api<T>(p, { method: 'PUT', body })
export const patch = <T = any>(p: string, body?: any) => api<T>(p, { method: 'PATCH', body })
export const del = <T = any>(p: string) => api<T>(p, { method: 'DELETE' })

export const qs = (params: Record<string, any>) => {
  const u = new URLSearchParams()
  Object.entries(params).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) u.set(k, String(v)) })
  const s = u.toString()
  return s ? `?${s}` : ''
}
