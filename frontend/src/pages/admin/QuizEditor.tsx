import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { get, post, put, qs } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { LEVEL } from '../../lib/format'
import { Button, Card, ErrorState, Field, Input, Loading, PageHeader, Select, Textarea } from '../../components/ui'
import { FormErrors, StatusControl, useSave } from './shared'

const plain = (h: string) => (h || '').replace(/<[^>]+>/g, '').slice(0, 100)
const blank = { title: '', description: '', topic_id: '', duration_minutes: '', access_level: 0, items: [] as any[], status: 'draft' }

export default function QuizEditor() {
  usePageMeta('Editor kuis', { noindex: true })
  const { id } = useParams()
  const isNew = id === 'baru'
  const nav = useNavigate()
  const qc = useQueryClient()
  const topics = useQuery({ queryKey: ['opt', 'topics'], queryFn: () => get('/admin/topics?per_page=200') })
  const q = useQuery({ queryKey: ['admin-quiz', id], queryFn: () => get(`/admin/quizzes/${id}`), enabled: !isNew })
  const [search, setSearch] = useState('')
  const pool = useQuery({ queryKey: ['pool', search], queryFn: () => get('/admin/questions' + qs({ q: search, per_page: 30 })) })
  const [v, setV] = useState<any>(null)
  const saver = useSave()

  useEffect(() => {
    if (isNew) setV({ ...blank })
    else if (q.data) setV({ ...blank, ...q.data, topic_id: q.data.topic_id ?? '', duration_minutes: q.data.duration_minutes ?? '', description: q.data.description ?? '', items: q.data.items.map((i: any) => ({ question_id: i.question_id, weight: i.weight, label: plain(i.question?.body) })) })
  }, [isNew, q.data])

  if (!isNew && q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  if (!v) return <Loading />
  const set = (k: string, val: any) => setV((s: any) => ({ ...s, [k]: val }))
  const inQuiz = new Set(v.items.map((i: any) => i.question_id))

  const save = async () => {
    const body = {
      title: v.title, description: v.description || null, topic_id: v.topic_id ? +v.topic_id : null,
      duration_minutes: v.duration_minutes === '' ? null : +v.duration_minutes, access_level: +v.access_level,
      items: v.items.map((i: any) => ({ question_id: i.question_id, weight: +i.weight || 1 })),
    }
    const r: any = await saver.run(() => (isNew ? post('/admin/quizzes', body) : put(`/admin/quizzes/${id}`, body)))
    if (r) { qc.invalidateQueries({ queryKey: ['admin', 'quizzes'] }); qc.invalidateQueries({ queryKey: ['admin-quiz', id] }); if (isNew) nav(`/admin/kuis/${r.id}`, { replace: true }) }
  }

  return (
    <>
      <PageHeader title={isNew ? 'Kuis baru' : v.title || 'Kuis'} sub="Durasi dan bobot tiap soal diatur di sini; tidak ada format ujian yang dikunci.">
        <Link to="/admin/kuis" className="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-sunken">‹ Daftar kuis</Link>
      </PageHeader>
      {!isNew && <Card className="mb-4"><p className="mb-2 text-sm font-semibold">Status konten</p><StatusControl type="quizzes" id={+id!} status={v.status} onChanged={s => { set('status', s); qc.invalidateQueries({ queryKey: ['admin', 'quizzes'] }) }} /></Card>}
      <div className="space-y-5">
        <Card className="grid gap-4 sm:grid-cols-2">
          <Field label="Judul" htmlFor="jd"><Input id="jd" value={v.title} onChange={e => set('title', e.target.value)} /></Field>
          <Field label="Bidang (opsional)" htmlFor="bd"><Select id="bd" value={v.topic_id} onChange={e => set('topic_id', e.target.value)}><option value="">—</option>{topics.data?.data.map((t: any) => <option key={t.id} value={t.id}>{t.title}</option>)}</Select></Field>
          <Field label="Durasi (menit)" htmlFor="du" hint="Kosongkan untuk tanpa batas waktu."><Input id="du" type="number" min={1} value={v.duration_minutes} onChange={e => set('duration_minutes', e.target.value)} /></Field>
          <Field label="Level akses" htmlFor="al"><Select id="al" value={v.access_level} onChange={e => set('access_level', e.target.value)}>{Object.entries(LEVEL).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <div className="sm:col-span-2"><Field label="Deskripsi" htmlFor="dk"><Textarea id="dk" value={v.description} onChange={e => set('description', e.target.value)} /></Field></div>
        </Card>

        <div className="grid gap-5 lg:grid-cols-2">
          <Card>
            <p className="font-semibold">Soal dalam kuis ({v.items.length})</p>
            {v.items.length === 0 && <p className="mt-2 text-sm text-muted">Tambahkan soal dari daftar di sebelah.</p>}
            <ul className="mt-2 divide-y divide-line">{v.items.map((it: any, i: number) => (
              <li key={it.question_id} className="flex items-center gap-2 py-2 text-sm">
                <span className="flex-1 truncate">{i + 1}. {it.label || `Soal #${it.question_id}`}</span>
                <label className="flex items-center gap-1 text-xs text-muted">Bobot <Input type="number" min={1} max={100} value={it.weight} onChange={e => set('items', v.items.map((x: any, j: number) => (j === i ? { ...x, weight: e.target.value } : x)))} className="w-16 py-1" aria-label={`Bobot soal ${i + 1}`} /></label>
                <Button size="sm" variant="ghost" aria-label="Naikkan" disabled={i === 0} onClick={() => { const n = [...v.items];[n[i - 1], n[i]] = [n[i], n[i - 1]]; set('items', n) }}>↑</Button>
                <Button size="sm" variant="ghost" onClick={() => set('items', v.items.filter((_: any, j: number) => j !== i))}>Hapus</Button>
              </li>))}</ul>
          </Card>
          <Card>
            <p className="font-semibold">Pilih soal</p>
            <Input aria-label="Cari soal" placeholder="Cari teks soal…" value={search} onChange={e => setSearch(e.target.value)} className="mt-2" />
            <ul className="mt-2 max-h-96 divide-y divide-line overflow-y-auto">{(pool.data?.data ?? []).map((x: any) => (
              <li key={x.id} className="flex items-center gap-2 py-2 text-sm">
                <span className="flex-1">{plain(x.body) || `Soal #${x.id}`}<span className="block text-xs text-muted">{x.topic?.title} · {x.status}</span></span>
                <Button size="sm" variant="secondary" disabled={inQuiz.has(x.id)} onClick={() => set('items', [...v.items, { question_id: x.id, weight: 1, label: plain(x.body) }])}>{inQuiz.has(x.id) ? 'Ditambahkan' : 'Tambah'}</Button>
              </li>))}</ul>
          </Card>
        </div>
        <FormErrors error={saver.error} />
        <div className="sticky bottom-3 flex items-center gap-3 rounded-2xl border border-line bg-surface p-3 shadow-lg"><Button loading={saver.busy} onClick={save}>Simpan</Button>{saver.ok && <span role="status" className="text-sm text-ok">Tersimpan.</span>}</div>
      </div>
    </>
  )
}
