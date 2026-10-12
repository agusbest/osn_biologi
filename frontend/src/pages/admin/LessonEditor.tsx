import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { get, post, put } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY } from '../../lib/format'
import { Button, Card, ErrorState, Field, Input, Loading, PageHeader, Select } from '../../components/ui'
import { Editor } from '../../components/Editor'
import { FormErrors, StatusControl, useSave } from './shared'

const SECTIONS: [string, string][] = [
  ['objectives', 'Target pembelajaran'], ['explanation', 'Penjelasan sederhana'], ['key_concepts', 'Konsep wajib dipahami'],
  ['osn_thinking', 'Cara berpikir ala OSN'], ['worked_example', 'Contoh soal'], ['practice', 'Latihan mandiri'], ['summary', 'Ringkasan cepat'],
]
const blank = { module_id: '', title: '', difficulty: 'fondasi', est_minutes: 10, objectives: '', explanation: '', key_concepts: '', osn_thinking: '', worked_example: '', practice: '', summary: '', glossary: [], reference_list: [], status: 'draft' }

export default function LessonEditor() {
  usePageMeta('Editor materi', { noindex: true })
  const { id } = useParams()
  const isNew = id === 'baru'
  const nav = useNavigate()
  const qc = useQueryClient()
  const modules = useQuery({ queryKey: ['opt', 'modules'], queryFn: () => get('/admin/modules?per_page=200') })
  const q = useQuery({ queryKey: ['admin-lesson', id], queryFn: () => get(`/admin/lessons/${id}`), enabled: !isNew })
  const [v, setV] = useState<any>(null)
  const saver = useSave()

  useEffect(() => { if (isNew) setV({ ...blank }); else if (q.data) setV({ ...blank, ...q.data, glossary: q.data.glossary ?? [], reference_list: q.data.reference_list ?? [] }) }, [isNew, q.data])

  if (!isNew && q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  if (!v) return <Loading />
  const set = (k: string, val: any) => setV((s: any) => ({ ...s, [k]: val }))

  const save = async () => {
    const body: any = { module_id: +v.module_id, title: v.title, difficulty: v.difficulty, est_minutes: +v.est_minutes, glossary: v.glossary, reference_list: v.reference_list.map((r: any) => ({ text: r.text, url: r.url || null })) }
    SECTIONS.forEach(([k]) => { body[k] = v[k] || null })
    const r: any = await saver.run(() => (isNew ? post('/admin/lessons', body) : put(`/admin/lessons/${id}`, body)))
    if (r) {
      qc.invalidateQueries({ queryKey: ['admin', 'lessons'] }); qc.invalidateQueries({ queryKey: ['admin-lesson', id] })
      if (isNew) nav(`/admin/materi/${r.id}`, { replace: true })
    }
  }

  return (
    <>
      <PageHeader title={isNew ? 'Materi baru' : v.title || 'Materi'} sub="Gunakan $...$ untuk rumus sebaris dan $$...$$ untuk rumus blok (KaTeX).">
        <Link to="/admin/materi" className="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-sunken">‹ Daftar materi</Link>
        {!isNew && <Link to={`/app/materi/${id}`} className="rounded-lg border border-line px-3 py-2 text-sm font-semibold hover:bg-sunken">Pratinjau sebagai siswa</Link>}
      </PageHeader>

      {!isNew && <Card className="mb-4"><p className="mb-2 text-sm font-semibold">Status konten</p><StatusControl type="lessons" id={+id!} status={v.status} onChanged={s => { set('status', s); qc.invalidateQueries({ queryKey: ['admin', 'lessons'] }) }} /></Card>}

      <div className="space-y-5">
        <Card className="grid gap-4 sm:grid-cols-2">
          <Field label="Modul" htmlFor="md"><Select id="md" value={v.module_id} onChange={e => set('module_id', e.target.value)}><option value="">Pilih modul</option>{modules.data?.data.map((m: any) => <option key={m.id} value={m.id}>{m.topic?.title} › {m.title}</option>)}</Select></Field>
          <Field label="Judul materi" htmlFor="tt"><Input id="tt" value={v.title} onChange={e => set('title', e.target.value)} /></Field>
          <Field label="Tingkat kesulitan" htmlFor="df"><Select id="df" value={v.difficulty} onChange={e => set('difficulty', e.target.value)}>{Object.entries(DIFFICULTY).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          <Field label="Estimasi waktu baca (menit)" htmlFor="em"><Input id="em" type="number" min={1} value={v.est_minutes} onChange={e => set('est_minutes', e.target.value)} /></Field>
        </Card>

        {SECTIONS.map(([k, label]) => (
          <Card key={k}><p className="mb-2 font-semibold">{label}</p><Editor label={label} value={v[k] ?? ''} onChange={h => set(k, h)} lessonId={isNew ? undefined : +id!} /></Card>
        ))}

        <Card>
          <div className="mb-2 flex items-center justify-between"><p className="font-semibold">Glosarium</p><Button size="sm" variant="secondary" onClick={() => set('glossary', [...v.glossary, { term: '', definition: '' }])}>Tambah istilah</Button></div>
          {v.glossary.length === 0 && <p className="text-sm text-muted">Belum ada istilah.</p>}
          <div className="space-y-2">{v.glossary.map((g: any, i: number) => (
            <div key={i} className="grid gap-2 sm:grid-cols-[12rem_1fr_auto]">
              <Input aria-label="Istilah" placeholder="Istilah" value={g.term} onChange={e => set('glossary', v.glossary.map((x: any, j: number) => (j === i ? { ...x, term: e.target.value } : x)))} />
              <Input aria-label="Definisi" placeholder="Definisi" value={g.definition} onChange={e => set('glossary', v.glossary.map((x: any, j: number) => (j === i ? { ...x, definition: e.target.value } : x)))} />
              <Button variant="ghost" size="sm" onClick={() => set('glossary', v.glossary.filter((_: any, j: number) => j !== i))}>Hapus</Button>
            </div>))}</div>
        </Card>

        <Card>
          <div className="mb-2 flex items-center justify-between"><p className="font-semibold">Referensi</p><Button size="sm" variant="secondary" onClick={() => set('reference_list', [...v.reference_list, { text: '', url: '' }])}>Tambah referensi</Button></div>
          <p className="mb-2 text-xs text-muted">Cantumkan sebagai rujukan saja; jangan menyalin teks, gambar, atau soal dari sumber berhak cipta.</p>
          <div className="space-y-2">{v.reference_list.map((r: any, i: number) => (
            <div key={i} className="grid gap-2 sm:grid-cols-[1fr_16rem_auto]">
              <Input aria-label="Teks referensi" placeholder="Judul / keterangan" value={r.text} onChange={e => set('reference_list', v.reference_list.map((x: any, j: number) => (j === i ? { ...x, text: e.target.value } : x)))} />
              <Input aria-label="URL" placeholder="https://… (opsional)" value={r.url ?? ''} onChange={e => set('reference_list', v.reference_list.map((x: any, j: number) => (j === i ? { ...x, url: e.target.value } : x)))} />
              <Button variant="ghost" size="sm" onClick={() => set('reference_list', v.reference_list.filter((_: any, j: number) => j !== i))}>Hapus</Button>
            </div>))}</div>
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
