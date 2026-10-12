import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { get, post, setUnauthorizedHandler, tokenStore } from './api'

export type User = { id: number; name: string; email: string; role: 'student' | 'admin'; daily_target_minutes: number; access_level: number }

type Ctx = {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<User>
  register: (d: { name: string; email: string; password: string; password_confirmation: string }) => Promise<User>
  logout: () => Promise<void>
  refresh: () => Promise<void>
  setUser: (u: User) => void
}

const AuthCtx = createContext<Ctx>(null as any)
export const useAuth = () => useContext(AuthCtx)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(!!tokenStore.get())
  const qc = useQueryClient()

  const refresh = useCallback(async () => {
    if (!tokenStore.get()) { setUser(null); setLoading(false); return }
    try { setUser((await get('/auth/me')).user) } catch { setUser(null) } finally { setLoading(false) }
  }, [])

  useEffect(() => {
    setUnauthorizedHandler(() => { setUser(null); qc.clear() })
    refresh()
  }, [refresh, qc])

  const value = useMemo<Ctx>(() => ({
    user, loading, refresh, setUser,
    async login(email, password) {
      const r = await post('/auth/login', { email, password })
      tokenStore.set(r.token); qc.clear(); setUser(r.user); return r.user
    },
    async register(d) {
      const r = await post('/auth/register', d)
      tokenStore.set(r.token); qc.clear(); setUser(r.user); return r.user
    },
    async logout() {
      try { await post('/auth/logout') } catch { /* token mungkin sudah tidak berlaku */ }
      tokenStore.clear(); qc.clear(); setUser(null)
    },
  }), [user, loading, refresh, qc])

  return <AuthCtx.Provider value={value}>{children}</AuthCtx.Provider>
}
