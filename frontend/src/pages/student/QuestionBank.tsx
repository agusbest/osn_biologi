import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ApiError, del, get, post, qs } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY, QTYPE } from '../../lib/format'
import { Badge, Button, Card, EmptyState, ErrorState, Loading, PageHeader, Select } from '../../components/ui'

const resultTone: Record<string, 'neutral' | 'ok' | 'danger'> = { belum: 'neutral', benar: 'ok', salah: 'danger' }

export default function QuestionBank() {
  usePageMeta('Bank soal', { noindex: true })
  const [sp, setSp] = useSearchParams()
  const nav = useNavigate()
  const qc = useQueryClient()
  const [count, setCount] = useState(10)
  const [startErr, setStartErr] = useState('')
  const f = { topic_id: sp.get('topic_id') || '', module_id: sp.get('module_id') || '', difficulty: sp.get('difficulty') || '', type: sp.get('type') || '', status: sp.get('status') || '' }
  const page = Number(sp.get('page') || 1)
  const setF = (k: string, v: string) => { const n = new URLSearchParams(sp); v ? n.set(k, v) : n.delete(k); n.delete('page'); setSp(n) }

  const topics = useQuery({ queryKey: ['curriculum'], queryFn: () => get('/curriculum') })
  const quizzes = useQuery({ queryKey: ['quizzes'], queryFn: () => get('/quizzes') })
  const list = useQuery({ queryKey: ['questions', f, page], queryFn: () => get('/questions' + qs({ ...f, page })) })

  const start = useMutation({
    mutationFn: (body: any) => post('/attempts', body),
    onSuccess: r => nav(`/app/latihan/sesi/${r.id}`),
    onError: e => setStartErr(e instanceof ApiError ? e.friendly : 'Gagal memulai latihan.'),
  })
  const mark = useMutation({
    mutationFn: ({ id, kind, on }: { id: number; kind: string; on: boolean }) => (on ? del(`/questions/${id}/bookmark/${kind}`) : post(`/questions/${id}/bookmark`, { kind })),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['questions'] }),
  })

  const rows = list.data?.data ?? []

  return (
    <>
      <PageHeader title="Bank soal" sub="Pilih topik dan tingkat, lalu mulai sesi latihan. Kunci dan pembahasan terbuka setelah kamu mengirim jawaban." />

      {quizzes.data?.quizzes?.length > 0 && (
        <section aria-label="Kuis" className="mb-6">
          <h2 className="mb-2 font-bold">Kuis</h2>
          <div className="grid gap-3 sm:grid-cols-2">
            {quizzes.data.quizzes.map((z: any) => (
              <Card key={z.id} className="flex items-center justify-between gap-3">
                <div><p className="font-semibold">{z.title}</p><p className="text-sm text-muted">{z.question_count} soal{z.duration_minutes ? ` · ${z.duration_minutes} menit` : ''}</p></div>
                {z.locked ? <Link to="/app/paket" className="text-sm font-semibold text-amber underline">Terkunci</Link> : <Button size="sm" loading={start.isPending} onClick={() => { setStartErr(''); start.mutate({ quiz_id: z.id }) }}>Mulai</Button>}
              </Card>
            ))}
          </div>
        </section>
      )}

      <Card>
        <h2 className="font-bold">Filter</h2>
        <div className="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <Select aria-label="Topik" value={f.topic_id} onChange={e => setF('topic_id', e.target.value)}>
            <option value="">Semua topik</option>
            {topics.data?.topics.map((t: any) => <option key={t.id} value={t.id}>{t.title}</option>)}
          </Select>
          <Select aria-label="Kesulitan" value={f.difficulty} onChange={e => setF('difficulty', e.target.value)}>
            <option value="">Semua tingkat</option>
            {Object.entries(DIFFICULTY).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </Select>
          <Select aria-label="Jenis soal" value={f.type} onChange={e => setF('type', e.target.value)}>
            <option value="">Semua jenis</option>
            {Object.entries(QTYPE).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </Select>
          <Select aria-label="Status pengerjaan" value={f.status} onChange={e => setF('status', e.target.value)}>
            <option value="">Semua status</option>
            <option value="belum">Belum dikerjakan</option>
            <option value="benar">Terakhir benar</option>
            <option value="salah">Terakhir salah</option>
            <option value="favorit">Favorit</option>
            <option value="sulit">Masih sulit</option>
          </Select>
          <div className="flex gap-2">
            <Select aria-label="Jumlah soal" value={count} onChange={e => setCount(+e.target.value)} className="w-24">
              {[5, 10, 15, 20, 30].map(n => <option key={n} value={n}>{n} soal</option>)}
            </Select>
            <Button loading={start.isPending} onClick={() => { setStartErr(''); start.mutate({ ...Object.fromEntries(Object.entries(f).filter(([, v]) => v)), count }) }}>Mulai latihan</Button>
          </div>
        </div>
        {startErr && <p role="alert" className="mt-3 text-sm text-danger">{startErr}</p>}
      </Card>

      <div className="mt-5">
        {list.isLoading && <Loading />}
        {list.isError && <ErrorState error={list.error} onRetry={() => list.refetch()} />}
        {list.data && rows.length === 0 && <EmptyState title="Tidak ada soal yang cocok">Ubah filter, atau soal untuk topik ini belum diterbitkan.</EmptyState>}
        <ul className="space-y-3">
          {rows.map((x: any) => (
            <li key={x.id} className="rounded-2xl border border-line bg-surface p-4">
              <div className="flex flex-wrap items-center gap-2 text-xs">
                <Badge tone="brand">{DIFFICULTY[x.difficulty]}</Badge><Badge>{QTYPE[x.type]}</Badge><span className="text-muted">{x.topic}</span>
                {!x.locked && <Badge tone={resultTone[x.last_result]}>{x.last_result === 'belum' ? 'Belum dikerjakan' : x.last_result === 'benar' ? 'Terakhir benar' : 'Terakhir salah'}</Badge>}
              </div>
              {x.locked ? (
                <p className="mt-2 text-muted">🔒 Soal ini ada di paket yang lebih tinggi. <Link className="font-semibold text-amber underline" to="/app/paket">Lihat paket</Link></p>
              ) : (
                <>
                  <p className="mt-2 line-clamp-2">{x.excerpt}</p>
                  <div className="mt-3 flex flex-wrap gap-2">
                    <Button size="sm" onClick={() => { setStartErr(''); start.mutate({ question_ids: [x.id] }) }}>Kerjakan</Button>
                    <Button size="sm" variant="ghost" aria-pressed={x.favorite} onClick={() => mark.mutate({ id: x.id, kind: 'favorite', on: x.favorite })}>{x.favorite ? '★ Favorit' : '☆ Favorit'}</Button>
                    <Button size="sm" variant="ghost" aria-pressed={x.hard} onClick={() => mark.mutate({ id: x.id, kind: 'hard', on: x.hard })}>{x.hard ? '⚑ Masih sulit' : '⚐ Tandai sulit'}</Button>
                  </div>
                </>
              )}
            </li>
          ))}
        </ul>
        {list.data && list.data.last_page > 1 && (
          <div className="mt-5 flex items-center justify-center gap-3">
            <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => { const n = new URLSearchParams(sp); n.set('page', String(page - 1)); setSp(n) }}>‹ Sebelumnya</Button>
            <span className="text-sm text-muted">Halaman {page} dari {list.data.last_page}</span>
            <Button variant="secondary" size="sm" disabled={page >= list.data.last_page} onClick={() => { const n = new URLSearchParams(sp); n.set('page', String(page + 1)); setSp(n) }}>Berikutnya ›</Button>
          </div>
        )}
      </div>
    </>
  )
}
