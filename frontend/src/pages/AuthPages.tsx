import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import { ApiError, post } from '../lib/api'
import { usePageMeta } from '../lib/meta'
import { Button, Field, fieldError, Input } from '../components/ui'

function Shell({ title, sub, children }: { title: string; sub?: string; children: React.ReactNode }) {
  return (
    <div className="mx-auto max-w-md px-4 py-12">
      <h1 className="text-3xl font-extrabold tracking-tight">{title}</h1>
      {sub && <p className="mt-2 text-muted">{sub}</p>}
      <div className="mt-6 rounded-2xl border border-line bg-surface p-6">{children}</div>
    </div>
  )
}

export function Login() {
  usePageMeta('Masuk', { noindex: true })
  const { user, login } = useAuth()
  const nav = useNavigate()
  const loc = useLocation() as any
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [err, setErr] = useState<unknown>(null)
  const [busy, setBusy] = useState(false)

  if (user) return <Navigate to={user.role === 'admin' ? '/admin' : '/app'} replace />

  const submit = async (e: FormEvent) => {
    e.preventDefault(); setBusy(true); setErr(null)
    try {
      const u = await login(email, password)
      nav(loc.state?.from || (u.role === 'admin' ? '/admin' : '/app'), { replace: true })
    } catch (x) { setErr(x) } finally { setBusy(false) }
  }
  return (
    <Shell title="Masuk" sub="Lanjutkan belajar dari materi terakhirmu.">
      <form onSubmit={submit} className="space-y-4" noValidate>
        {err instanceof ApiError && !err.errors.email && <p role="alert" className="rounded-lg bg-danger-soft p-3 text-sm text-danger">{err.friendly}</p>}
        <Field label="Email" htmlFor="email" error={fieldError(err, 'email')}><Input id="email" type="email" autoComplete="email" required value={email} onChange={e => setEmail(e.target.value)} /></Field>
        <Field label="Kata sandi" htmlFor="password"><Input id="password" type="password" autoComplete="current-password" required value={password} onChange={e => setPassword(e.target.value)} /></Field>
        <Button type="submit" loading={busy} className="w-full">Masuk</Button>
        <p className="text-center text-sm"><Link to="/lupa-password" className="text-brand-strong underline">Lupa kata sandi?</Link></p>
      </form>
      <p className="mt-4 border-t border-line pt-4 text-center text-sm text-muted">Belum punya akun? <Link to="/daftar" className="font-semibold text-brand-strong underline">Daftar gratis</Link></p>
    </Shell>
  )
}

export function Register() {
  usePageMeta('Daftar', { noindex: true })
  const { user, register } = useAuth()
  const nav = useNavigate()
  const [f, setF] = useState({ name: '', email: '', password: '', password_confirmation: '' })
  const [err, setErr] = useState<unknown>(null)
  const [busy, setBusy] = useState(false)
  if (user) return <Navigate to="/app" replace />
  const set = (k: keyof typeof f) => (e: React.ChangeEvent<HTMLInputElement>) => setF({ ...f, [k]: e.target.value })
  const submit = async (e: FormEvent) => {
    e.preventDefault(); setBusy(true); setErr(null)
    try { await register(f); nav('/app', { replace: true }) } catch (x) { setErr(x) } finally { setBusy(false) }
  }
  return (
    <Shell title="Buat akun gratis" sub="Akun baru langsung mendapat paket Free.">
      <form onSubmit={submit} className="space-y-4" noValidate>
        {err instanceof ApiError && err.status !== 422 && <p role="alert" className="rounded-lg bg-danger-soft p-3 text-sm text-danger">{err.friendly}</p>}
        <Field label="Nama" htmlFor="name" error={fieldError(err, 'name')}><Input id="name" autoComplete="name" required value={f.name} onChange={set('name')} /></Field>
        <Field label="Email" htmlFor="email" error={fieldError(err, 'email')}><Input id="email" type="email" autoComplete="email" required value={f.email} onChange={set('email')} /></Field>
        <Field label="Kata sandi" htmlFor="password" hint="Minimal 8 karakter, berisi huruf dan angka." error={fieldError(err, 'password')}><Input id="password" type="password" autoComplete="new-password" required value={f.password} onChange={set('password')} /></Field>
        <Field label="Ulangi kata sandi" htmlFor="pc"><Input id="pc" type="password" autoComplete="new-password" required value={f.password_confirmation} onChange={set('password_confirmation')} /></Field>
        <Button type="submit" loading={busy} className="w-full">Daftar</Button>
        <p className="text-xs text-muted">Dengan mendaftar, Anda menyetujui <Link className="underline" to="/syarat-penggunaan">Syarat Penggunaan</Link> dan <Link className="underline" to="/kebijakan-privasi">Kebijakan Privasi</Link>.</p>
      </form>
      <p className="mt-4 border-t border-line pt-4 text-center text-sm text-muted">Sudah punya akun? <Link to="/masuk" className="font-semibold text-brand-strong underline">Masuk</Link></p>
    </Shell>
  )
}

