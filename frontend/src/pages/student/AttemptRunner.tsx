import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { ApiError, get, post, put } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { mmss, QTYPE, DIFFICULTY } from '../../lib/format'
import { Badge, Button, cx, ErrorState, Loading, Modal, Textarea } from '../../components/ui'
import { MathText, RichText } from '../../components/RichText'

type Ans = { option_id: number | null; answer_text: string | null }

export default function AttemptRunner() {
  const { id } = useParams()
  const nav = useNavigate()
  const qc = useQueryClient()
  usePageMeta('Latihan', { noindex: true })
  const q = useQuery({ queryKey: ['attempt', id], queryFn: () => get(`/attempts/${id}`), staleTime: Infinity, refetchOnMount: 'always' })

  const [idx, setIdx] = useState(0)
  const [answers, setAnswers] = useState<Record<number, Ans>>({})
  const [dirty, setDirty] = useState(false)
  const [saveState, setSaveState] = useState<'ok' | 'saving' | 'offline'>('ok')
  const [confirm, setConfirm] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [err, setErr] = useState('')
  const [left, setLeft] = useState<number | null>(null)
  const ready = useRef(false)
  const lsKey = `osn.attempt.${id}`

  // Inisialisasi dari server; jika ada cadangan lokal yang lebih baru (koneksi putus), pakai cadangan.
  useEffect(() => {
    if (!q.data || q.data.status === 'submitted' || ready.current) return
    const init: Record<number, Ans> = {}
    q.data.items.forEach((it: any) => { init[it.question.id] = { option_id: it.answer.option_id, answer_text: it.answer.answer_text } })
    try {
      const raw = localStorage.getItem(lsKey)
      if (raw) { const local = JSON.parse(raw); Object.assign(init, local); setDirty(true) }
    } catch { /* ignore */ }
    setAnswers(init)
    ready.current = true
  }, [q.data, lsKey])

  const payload = useCallback(() => Object.entries(answers).map(([qid, a]) => ({ question_id: +qid, option_id: a.option_id, answer_text: a.answer_text })), [answers])

  const save = useCallback(async () => {
    if (!dirty || !ready.current) return
    setSaveState('saving')
    try { await put(`/attempts/${id}/answers`, { answers: payload() }); setDirty(false); setSaveState('ok'); try { localStorage.removeItem(lsKey) } catch { /* ignore */ } }
    catch (e) { setSaveState(e instanceof ApiError && e.status === 0 ? 'offline' : 'ok') }
  }, [dirty, id, payload, lsKey])

  // Cadangan lokal tiap perubahan + autosave berkala ke server.
  useEffect(() => { if (dirty) try { localStorage.setItem(lsKey, JSON.stringify(answers)) } catch { /* ignore */ } }, [answers, dirty, lsKey])
  const interval = (q.data?.autosave_seconds ?? 15) * 1000
  useEffect(() => { const t = setInterval(save, interval); return () => clearInterval(t) }, [save, interval])
  useEffect(() => {
    const onHide = () => document.visibilityState === 'hidden' && save()
    document.addEventListener('visibilitychange', onHide)
    window.addEventListener('online', save)
    return () => { document.removeEventListener('visibilitychange', onHide); window.removeEventListener('online', save) }
  }, [save])

  const submit = useCallback(async () => {
    setSubmitting(true); setErr('')
    try {
      const r = await post(`/attempts/${id}/submit`, { answers: payload() })
      try { localStorage.removeItem(lsKey) } catch { /* ignore */ }
      qc.setQueryData(['results', id], r)
      qc.invalidateQueries({ queryKey: ['dashboard'] }); qc.invalidateQueries({ queryKey: ['evaluation'] }); qc.invalidateQueries({ queryKey: ['questions'] })
      nav(`/app/latihan/hasil/${id}`, { replace: true })
    } catch (e) {
      setErr(e instanceof ApiError ? e.friendly : 'Gagal mengirim.'); setSubmitting(false); setConfirm(false)
    }
  }, [id, payload, nav, qc, lsKey])

  // Timer: sisa waktu dihitung dari jam server (selisih saat dimuat), bukan jam perangkat.
  const offset = useMemo(() => (q.data?.server_time ? new Date(q.data.server_time).getTime() - Date.now() : 0), [q.data?.server_time])
  const autoSubmitted = useRef(false)
  useEffect(() => {
    if (!q.data?.deadline_at || q.data.status === 'submitted') return
    const tick = () => {
      const s = Math.round((new Date(q.data.deadline_at).getTime() - (Date.now() + offset)) / 1000)
      setLeft(s)
      if (s <= 0 && !autoSubmitted.current && ready.current) { autoSubmitted.current = true; submit() }
    }
    tick(); const t = setInterval(tick, 1000); return () => clearInterval(t)
  }, [q.data, offset, submit])

  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  if (q.data.status === 'submitted') return <Navigate to={`/app/latihan/hasil/${id}`} replace />

  const items = q.data.items
  const cur = items[idx]
  const a = answers[cur.question.id] ?? { option_id: null, answer_text: null }
  const set = (patch: Partial<Ans>) => { setAnswers(s => ({ ...s, [cur.question.id]: { ...a, ...patch } })); setDirty(true) }
  const answered = (qid: number, type: string) => { const x = answers[qid]; return type === 'essay' ? !!x?.answer_text?.trim() : x?.option_id != null }
  const unanswered = items.filter((it: any) => !answered(it.question.id, it.question.type)).length

  return (
    <div>
      <div className="sticky top-0 z-20 -mx-4 flex flex-wrap items-center justify-between gap-2 border-b border-line bg-paper/95 px-4 py-2 backdrop-blur md:static md:mx-0 md:border-0 md:bg-transparent md:px-0">
        <div><p className="font-bold">{q.data.title}</p><p className="text-xs text-muted" aria-live="polite">{saveState === 'saving' ? 'Menyimpan…' : saveState === 'offline' ? 'Offline — jawaban tersimpan di perangkat ini' : dirty ? 'Perubahan belum tersimpan' : 'Tersimpan'}</p></div>
        {left !== null && <p className={cx('rounded-lg px-3 py-1 font-mono text-lg font-bold', left < 60 ? 'bg-danger-soft text-danger' : 'bg-sunken')} role="timer" aria-label="Sisa waktu">{mmss(left)}</p>}
      </div>

      <nav aria-label="Navigasi soal" className="mt-3 flex flex-wrap gap-1.5">
        {items.map((it: any, i: number) => (
          <button key={it.question.id} onClick={() => setIdx(i)} aria-current={i === idx} aria-label={`Soal ${i + 1}${answered(it.question.id, it.question.type) ? ', sudah dijawab' : ', belum dijawab'}`}
            className={cx('h-9 w-9 rounded-lg border text-sm font-semibold', i === idx ? 'border-brand bg-brand text-on-brand' : answered(it.question.id, it.question.type) ? 'border-brand/40 bg-brand-soft text-brand-strong' : 'border-line bg-surface')}>{i + 1}</button>
        ))}
      </nav>

      <section className="mt-5 rounded-2xl border border-line bg-surface p-5" aria-labelledby="soal">
        <div className="flex flex-wrap items-center gap-2 text-xs"><Badge tone="brand">{DIFFICULTY[cur.question.difficulty]}</Badge><Badge>{QTYPE[cur.question.type]}</Badge><span className="text-muted">{cur.question.topic}</span></div>
        <h2 id="soal" className="sr-only">Soal {idx + 1}</h2>
        <div className="mt-3"><RichText html={cur.question.body} /></div>

        {cur.question.type === 'essay' ? (
          <div className="mt-4">
            <label htmlFor="essay" className="mb-1 block text-sm font-semibold">Jawabanmu</label>
            <Textarea id="essay" rows={7} maxLength={5000} value={a.answer_text ?? ''} onChange={e => set({ answer_text: e.target.value })} placeholder="Tulis jawabanmu. Setelah dikirim kamu bisa membandingkannya dengan pembahasan dan menilai sendiri." />
          </div>
        ) : (
          <fieldset className="mt-4 space-y-2">
            <legend className="sr-only">Pilihan jawaban</legend>
            {cur.question.options.map((o: any) => (
              <label key={o.id} className={cx('flex cursor-pointer items-start gap-3 rounded-xl border p-3', a.option_id === o.id ? 'border-brand bg-brand-soft' : 'border-line hover:bg-sunken')}>
                <input type="radio" name={`q${cur.question.id}`} checked={a.option_id === o.id} onChange={() => set({ option_id: o.id })} className="mt-1 accent-[var(--brand)]" />
                <span><strong className="mr-2">{o.label}.</strong><MathText text={o.text} /></span>
              </label>
            ))}
            {a.option_id != null && <button type="button" onClick={() => set({ option_id: null })} className="text-sm text-muted underline">Hapus pilihan</button>}
          </fieldset>
        )}
      </section>

      {err && <p role="alert" className="mt-3 rounded-lg bg-danger-soft p-3 text-sm text-danger">{err}</p>}
      <div className="mt-5 flex items-center justify-between gap-3">
        <Button variant="secondary" disabled={idx === 0} onClick={() => setIdx(i => i - 1)}>‹ Sebelumnya</Button>
        {idx < items.length - 1 ? <Button onClick={() => setIdx(i => i + 1)}>Berikutnya ›</Button> : <Button onClick={() => setConfirm(true)}>Kirim jawaban</Button>}
      </div>
      {idx < items.length - 1 && <div className="mt-3 text-center"><button className="text-sm font-semibold text-brand-strong underline" onClick={() => setConfirm(true)}>Kirim jawaban sekarang</button></div>}

      <Modal open={confirm} onClose={() => !submitting && setConfirm(false)} title="Kirim jawaban?">
        <p>{unanswered > 0 ? `${unanswered} soal belum dijawab dan akan dihitung salah.` : 'Semua soal sudah dijawab.'} Setelah dikirim, jawaban tidak bisa diubah dan pembahasan terbuka.</p>
        <div className="mt-5 flex justify-end gap-2"><Button variant="secondary" disabled={submitting} onClick={() => setConfirm(false)}>Kembali</Button><Button loading={submitting} onClick={submit}>Kirim</Button></div>
      </Modal>
    </div>
  )
}
