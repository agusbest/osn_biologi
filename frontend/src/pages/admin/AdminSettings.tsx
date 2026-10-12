import { useEffect, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { get, put } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { Button, Card, ErrorState, Field, Input, Loading, PageHeader, Select, Textarea } from '../../components/ui'
import { FormErrors, useSave } from './shared'

export default function AdminSettings() {
  usePageMeta('Pengaturan', { noindex: true })
  const qc = useQueryClient()
  const q = useQuery({ queryKey: ['admin-settings'], queryFn: () => get('/admin/settings') })
  const lessons = useQuery({ queryKey: ['opt', 'lessons-pub'], queryFn: () => get('/admin/lessons?status=published&per_page=200') })
  const [v, setV] = useState<any>(null)
  const saver = useSave()
  useEffect(() => { if (q.data) setV(q.data) }, [q.data])
  if (q.isLoading || !v) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const set = (k: string, val: any) => setV((s: any) => ({ ...s, [k]: val }))
  const rules = v.recommendation_rules
  const setTier = (i: number, patch: any) => set('recommendation_rules', { ...rules, tiers: rules.tiers.map((t: any, j: number) => (j === i ? { ...t, ...patch } : t)) })

  const save = async () => {
    const body = {
      site_name: v.site_name, practice_default_count: +v.practice_default_count, practice_max_count: +v.practice_max_count,
      daily_target_min: +v.daily_target_min, daily_target_max: +v.daily_target_max, autosave_seconds: +v.autosave_seconds,
      demo_lesson_id: v.demo_lesson_id ? +v.demo_lesson_id : null, payment_instructions: v.payment_instructions,
      recommendation_rules: { min_answers: +rules.min_answers, tiers: rules.tiers.map((t: any) => ({ ...t, below: +t.below, actions: Array.isArray(t.actions) ? t.actions : String(t.actions).split('\n').map((s: string) => s.trim()).filter(Boolean) })) },
    }
    const r = await saver.run(() => put('/admin/settings', body))
    if (r) qc.invalidateQueries({ queryKey: ['admin-settings'] })
  }

  return (
    <>
      <PageHeader title="Pengaturan" sub="Aturan rekomendasi, jumlah soal, dan batas target belajar dapat diubah di sini tanpa mengubah kode." />
      <div className="space-y-5">
        <Card className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Field label="Nama situs" htmlFor="sn"><Input id="sn" value={v.site_name} onChange={e => set('site_name', e.target.value)} /></Field>
          <Field label="Jumlah soal latihan (bawaan)" htmlFor="pd"><Input id="pd" type="number" min={1} value={v.practice_default_count} onChange={e => set('practice_default_count', e.target.value)} /></Field>
          <Field label="Jumlah soal latihan (maksimum)" htmlFor="pm"><Input id="pm" type="number" min={1} value={v.practice_max_count} onChange={e => set('practice_max_count', e.target.value)} /></Field>
          <Field label="Target belajar harian minimum (menit)" htmlFor="dn"><Input id="dn" type="number" min={5} value={v.daily_target_min} onChange={e => set('daily_target_min', e.target.value)} /></Field>
          <Field label="Target belajar harian maksimum (menit)" htmlFor="dx"><Input id="dx" type="number" min={5} value={v.daily_target_max} onChange={e => set('daily_target_max', e.target.value)} /></Field>
          <Field label="Interval autosave jawaban (detik)" htmlFor="as"><Input id="as" type="number" min={5} value={v.autosave_seconds} onChange={e => set('autosave_seconds', e.target.value)} /></Field>
          <Field label="Materi demo di landing page" htmlFor="dm" hint="Hanya materi terbit di modul Free yang bisa tampil."><Select id="dm" value={v.demo_lesson_id ?? ''} onChange={e => set('demo_lesson_id', e.target.value)}><option value="">Otomatis (materi gratis pertama)</option>{lessons.data?.data.map((l: any) => <option key={l.id} value={l.id}>{l.title}</option>)}</Select></Field>
        </Card>

        <Card>
          <h2 className="font-bold">Aturan rekomendasi per topik</h2>
          <p className="mb-3 text-sm text-muted">Akurasi di bawah batas pertama masuk tingkat pertama, dan seterusnya. Topik baru dianalisis setelah siswa menjawab minimal sejumlah soal.</p>
          <Field label="Minimal soal dijawab sebelum dianalisis" htmlFor="ma"><Input id="ma" type="number" min={1} className="max-w-32" value={rules.min_answers} onChange={e => set('recommendation_rules', { ...rules, min_answers: e.target.value })} /></Field>
          <div className="mt-4 space-y-4">{rules.tiers.map((t: any, i: number) => (
            <div key={i} className="grid gap-3 rounded-xl border border-line p-3 sm:grid-cols-[8rem_1fr]">
              <Field label="Akurasi di bawah (%)" htmlFor={`b${i}`}><Input id={`b${i}`} type="number" min={1} max={101} value={t.below} onChange={e => setTier(i, { below: e.target.value })} /></Field>
              <Field label="Label" htmlFor={`l${i}`}><Input id={`l${i}`} value={t.label} onChange={e => setTier(i, { label: e.target.value })} /></Field>
              <div className="sm:col-span-2"><Field label="Tindakan yang disarankan (satu per baris)" htmlFor={`a${i}`}><Textarea id={`a${i}`} rows={2} value={Array.isArray(t.actions) ? t.actions.join('\n') : t.actions} onChange={e => setTier(i, { actions: e.target.value })} /></Field></div>
            </div>))}</div>
        </Card>

        <Card><Field label="Instruksi pembayaran manual (tampil ke siswa)" htmlFor="pi"><Textarea id="pi" rows={4} value={v.payment_instructions ?? ''} onChange={e => set('payment_instructions', e.target.value)} /></Field></Card>

        <FormErrors error={saver.error} />
        <div className="flex items-center gap-3"><Button loading={saver.busy} onClick={save}>Simpan pengaturan</Button>{saver.ok && <span role="status" className="text-sm text-ok">Tersimpan.</span>}</div>
      </div>
    </>
  )
}
