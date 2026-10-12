import { forwardRef, useEffect, useRef, type ButtonHTMLAttributes, type InputHTMLAttributes, type ReactNode, type SelectHTMLAttributes, type TextareaHTMLAttributes } from 'react'
import { Link } from 'react-router-dom'
import { ApiError } from '../lib/api'

export const cx = (...a: (string | false | null | undefined)[]) => a.filter(Boolean).join(' ')

type BtnProps = ButtonHTMLAttributes<HTMLButtonElement> & { variant?: 'primary' | 'secondary' | 'ghost' | 'danger'; size?: 'sm' | 'md' | 'lg'; loading?: boolean }
const btnBase = 'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap'
const btnVariant = {
  primary: 'bg-brand text-on-brand hover:bg-brand-strong hover:text-on-brand',
  secondary: 'bg-surface text-ink border border-line hover:bg-sunken',
  ghost: 'text-ink hover:bg-sunken',
  danger: 'bg-danger text-white hover:opacity-90',
}
const btnSize = { sm: 'px-3 py-1.5 text-sm', md: 'px-4 py-2.5 text-[0.95rem]', lg: 'px-6 py-3.5 text-base' }

export function Button({ variant = 'primary', size = 'md', loading, className, children, disabled, ...rest }: BtnProps) {
  return (
    <button className={cx(btnBase, btnVariant[variant], btnSize[size], className)} disabled={disabled || loading} {...rest}>
      {loading && <Spinner small />}
      {children}
    </button>
  )
}

export function LinkButton({ to, variant = 'primary', size = 'md', className, children }: { to: string; variant?: BtnProps['variant']; size?: BtnProps['size']; className?: string; children: ReactNode }) {
  return <Link to={to} className={cx(btnBase, btnVariant[variant!], btnSize[size!], className)}>{children}</Link>
}

export function Spinner({ small }: { small?: boolean }) {
  return <span role="status" aria-label="Memuat" className={cx('inline-block animate-spin rounded-full border-2 border-current border-t-transparent', small ? 'h-4 w-4' : 'h-6 w-6')} />
}

export function Loading({ label = 'Memuat…' }: { label?: string }) {
  return <div className="flex items-center justify-center gap-3 py-16 text-muted"><Spinner /> <span>{label}</span></div>
}

export function Skeleton({ className }: { className?: string }) {
  return <div className={cx('animate-pulse rounded-lg bg-sunken', className)} aria-hidden />
}

export function ErrorState({ error, onRetry }: { error: unknown; onRetry?: () => void }) {
  const msg = error instanceof ApiError ? error.friendly : 'Terjadi kesalahan. Coba lagi.'
  const locked = error instanceof ApiError && error.status === 403
  return (
    <div role="alert" className="mx-auto max-w-md rounded-xl border border-line bg-surface p-6 text-center">
      <p className="font-semibold">{locked ? 'Konten ini terkunci' : 'Gagal memuat data'}</p>
      <p className="mt-1 text-sm text-muted">{msg}</p>
      <div className="mt-4 flex justify-center gap-2">
        {locked && <LinkButton to="/app/paket" size="sm">Lihat paket</LinkButton>}
        {onRetry && !locked && <Button size="sm" variant="secondary" onClick={onRetry}>Coba lagi</Button>}
      </div>
    </div>
  )
}

export function EmptyState({ title, children, action }: { title: string; children?: ReactNode; action?: ReactNode }) {
  return (
    <div className="mx-auto max-w-md rounded-xl border border-dashed border-line p-8 text-center">
      <p className="font-semibold">{title}</p>
      {children && <p className="mt-1 text-sm text-muted">{children}</p>}
      {action && <div className="mt-4 flex justify-center">{action}</div>}
    </div>
  )
}

export function Card({ className, children, ...rest }: { className?: string; children: ReactNode } & React.HTMLAttributes<HTMLDivElement>) {
  return <div className={cx('rounded-2xl border border-line bg-surface p-5', className)} {...rest}>{children}</div>
}

const badgeTone = {
  neutral: 'bg-sunken text-muted',
  brand: 'bg-brand-soft text-brand-strong',
  amber: 'bg-amber-soft text-amber',
  ok: 'bg-ok-soft text-ok',
  danger: 'bg-danger-soft text-danger',
}
export function Badge({ tone = 'neutral', children }: { tone?: keyof typeof badgeTone; children: ReactNode }) {
  return <span className={cx('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', badgeTone[tone])}>{children}</span>
}

export function ProgressBar({ value, label, tone = 'brand' }: { value: number; label?: string; tone?: 'brand' | 'amber' }) {
  const v = Math.max(0, Math.min(100, value || 0))
  return (
    <div role="progressbar" aria-valuenow={Math.round(v)} aria-valuemin={0} aria-valuemax={100} aria-label={label} className="h-2.5 w-full overflow-hidden rounded-full bg-sunken">
      <div className={cx('h-full rounded-full transition-[width] duration-500', tone === 'brand' ? 'bg-brand' : 'bg-amber')} style={{ width: `${v}%` }} />
    </div>
  )
}

const fieldCls = 'w-full rounded-lg border border-line bg-surface px-3 py-2.5 text-ink placeholder:text-muted/70 focus:border-brand'
export const Input = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(function Input({ className, ...p }, ref) {
  return <input ref={ref} className={cx(fieldCls, className)} {...p} />
})
export function Select({ className, children, ...p }: SelectHTMLAttributes<HTMLSelectElement>) {
  return <select className={cx(fieldCls, 'pr-8', className)} {...p}>{children}</select>
}
export function Textarea({ className, ...p }: TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return <textarea className={cx(fieldCls, 'min-h-24', className)} {...p} />
}

export function Field({ label, error, hint, children, htmlFor }: { label: string; error?: string; hint?: string; children: ReactNode; htmlFor?: string }) {
  return (
    <div className="space-y-1.5">
      <label htmlFor={htmlFor} className="block text-sm font-semibold">{label}</label>
      {children}
      {hint && !error && <p className="text-xs text-muted">{hint}</p>}
      {error && <p role="alert" className="text-xs font-medium text-danger">{error}</p>}
    </div>
  )
}

/** Pesan error form (422) per field. */
export const fieldError = (e: unknown, name: string) => (e instanceof ApiError ? e.errors[name]?.[0] : undefined)

export function Modal({ open, onClose, title, children, wide }: { open: boolean; onClose: () => void; title: string; children: ReactNode; wide?: boolean }) {
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', onKey)
    ref.current?.focus()
    return () => document.removeEventListener('keydown', onKey)
  }, [open, onClose])
  if (!open) return null
  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" onMouseDown={e => e.target === e.currentTarget && onClose()}>
      <div ref={ref} tabIndex={-1} role="dialog" aria-modal="true" aria-label={title}
        className={cx('max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-surface p-5 shadow-xl outline-none sm:rounded-2xl', wide ? 'sm:max-w-3xl' : 'sm:max-w-lg')}>
        <div className="mb-4 flex items-start justify-between gap-4">
          <h2 className="text-lg font-bold">{title}</h2>
          <button onClick={onClose} aria-label="Tutup" className="rounded-md p-1 text-muted hover:bg-sunken">✕</button>
        </div>
        {children}
      </div>
    </div>
  )
}

export function PageHeader({ title, children, sub }: { title: string; sub?: string; children?: ReactNode }) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">{title}</h1>
        {sub && <p className="mt-1 max-w-2xl text-muted">{sub}</p>}
      </div>
      {children && <div className="flex flex-wrap gap-2">{children}</div>}
    </div>
  )
}