export function ForgotPassword() {
  usePageMeta('Lupa kata sandi', { noindex: true })
  const [email, setEmail] = useState('')
  const [done, setDone] = useState(false)
  const [err, setErr] = useState<unknown>(null)
  const [busy, setBusy] = useState(false)
  const submit = async (e: FormEvent) => {
    e.preventDefault(); setBusy(true); setErr(null)
    try { await post('/auth/forgot-password', { email }); setDone(true) } catch (x) { setErr(x) } finally { setBusy(false) }
  }
  return (
    <Shell title="Lupa kata sandi" sub="Masukkan email akunmu. Kami kirim tautan untuk membuat kata sandi baru.">
      {done ? (
        <p role="status" className="rounded-lg bg-ok-soft p-3 text-sm text-ok">Jika email terdaftar, tautan reset sudah dikirim. Periksa kotak masuk dan folder spam.</p>
      ) : (
        <form onSubmit={submit} className="space-y-4">
          {err instanceof ApiError && <p role="alert" className="text-sm text-danger">{err.friendly}</p>}
          <Field label="Email" htmlFor="email"><Input id="email" type="email" required value={email} onChange={e => setEmail(e.target.value)} /></Field>
          <Button type="submit" loading={busy} className="w-full">Kirim tautan reset</Button>
        </form>
      )}
    </Shell>
  )
}

export function ResetPassword() {
  usePageMeta('Reset kata sandi', { noindex: true })
  const [sp] = useSearchParams()
  const nav = useNavigate()
  const [f, setF] = useState({ password: '', password_confirmation: '' })
  const [err, setErr] = useState<unknown>(null)
  const [busy, setBusy] = useState(false)
  const submit = async (e: FormEvent) => {
    e.preventDefault(); setBusy(true); setErr(null)
    try {
      await post('/auth/reset-password', { token: sp.get('token'), email: sp.get('email'), ...f })
      nav('/masuk', { replace: true })
    } catch (x) { setErr(x) } finally { setBusy(false) }
  }
  return (
    <Shell title="Buat kata sandi baru">
      <form onSubmit={submit} className="space-y-4">
        {err instanceof ApiError && !err.errors.password && <p role="alert" className="rounded-lg bg-danger-soft p-3 text-sm text-danger">{err.friendly}</p>}
        <Field label="Kata sandi baru" htmlFor="p" hint="Minimal 8 karakter, berisi huruf dan angka." error={fieldError(err, 'password')}><Input id="p" type="password" required value={f.password} onChange={e => setF({ ...f, password: e.target.value })} /></Field>
        <Field label="Ulangi kata sandi" htmlFor="pc"><Input id="pc" type="password" required value={f.password_confirmation} onChange={e => setF({ ...f, password_confirmation: e.target.value })} /></Field>
        <Button type="submit" loading={busy} className="w-full">Simpan kata sandi</Button>
      </form>
    </Shell>
  )
}
