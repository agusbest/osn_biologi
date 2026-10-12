import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { get, post, put } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY, LEVEL, QTYPE } from '../../lib/format'
import { Button, Card, ErrorState, Field, Input, Loading, PageHeader, Select } from '../../components/ui'
import { Editor } from '../../components/Editor'
import { FormErrors, StatusControl, useSave } from './shared'

const PARTS: [string, string][] = [
  ['correct_answer', '1. Jawaban benar'], ['concept_tested', '2. Konsep yang diuji'], ['solution_steps', '3. Langkah penyelesaian'],
  ['why_others_wrong', '4. Alasan opsi lain salah'], ['practical_trick', '5. Trik praktis'], ['common_mistakes', '6. Kesalahan umum'], ['similar_questions', '7. Soal sejenis'],
]
const TF = [{ label: 'A', text: 'Benar', is_correct: true }, { label: 'B', text: 'Salah', is_correct: false }]
const mcqDefault = ['A', 'B', 'C', 'D'].map((l, i) => ({ label: l, text: '', is_correct: i === 0 }))
const blank = { topic_id: '', module_id: '', type: 'mcq', difficulty: 'fondasi', access_level: 0, body: '', options: mcqDefault, explanation: {}, tags: '', status: 'draft' }

export default function QuestionEditor() {
  usePageMeta('Editor soal', { noindex: true })
  const { id } = useParams()
  const isNew = id === 'baru'
  const nav = useNavigate()
  const qc = useQueryClient()
  const topics = useQuery({ queryKey: ['opt', 'topics'], queryFn: () => get('/admin/topics?per_page=200') })
  const modules = useQuery({ queryKey: ['opt', 'modules'], queryFn: () => get('/admin/modules?per_page=200') })
  const q = useQuery({ queryKey: ['admin-question', id], queryFn: () => get(`/admin/questions/${id}`), enabled: !isNew })
  const [v, setV] = useState<any>(null)
  const saver = useSave()

  useEffect(() => {
    if (isNew) setV({ ...blank })
    else if (q.data) setV({ ...blank, ...q.data, module_id: q.data.module_id ?? '', explanation: q.data.explanation ?? {}, tags: (q.data.tags ?? []).map((t: any) => t.tag).filter((t: string) => !t.startsWith('seed:')).join(', '), options: q.data.options?.length ? q.data.options : [] })
  }, [isNew, q.data])

  if (!isNew && q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  if (!v) return <Loading />
  const set = (k: string, val: any) => setV((s: any) => ({ ...s, [k]: val }))

  const changeType = (t: string) => setV((s: any) => ({ ...s, type: t, options: t === 'essay' ? [] : t === 'truefalse' ? TF : s.options.length > 2 || s.type === 'mcq' ? s.options : mcqDefault }))
  const setOpt = (i: number, patch: any) => set('options', v.options.map((o: any, j: number) => (j === i ? { ...o, ...patch } : o)))
  const setCorrect = (i: number) => set('options', v.options.map((o: any, j: number) => ({ ...o, is_correct: j === i })))
  const addOpt = () => set('options', [...v.options, { label: String.fromCharCode(65 + v.options.length), text: '', is_correct: false }])
  const rmOpt = (i: number) => set('options', v.options.filter((_: any, j: number) => j !== i).map((o: any, j: number) => ({ ...o, label: String.fromCharCode(65 + j) })))

  const save = async () => {
    const body: any = {
      topic_id: +v.topic_id, module_id: v.module_id ? +v.module_id : null, type: v.type, difficulty: v.difficulty, access_level: +v.access_level,
      body: v.body, explanation: v.explanation, tags: v.tags.split(',').map((t: string) => t.trim()).filter(Boolean),
    }
    if (v.type !== 'essay') body.options = v.options.map((o: any) => ({ id: o.id, label: o.label, text: o.text, is_correct: !!o.is_correct }))
    const r: any = await saver.run(() => (isNew ? post('/admin/questions', body) : put(`/admin/questions/${id}`, body)))
    if (r) {
      qc.invalidateQueries({ queryKey: ['admin', 'questions'] }); qc.invalidateQueries({ queryKey: ['admin-question', id] })
      if (isNew) nav(`/admin/soal/${r.id}`, { replace: true })
    }
  }
  const mods = (modules.data?.data ?? []).filter((m: any) => !v.topic_id || m.topic_id === +v.topic_id)

  return (
    <>
      <PageHeader title={isNew ? 'Soal baru' : `Soal #${id}`} sub="Soal tidak bisa diterbitkan sebelum kunci jawaban dan ketujuh bagian pembahasan terisi.">
        <Link to="/admin/soal" className="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-sunken">‹ Daftar soal</Link>
      </PageHeader>
      {!isNew && <Card className="mb-4"><p className="mb-2 text-sm font-semibold">Status konten</p><StatusControl type="questions" id={+id!} status={v.status} onChanged={s => { set('status', s); qc.invalidateQueries({ queryKey: ['admin', 'questions'] }) }} /></Card>}

      <div className="space-y-5">
        <Card className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Field label="Bidang" htmlFor="tp"><Select id="tp" value={v.topic_id} onChange={e => setV({ ...v, topic_id: e.target.value, module_id: '' })}><option value="">Pilih bidang</option>{topics.data?.data.map((t: any) => <option key={t.id} value={t.id}>{t.title}</option>)}</Select></Field>
          <Field label="Modul terkait (opsional)" htmlFor="md" hint="Dipakai untuk statistik materi tersulit."><Select id="md" value={v.module_id} onChange={e => set('module_id', e.target.value)}><option value="">—</option>{mods.map((m: any) => <option key={m.id} value={m.id}>{m.title}</option>)}</Select></Field>
          <Field label="Jenis soal" htmlFor="ty"><Select id="ty" value={v.type} onChange={e => changeType(e.target.value)}>{Object.entries(QTYPE).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <Field label="Kesulitan" htmlFor="df"><Select id="df" value={v.difficulty} onChange={e => set('difficulty', e.target.value)}>{Object.entries(DIFFICULTY).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <Field label="Level akses" htmlFor="al"><Select id="al" value={v.access_level} onChange={e => set('access_level', e.target.value)}>{Object.entries(LEVEL).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <Field label="Tag kompetensi" htmlFor="tg" hint="Pisahkan dengan koma."><Input id="tg" value={v.tags} onChange={e => set('tags', e.target.value)} placeholder="mis. hardy-weinberg, perhitungan" /></Field>
        </Card>

        <Card><p className="mb-2 font-semibold">Teks soal</p><Editor label="Teks soal" value={v.body} onChange={h => set('body', h)} /></Card>

        {v.type !== 'essay' && (
          <Card>
            <p className="mb-2 font-semibold">Opsi jawaban</p>
            <fieldset className="space-y-2">
              <legend className="sr-only">Pilih opsi yang menjadi kunci</legend>
              {v.options.map((o: any, i: number) => (
                <div key={i} className="flex items-center gap-2">
                  <input type="radio" name="kunci" checked={!!o.is_correct} onChange={() => setCorrect(i)} aria-label={`Jadikan opsi ${o.label} sebagai kunci`} className="accent-[var(--brand)]" />
                  <span className="w-6 font-bold">{o.label}</span>
                  <Input aria-label={`Teks opsi ${o.label}`} value={o.text} readOnly={v.type === 'truefalse'} onChange={e => setOpt(i, { text: e.target.value })} placeholder="Teks opsi (boleh memakai $rumus$)" />
                  {v.type === 'mcq' && v.options.length > 2 && <Button variant="ghost" size="sm" onClick={() => rmOpt(i)}>Hapus</Button>}
                </div>
              ))}
            </fieldset>
            {v.type === 'mcq' && v.options.length < 6 && <Button variant="secondary" size="sm" className="mt-3" onClick={addOpt}>Tambah opsi</Button>}
            <p className="mt-2 text-xs text-muted">Tombol bulat menandai kunci jawaban. Kunci tidak pernah dikirim ke siswa sebelum mereka mengirim jawaban.</p>
          </Card>
        )}

        <Card>
          <p className="font-semibold">Pembahasan (tujuh bagian)</p>
          <p className="mb-3 text-xs text-muted">{v.type === 'essay' ? 'Untuk uraian, isi bagian 1 dengan jawaban model; siswa membandingkannya dengan jawaban mereka.' : 'Semua bagian wajib terisi untuk bisa diterbitkan.'}</p>
          <div className="space-y-4">{PARTS.map(([k, l]) => (
            <div key={k}><p className="mb-1 text-sm font-semibold">{l}</p><Editor minimal label={l} value={v.explanation[k] ?? ''} onChange={h => set('explanation', { ...v.explanation, [k]: h })} /></div>
          ))}</div>
        </Card>

        <FormErrors error={saver.error} />
        <div className="sticky bottom-3 flex items-center gap-3 rounded-2xl border border-line bg-surface p-3 shadow-lg">
          <Button loading={saver.busy} onClick={save}>Simpan</Button>
          {saver.ok && <span role="status" className="text-sm text-ok">Tersimpan.</span>}
        </div>
      </div>
    </>
  )
}
