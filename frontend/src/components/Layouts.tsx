import { useState } from 'react'
import { Link, NavLink, Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import { useTheme } from '../lib/theme'
import { cx, LinkButton, Loading } from './ui'

export function Logo({ className }: { className?: string }) {
  return (
    <span className={cx('inline-flex items-center gap-2 font-bold tracking-tight', className)}>
      <svg width="28" height="28" viewBox="0 0 64 64" aria-hidden><rect width="64" height="64" rx="14" fill="var(--brand)" /><path d="M14 44c10 0 8-24 18-24s8 24 18 24" fill="none" stroke="var(--on-brand)" strokeWidth="5" strokeLinecap="round" /></svg>
      <span>OSN Biology Academy</span>
    </span>
  )
}

export function ThemeToggle() {
  const { theme, toggle } = useTheme()
  return (
    <button onClick={toggle} aria-label={theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'} className="rounded-lg p-2 text-muted hover:bg-sunken">
      {theme === 'dark' ? '☀' : '☾'}
    </button>
  )
}

export function PublicLayout() {
  const { user } = useAuth()
  return (
    <div className="flex min-h-screen flex-col">
      <a href="#utama" className="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded focus:bg-surface focus:p-2">Lewati ke konten</a>
      <header className="sticky top-0 z-30 border-b border-line bg-paper/90 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
          <Link to="/" aria-label="Beranda"><Logo /></Link>
          <nav aria-label="Utama" className="ml-6 hidden items-center gap-5 text-sm font-medium md:flex">
            <a href="/#kurikulum" className="text-muted hover:text-ink">Kurikulum</a>
            <Link to="/demo" className="text-muted hover:text-ink">Materi gratis</Link>
            <a href="/#paket" className="text-muted hover:text-ink">Paket</a>
            <a href="/#faq" className="text-muted hover:text-ink">FAQ</a>
          </nav>
          <div className="ml-auto flex items-center gap-2">
            <ThemeToggle />
            {user ? (
              <LinkButton to={user.role === 'admin' ? '/admin' : '/app'} size="sm">Buka dashboard</LinkButton>
            ) : (
              <>
                <LinkButton to="/masuk" variant="ghost" size="sm" className="hidden sm:inline-flex">Masuk</LinkButton>
                <LinkButton to="/daftar" size="sm">Mulai gratis</LinkButton>
              </>
            )}
          </div>
        </div>
      </header>
      <main id="utama" className="flex-1"><Outlet /></main>
      <footer className="border-t border-line bg-sunken">
        <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:justify-between">
          <div className="max-w-sm">
            <Logo />
            <p className="mt-3 text-sm text-muted">Belajar mandiri Biologi untuk OSN: pelajari, latih, ukur, lalu perbaiki.</p>
          </div>
          <nav aria-label="Legal" className="flex flex-col gap-2 text-sm">
            <Link to="/syarat-penggunaan" className="text-muted hover:text-ink">Syarat Penggunaan</Link>
            <Link to="/kebijakan-privasi" className="text-muted hover:text-ink">Kebijakan Privasi</Link>
            <Link to="/ketentuan-refund" className="text-muted hover:text-ink">Ketentuan Refund</Link>
          </nav>
        </div>
        <p className="border-t border-line px-4 py-4 text-center text-xs text-muted">© {new Date().getFullYear()} OSN Biology Academy. Platform ini independen dan tidak berafiliasi dengan penyelenggara OSN.</p>
      </footer>
    </div>
  )
}

export function RequireAuth({ admin }: { admin?: boolean }) {
  const { user, loading } = useAuth()
  const loc = useLocation()
  if (loading) return <Loading />
  if (!user) return <Navigate to="/masuk" state={{ from: loc.pathname }} replace />
  if (admin && user.role !== 'admin') return <Navigate to="/app" replace />
  return <Outlet />
}

const studentNav = [
  { to: '/app', label: 'Beranda', end: true, icon: '⌂' },
  { to: '/app/kurikulum', label: 'Belajar', icon: '▤' },
  { to: '/app/latihan', label: 'Latihan', icon: '✎' },
  { to: '/app/evaluasi', label: 'Evaluasi', icon: '%' },
  { to: '/app/paket', label: 'Paket', icon: '★' },
  { to: '/app/profil', label: 'Profil', icon: '☺' },
]

function NavItems({ items, mobile }: { items: { to: string; label: string; end?: boolean; icon?: string }[]; mobile?: boolean }) {
  return (
    <>
      {items.map(i => (
        <NavLink key={i.to} to={i.to} end={i.end}
          className={({ isActive }) => cx(
            mobile ? 'flex flex-1 flex-col items-center gap-0.5 py-2 text-[0.7rem]' : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
            isActive ? 'bg-brand-soft text-brand-strong font-semibold' : 'text-muted hover:bg-sunken hover:text-ink',
          )}>
          {i.icon && <span aria-hidden className={mobile ? 'text-lg leading-none' : 'w-5 text-center'}>{i.icon}</span>}
          {i.label}
        </NavLink>
      ))}
    </>
  )
}

export function StudentLayout() {
  const { user, logout } = useAuth()
  return (
    <div className="min-h-screen md:grid md:grid-cols-[15rem_1fr]">
      <aside className="hidden border-r border-line bg-surface md:block">
        <div className="sticky top-0 flex h-screen flex-col p-4">
          <Link to="/app"><Logo className="text-[0.95rem]" /></Link>
          <nav aria-label="Menu siswa" className="mt-6 flex flex-col gap-1"><NavItems items={studentNav} /></nav>
          <div className="mt-auto space-y-2 text-sm">
            {user?.role === 'admin' && <Link to="/admin" className="block rounded-lg px-3 py-2 font-medium text-brand-strong hover:bg-sunken">Panel admin</Link>}
            <div className="flex items-center justify-between px-3"><span className="truncate text-muted">{user?.name}</span><ThemeToggle /></div>
            <button onClick={logout} className="w-full rounded-lg px-3 py-2 text-left text-muted hover:bg-sunken">Keluar</button>
          </div>
        </div>
      </aside>
      <div className="flex min-w-0 flex-col">
        <header className="flex items-center justify-between border-b border-line bg-surface px-4 py-2.5 md:hidden">
          <Link to="/app"><Logo className="text-sm" /></Link>
          <div className="flex items-center gap-1"><ThemeToggle /><button onClick={logout} className="rounded-lg px-2 py-1 text-sm text-muted hover:bg-sunken">Keluar</button></div>
        </header>
        <main className="mx-auto w-full max-w-5xl flex-1 px-4 py-6 pb-28 md:px-8 md:py-8 md:pb-10"><Outlet /></main>
        <nav aria-label="Menu siswa" className="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-surface md:hidden"><NavItems mobile items={studentNav.filter(i => i.to !== '/app/paket')} /></nav>
      </div>
    </div>
  )
}

const adminNav = [
  { to: '/admin', label: 'Ringkasan', end: true },
  { to: '/admin/topik', label: 'Bidang' },
  { to: '/admin/modul', label: 'Modul' },
  { to: '/admin/materi', label: 'Materi' },
  { to: '/admin/soal', label: 'Soal' },
  { to: '/admin/kuis', label: 'Kuis' },
  { to: '/admin/siswa', label: 'Siswa' },
  { to: '/admin/pembayaran', label: 'Pembayaran' },
  { to: '/admin/paket', label: 'Paket' },
  { to: '/admin/pengaturan', label: 'Pengaturan' },
  { to: '/admin/audit', label: 'Audit log' },
]

export function AdminLayout() {
  const { user, logout } = useAuth()
  const [open, setOpen] = useState(false)
  return (
    <div className="min-h-screen md:grid md:grid-cols-[14rem_1fr]">
      <aside className={cx('border-r border-line bg-surface md:block', open ? 'block' : 'hidden')}>
        <div className="p-4 md:sticky md:top-0 md:h-screen md:overflow-y-auto">
          <Link to="/admin"><Logo className="text-[0.9rem]" /></Link>
          <p className="mt-1 text-xs text-muted">Panel admin</p>
          <nav aria-label="Menu admin" className="mt-4 flex flex-col gap-1" onClick={() => setOpen(false)}><NavItems items={adminNav} /></nav>
          <div className="mt-6 space-y-1 text-sm">
            <Link to="/app" className="block rounded-lg px-3 py-2 text-muted hover:bg-sunken">Lihat sebagai siswa</Link>
            <button onClick={logout} className="w-full rounded-lg px-3 py-2 text-left text-muted hover:bg-sunken">Keluar ({user?.name})</button>
            <ThemeToggle />
          </div>
        </div>
      </aside>
      <div className="min-w-0">
        <div className="flex items-center border-b border-line bg-surface px-4 py-2 md:hidden">
          <button onClick={() => setOpen(o => !o)} aria-expanded={open} className="rounded-lg px-3 py-1.5 text-sm font-semibold hover:bg-sunken">☰ Menu</button>
        </div>
        <main className="mx-auto max-w-6xl px-4 py-6 md:px-8"><Outlet /></main>
      </div>
    </div>
  )
}
