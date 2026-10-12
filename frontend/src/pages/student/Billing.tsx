import { useState, type FormEvent } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { get, post } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { usePageMeta } from '../../lib/meta'
import { dateID, LEVEL, rupiah } from '../../lib/format'
import { Badge, Button, Card, cx, ErrorState, Field, fieldError, Input, Loading, Modal, PageHeader, Select, Textarea } from '../../components/ui'

const payTone = { pending: 'amber', verified: 'ok', rejected: 'danger' } as const
const payLabel = { pending: 'Menunggu verifikasi', verified: 'Terverifikasi', rejected: 'Ditolak' } as const

export default function Billing() {
  usePageMeta('Paket', { noindex: true })
  const qc = useQueryClient()
  const { refresh } = useAuth()
  const q = useQuery({ queryKey: ['billing'], queryFn: () => get('/billing') })
  const [plan, setPlan] = useState<any>(null)
  const [f, setF] = useState({ method: 'transfer', reference: '', note: '' })
  const [done, setDone] = useState(false)

  const pay = useMutation({
    mutationFn: () => post('/payments', { plan_id: plan.id, ...f }),
    onSuccess: () => { setDone(true); qc.invalidateQueries({ queryKey: ['billing'] }); refresh() },
  })

  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const d = q.data
  const pending = new Set(d.payments.filter((p: any) => p.status === 'pending').map((p: any) => p.plan))
  const submit = (e: FormEvent) => { e.preventDefault(); pay.mutate() }
  const close = () => { setPlan(null); setDone(false); pay.reset(); setF({ method: 'transfer', reference: '', note: '' }) }

  return (
    <>
      <PageHeader title="Paket dan akses" sub={`Akses kamu saat ini: ${LEVEL[d.access_level] ?? 'Admin'}.`} />
      <div className="grid gap-4 md:grid-cols-3">
        {d.plans.map((p: any) => {
          const current = d.access_level >= p.access_level
          return (
            <Card key={p.id} className={cx('flex flex-col', p.access_level === d.access_level && 'ring-2 ring-brand')}>
              <h2 className="text-lg font-bold">{p.name}</h2>
              <p className="mt-1 text-2xl font-extrabold">{rupiah(p.price)}</p>
              <p className="text-sm text-muted">{p.duration_days ? `Akses ${p.duration_days} hari` : 'Tanpa batas waktu'}</p>
              <ul className="my-3 flex-1 space-y-1.5 text-sm">{p.features.map((x: string) => <li key={x}>✓ {x}</li>)}</ul>
              {p.price === 0 ? <Badge tone="ok">Sudah aktif</Badge>
                : pending.has(p.name) ? <Badge tone="amber">Menunggu verifikasi</Badge>
                : <Button variant={current && p.access_level === d.access_level ? 'secondary' : 'primary'} onClick={() => setPlan(p)}>{current && p.access_level === d.access_level ? 'Perpanjang' : `Beli ${p.name}`}</Button>}
            </Card>
          )
        })}
      </div>

      <div className="mt-8 grid gap-5 lg:grid-cols-2">
        <Card>
          <h2 className="font-bold">Masa akses</h2>
          <ul className="mt-2 divide-y divide-line text-sm">
            {d.enrollments.map((e: any) => (
              <li key={e.id} className="flex items-center justify-between py-2.5"><span><strong>{e.plan}</strong><span className="block text-xs text-muted">{dateID(e.starts_at)} – {e.ends_at ? dateID(e.ends_at) : 'tanpa batas'}</span></span><Badge tone={e.active ? 'ok' : 'neutral'}>{e.active ? 'Aktif' : 'Tidak aktif'}</Badge></li>
            ))}
          </ul>
        </Card>
        <Card>
          <h2 className="font-bold">Riwayat pembayaran</h2>
          {d.payments.length === 0 ? <p className="mt-2 text-sm text-muted">Belum ada pembayaran.</p> : (
            <ul className="mt-2 divide-y divide-line text-sm">
              {d.payments.map((p: any) => (
                <li key={p.id} className="flex items-center justify-between gap-2 py-2.5"><span><strong>{p.plan}</strong> · {rupiah(p.amount)}<span className="block text-xs text-muted">{dateID(p.created_at)} · ref. {p.reference}</span></span><Badge tone={payTone[p.status as keyof typeof payTone]}>{payLabel[p.status as keyof typeof payLabel]}</Badge></li>
              ))}
            </ul>
          )}
        </Card>
      </div>

      <Modal open={!!plan} onClose={close} title={done ? 'Pembayaran dikirim' : `Beli ${plan?.name ?? ''}`}>
        {done ? (
          <div>
            <p>Terima kasih. Admin akan memverifikasi pembayaranmu secara manual. Akses terbuka otomatis setelah diverifikasi.</p>
            <Button className="mt-4" onClick={close}>Tutup</Button>
          </div>
        ) : (
          <form onSubmit={submit} className="space-y-4">
            <p className="rounded-lg bg-sunken p-3 text-sm">Nominal: <strong>{plan && rupiah(plan.price)}</strong><br /><span className="whitespace-pre-wrap text-muted">{d.payment_instructions}</span></p>
            <Field label="Metode pembayaran" htmlFor="m"><Select id="m" value={f.method} onChange={e => setF({ ...f, method: e.target.value })}><option value="transfer">Transfer bank</option><option value="qris">QRIS</option><option value="ewallet">E-wallet</option><option value="lainnya">Lainnya</option></Select></Field>
            <Field label="Referensi pembayaran" htmlFor="r" hint="Nomor referensi / nama pengirim pada bukti bayar." error={fieldError(pay.error, 'reference')}><Input id="r" required value={f.reference} onChange={e => setF({ ...f, reference: e.target.value })} /></Field>
            <Field label="Catatan (opsional)" htmlFor="c"><Textarea id="c" rows={2} value={f.note} onChange={e => setF({ ...f, note: e.target.value })} /></Field>
            {pay.isError && !fieldError(pay.error, 'reference') && <p role="alert" className="text-sm text-danger">{(pay.error as any)?.friendly}</p>}
            <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={close}>Batal</Button><Button type="submit" loading={pay.isPending}>Kirim pembayaran</Button></div>
          </form>
        )}
      </Modal>
    </>
  )
}
