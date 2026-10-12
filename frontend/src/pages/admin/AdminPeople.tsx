import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ApiError, get, patch, post, put, qs } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { dateID, dateTimeID, LEVEL, rupiah } from '../../lib/format'
import { Badge, Button, EmptyState, ErrorState, Field, Input, Loading, Modal, PageHeader, Select, Textarea } from '../../components/ui'
import { FormErrors } from './shared'

type Kind = 'users' | 'payments' | 'plans' | 'audit'
const TITLE: Record<Kind, string> = { users: 'Siswa dan akses', payments: 'Verifikasi pembayaran', plans: 'Paket', audit: 'Audit log' }

export default function AdminPeople({ kind }: { kind: Kind }) {
  usePageMeta(TITLE[kind], { noindex: true })
  return (
    <>
      <PageHeader title={TITLE[kind]} />
      {kind === 'users' && <Users />}
      {kind === 'payments' && <Payments />}
      {kind === 'plans' && <Plans />}
      {kind === 'audit' && <Audit />}
    </>
  )
}

function Users() {
  const qc = useQueryClient()
  const [s, setS] = useState('')
  const [role, setRole] = useState('')
  const [grant, setGrant] = useState<any>(null)
  const [create, setCreate] = useState(false)
  const list = useQuery({ queryKey: ['admin-users', s, role], queryFn: () => get('/admin/users' + qs({ q: s, role })) })
  const plans = useQuery({ queryKey: ['admin-plans'], queryFn: () => get('/admin/plans?per_page=100') })
  const roleM = useMutation({ mutationFn: ({ id, role }: any) => patch(`/admin/users/${id}`, { role }), onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-users'] }), onError: e => alert(e instanceof ApiError ? e.friendly : 'Gagal') })
  return (
    <>
      <div className="mb-4 flex flex-wrap gap-2">
        <Input aria-label="Cari pengguna" placeholder="Cari nama atau email…" value={s} onChange={e => setS(e.target.value)} className="max-w-xs" />
        <Select aria-label="Peran" value={role} onChange={e => setRole(e.target.value)} className="max-w-40"><option value="">Semua peran</option><option value="student">Siswa</option><option value="admin">Admin</option></Select>
        <Button className="ml-auto" onClick={() => setCreate(true)}>Tambah pengguna</Button>
      </div>
      {list.isLoading && <Loading />}
      {list.isError && <ErrorState error={list.error} onRetry={() => list.refetch()} />}
      {list.data?.data.length === 0 && <EmptyState title="Tidak ada pengguna" />}
      <ul className="divide-y divide-line rounded-2xl border border-line bg-surface">
        {list.data?.data.map((u: any) => (
          <li key={u.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div><p className="font-semibold">{u.name} {u.role === 'admin' && <Badge tone="brand">Admin</Badge>}</p><p className="text-sm text-muted">{u.email} · akses {LEVEL[u.access_level] ?? 'Admin'} · daftar {dateID(u.created_at)}</p></div>
            <div className="flex gap-2">
              <Button size="sm" variant="secondary" onClick={() => setGrant(u)}>Beri akses</Button>
              <Button size="sm" variant="ghost" onClick={() => { if (confirm(`Ubah peran ${u.name} menjadi ${u.role === 'admin' ? 'siswa' : 'admin'}?`)) roleM.mutate({ id: u.id, role: u.role === 'admin' ? 'student' : 'admin' }) }}>{u.role === 'admin' ? 'Jadikan siswa' : 'Jadikan admin'}</Button>
            </div>
          </li>
        ))}
      </ul>
      {grant && <GrantModal user={grant} plans={plans.data?.data ?? []} onClose={() => setGrant(null)} onDone={() => { setGrant(null); qc.invalidateQueries({ queryKey: ['admin-users'] }) }} />}
      {create && <CreateUser onClose={() => setCreate(false)} onDone={() => { setCreate(false); qc.invalidateQueries({ queryKey: ['admin-users'] }) }} />}
    </>
  )
}

function GrantModal({ user, plans, onClose, onDone }: any) {
  const [plan, setPlan] = useState('')
  const [ends, setEnds] = useState('')
  const m = useMutation({ mutationFn: () => post('/admin/enrollments', { user_id: user.id, plan_id: +plan, ends_at: ends || null }), onSuccess: onDone })
  return (
    <Modal open onClose={onClose} title={`Beri akses: ${user.name}`}>
      <form onSubmit={e => { e.preventDefault(); m.mutate() }} className="space-y-4">
        <FormErrors error={m.error} />
        <Field label="Paket" htmlFor="pl"><Select id="pl" required value={plan} onChange={e => setPlan(e.target.value)}><option value="">Pilih paket</option>{plans.map((p: any) => <option key={p.id} value={p.id}>{p.name}</option>)}</Select></Field>
        <Field label="Berakhir pada (opsional)" htmlFor="en" hint="Kosongkan untuk memakai durasi bawaan paket."><Input id="en" type="datetime-local" value={ends} onChange={e => setEnds(e.target.value)} /></Field>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onClose}>Batal</Button><Button type="submit" loading={m.isPending}>Beri akses</Button></div>
      </form>
    </Modal>
  )
}

function CreateUser({ onClose, onDone }: any) {
  const [v, setV] = useState({ name: '', email: '', password: '', role: 'student' })
  const m = useMutation({ mutationFn: () => post('/admin/users', v), onSuccess: onDone })
  return (
    <Modal open onClose={onClose} title="Tambah pengguna">
      <form onSubmit={e => { e.preventDefault(); m.mutate() }} className="space-y-4">
        <FormErrors error={m.error} />
        <Field label="Nama" htmlFor="nm"><Input id="nm" required value={v.name} onChange={e => setV({ ...v, name: e.target.value })} /></Field>
        <Field label="Email" htmlFor="em"><Input id="em" type="email" required value={v.email} onChange={e => setV({ ...v, email: e.target.value })} /></Field>
        <Field label="Kata sandi awal" htmlFor="pw" hint="Minimal 8 karakter, huruf dan angka."><Input id="pw" type="password" required value={v.password} onChange={e => setV({ ...v, password: e.target.value })} /></Field>
        <Field label="Peran" htmlFor="rl"><Select id="rl" value={v.role} onChange={e => setV({ ...v, role: e.target.value })}><option value="student">Siswa</option><option value="admin">Admin</option></Select></Field>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onClose}>Batal</Button><Button type="submit" loading={m.isPending}>Buat</Button></div>
      </form>
    </Modal>
  )
}

const payTone = { pending: 'amber', verified: 'ok', rejected: 'danger' } as const
const payLabel = { pending: 'Menunggu', verified: 'Terverifikasi', rejected: 'Ditolak' } as const

function Payments() {
  const qc = useQueryClient()
  const [status, setStatus] = useState('pending')
  const list = useQuery({ queryKey: ['admin-payments', status], queryFn: () => get('/admin/payments' + qs({ status })) })
  const act = useMutation({
    mutationFn: ({ id, a }: { id: number; a: 'verify' | 'reject' }) => post(`/admin/payments/${id}/${a}`),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['admin-payments'] }),
    onError: e => alert(e instanceof ApiError ? e.friendly : 'Gagal'),
  })
  return (
    <>
      <div className="mb-4"><Select aria-label="Status pembayaran" value={status} onChange={e => setStatus(e.target.value)} className="max-w-48"><option value="pending">Menunggu verifikasi</option><option value="verified">Terverifikasi</option><option value="rejected">Ditolak</option><option value="">Semua</option></Select></div>
      {list.isLoading && <Loading />}
      {list.isError && <ErrorState error={list.error} onRetry={() => list.refetch()} />}
      {list.data?.data.length === 0 && <EmptyState title="Tidak ada pembayaran pada status ini" />}
      <ul className="divide-y divide-line rounded-2xl border border-line bg-surface">
        {list.data?.data.map((p: any) => (
          <li key={p.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div>
              <p className="font-semibold">{p.user?.name} · {p.plan?.name} · {rupiah(p.amount)}</p>
              <p className="text-sm text-muted">{p.user?.email} · {p.method} · ref. {p.reference || '—'} · {dateTimeID(p.created_at)}</p>
              {p.note && <p className="text-sm text-muted">Catatan: {p.note}</p>}
            </div>
            <div className="flex items-center gap-2">
              <Badge tone={payTone[p.status as keyof typeof payTone]}>{payLabel[p.status as keyof typeof payLabel]}</Badge>
              {p.status === 'pending' && (<><Button size="sm" onClick={() => { if (confirm('Verifikasi pembayaran dan buka akses siswa?')) act.mutate({ id: p.id, a: 'verify' }) }}>Verifikasi</Button><Button size="sm" variant="secondary" onClick={() => act.mutate({ id: p.id, a: 'reject' })}>Tolak</Button></>)}
            </div>
          </li>
        ))}
      </ul>
    </>
  )
}

