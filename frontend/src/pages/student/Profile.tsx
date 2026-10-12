import { useEffect, useState, type FormEvent } from 'react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { ApiError, get, put } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { useTheme } from '../../lib/theme'
import { usePageMeta } from '../../lib/meta'
import { LEVEL } from '../../lib/format'
import { Button, Card, ErrorState, Field, fieldError, Input, Loading, PageHeader } from '../../components/ui'

export default function Profile() {
  usePageMeta('Profil', { noindex: true })
  const { setUser } = useAuth()
  const { theme, toggle } = useTheme()
  const q = useQuery({ queryKey: ['profile'], queryFn: () => get('/profile') })
  const [name, setName] = useState('')
  const [target, setTarget] = useState(45)
  const [msg, setMsg] = useState('')
  const [pw, setPw] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [pwMsg, setPwMsg] = useState('')

  useEffect(() => { if (q.data) { setName(q.data.user.name); setTarget(q.data.user.daily_target_minutes) } }, [q.data])

  const save = useMutation({
    mutationFn: () => put('/profile', { name, daily_target_minutes: target }),
    onSuccess: r => { setUser(r.user); setMsg('Profil disimpan.') },
  })
  const savePw = useMutation({
    mutationFn: () => put('/profile/password', pw),
    onSuccess: () => { setPwMsg('Kata sandi diperbarui.'); setPw({ current_password: '', password: '', password_confirmation: '' }) },
  })

  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const { limits, user } = q.data
  const submit = (e: FormEvent) => { e.preventDefault(); setMsg(''); save.mutate() }
  const submitPw = (e: FormEvent) => { e.preventDefault(); setPwMsg(''); savePw.mutate() }
  const steps: number[] = []
  for (let m = limits.daily_target_min; m <= limits.daily_target_max; m += 5) steps.push(m)

  return (
    <>
      <PageHeader title="Profil" sub={`${user.email} · Paket aktif: ${LEVEL[user.access_level] ?? 'Admin'}`} />
      <div className="grid gap-5 lg:grid-cols-2">
        <Card>
          <form onSubmit={submit} className="space-y-4">
            <h2 className="font-bold">Data dan target belajar</h2>
            <Field label="Nama" htmlFor="n" error={fieldError(save.error, 'name')}><Input id="n" value={name} onChange={e => setName(e.target.value)} required /></Field>
            <Field label={`Target belajar harian: ${target} menit`} htmlFor="t" hint={`Rentang ${limits.daily_target_min}–${limits.daily_target_max} menit.`} error={fieldError(save.error, 'daily_target_minutes')}>
              <input id="t" type="range" min={limits.daily_target_min} max={limits.daily_target_max} step={5} list="steps" value={target} onChange={e => setTarget(+e.target.value)} className="w-full accent-[var(--brand)]" />
              <datalist id="steps">{steps.map(s => <option key={s} value={s} />)}</datalist>
            </Field>
            {save.isError && !(save.error instanceof ApiError && save.error.status === 422) && <p role="alert" className="text-sm text-danger">Gagal menyimpan.</p>}
            {msg && <p role="status" className="text-sm text-ok">{msg}</p>}
            <Button type="submit" loading={save.isPending}>Simpan</Button>
          </form>
        </Card>
        <Card>
          <form onSubmit={submitPw} className="space-y-4">
            <h2 className="font-bold">Ganti kata sandi</h2>
            <Field label="Kata sandi saat ini" htmlFor="cp" error={fieldError(savePw.error, 'current_password')}><Input id="cp" type="password" autoComplete="current-password" value={pw.current_password} onChange={e => setPw({ ...pw, current_password: e.target.value })} required /></Field>
            <Field label="Kata sandi baru" htmlFor="np" hint="Minimal 8 karakter, berisi huruf dan angka." error={fieldError(savePw.error, 'password')}><Input id="np" type="password" autoComplete="new-password" value={pw.password} onChange={e => setPw({ ...pw, password: e.target.value })} required /></Field>
            <Field label="Ulangi kata sandi baru" htmlFor="rp"><Input id="rp" type="password" autoComplete="new-password" value={pw.password_confirmation} onChange={e => setPw({ ...pw, password_confirmation: e.target.value })} required /></Field>
            {pwMsg && <p role="status" className="text-sm text-ok">{pwMsg}</p>}
            <Button type="submit" variant="secondary" loading={savePw.isPending}>Ganti kata sandi</Button>
          </form>
        </Card>
        <Card className="lg:col-span-2">
          <h2 className="font-bold">Tampilan</h2>
          <p className="mt-1 text-sm text-muted">Mode {theme === 'dark' ? 'gelap' : 'terang'} sedang aktif.</p>
          <Button variant="secondary" className="mt-3" onClick={toggle}>Ganti ke mode {theme === 'dark' ? 'terang' : 'gelap'}</Button>
        </Card>
      </div>
    </>
  )
}