function Plans() {
  const qc = useQueryClient()
  const list = useQuery({ queryKey: ['admin-plans'], queryFn: () => get('/admin/plans?per_page=100') })
  const [edit, setEdit] = useState<any>(null)
  return (
    <>
      <div className="mb-4 flex justify-end"><Button onClick={() => setEdit({})}>Tambah paket</Button></div>
      {list.isLoading && <Loading />}
      {list.isError && <ErrorState error={list.error} onRetry={() => list.refetch()} />}
      <ul className="divide-y divide-line rounded-2xl border border-line bg-surface">
        {list.data?.data.map((p: any) => (
          <li key={p.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
            <div><p className="font-semibold">{p.name} <span className="font-normal text-muted">({p.code})</span></p><p className="text-sm text-muted">{rupiah(p.price)} · {p.duration_days ? p.duration_days + ' hari' : 'tanpa batas'} · level {LEVEL[p.access_level]} · {p.is_active ? 'aktif' : 'nonaktif'}</p></div>
            <Button size="sm" variant="secondary" onClick={() => setEdit(p)}>Ubah</Button>
          </li>
        ))}
      </ul>
      {edit && <PlanForm plan={edit} onClose={() => setEdit(null)} onDone={() => { setEdit(null); qc.invalidateQueries({ queryKey: ['admin-plans'] }) }} />}
    </>
  )
}

function PlanForm({ plan, onClose, onDone }: any) {
  const isNew = !plan.id
  const [v, setV] = useState<any>({ code: '', name: '', description: '', price: 0, access_level: 1, is_active: true, ...plan, features: (plan.features ?? []).join('\n'), duration_days: plan.duration_days ?? '' })
  const m = useMutation({
    mutationFn: () => {
      const body = { code: v.code, name: v.name, description: v.description || null, price: +v.price, duration_days: v.duration_days === '' ? null : +v.duration_days, access_level: +v.access_level, features: String(v.features).split('\n').map(s => s.trim()).filter(Boolean), is_active: !!v.is_active, sort: plan.sort ?? 0 }
      return isNew ? post('/admin/plans', body) : put(`/admin/plans/${plan.id}`, body)
    },
    onSuccess: onDone,
  })
  return (
    <Modal open onClose={onClose} title={isNew ? 'Tambah paket' : `Ubah ${plan.name}`}>
      <form onSubmit={e => { e.preventDefault(); m.mutate() }} className="space-y-4">
        <FormErrors error={m.error} />
        <div className="grid grid-cols-2 gap-3">
          <Field label="Kode" htmlFor="cd"><Input id="cd" required value={v.code} onChange={e => setV({ ...v, code: e.target.value })} /></Field>
          <Field label="Nama" htmlFor="nm"><Input id="nm" required value={v.name} onChange={e => setV({ ...v, name: e.target.value })} /></Field>
          <Field label="Harga (Rp)" htmlFor="hg"><Input id="hg" type="number" min={0} value={v.price} onChange={e => setV({ ...v, price: e.target.value as any })} /></Field>
          <Field label="Durasi (hari)" htmlFor="dr" hint="Kosong = tanpa batas"><Input id="dr" type="number" min={1} value={v.duration_days} onChange={e => setV({ ...v, duration_days: e.target.value })} /></Field>
          <Field label="Level akses" htmlFor="lv"><Select id="lv" value={v.access_level} onChange={e => setV({ ...v, access_level: +e.target.value })}>{Object.entries(LEVEL).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <Field label="Status" htmlFor="ac"><Select id="ac" value={v.is_active ? '1' : '0'} onChange={e => setV({ ...v, is_active: e.target.value === '1' })}><option value="1">Aktif</option><option value="0">Nonaktif</option></Select></Field>
        </div>
        <Field label="Deskripsi" htmlFor="ds"><Input id="ds" value={v.description ?? ''} onChange={e => setV({ ...v, description: e.target.value })} /></Field>
        <Field label="Fitur (satu per baris)" htmlFor="ft"><Textarea id="ft" rows={4} value={v.features} onChange={e => setV({ ...v, features: e.target.value })} /></Field>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onClose}>Batal</Button><Button type="submit" loading={m.isPending}>Simpan</Button></div>
      </form>
    </Modal>
  )
}

function Audit() {
  const [page, setPage] = useState(1)
  const list = useQuery({ queryKey: ['audit', page], queryFn: () => get('/admin/audit-logs' + qs({ page })) })
  if (list.isLoading) return <Loading />
  if (list.isError) return <ErrorState error={list.error} onRetry={() => list.refetch()} />
  return (
    <>
      <div className="overflow-x-auto rounded-2xl border border-line bg-surface">
        <table className="w-full text-left text-sm">
          <thead className="bg-sunken text-muted"><tr><th className="p-3">Waktu</th><th className="p-3">Pengguna</th><th className="p-3">Aksi</th><th className="p-3">Objek</th><th className="p-3">IP</th></tr></thead>
          <tbody className="divide-y divide-line">{list.data.data.map((a: any) => (
            <tr key={a.id}><td className="whitespace-nowrap p-3">{dateTimeID(a.created_at)}</td><td className="p-3">{a.user?.email ?? 'sistem'}</td><td className="p-3 font-medium">{a.action}</td><td className="p-3">{a.subject_type ? `${a.subject_type} #${a.subject_id}` : '—'}</td><td className="p-3">{a.ip}</td></tr>
          ))}</tbody>
        </table>
      </div>
      <div className="mt-4 flex items-center justify-center gap-3"><Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>‹</Button><span className="text-sm text-muted">Halaman {page} dari {list.data.last_page}</span><Button variant="secondary" size="sm" disabled={page >= list.data.last_page} onClick={() => setPage(page + 1)}>›</Button></div>
    </>
  )
}
