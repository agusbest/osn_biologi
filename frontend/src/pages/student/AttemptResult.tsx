import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ApiError, get, post } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY, mmss, pct, QTYPE } from '../../lib/format'
import { Badge, Button, Card, cx, ErrorState, LinkButton, Loading, PageHeader } from '../../components/ui'
import { MathText, RichText } from '../../components/RichText'

export default function AttemptResult() {
  const { id } = useParams()
  const nav = useNavigate()
  const qc = useQueryClient()
  usePageMeta('Hasil latihan', { noindex: true })
  const q = useQuery({ queryKey: ['results', id], queryFn: () => get(`/attempts/${id}/results`), staleTime: 60_000 })
  const [err, setErr] = useState('')

  const retry = useMutation({
    mutationFn: (onlyWrong: boolean) => post(`/attempts/${id}/retry`, { only_wrong: onlyWrong }),
    onSuccess: r => nav(`/app/latihan/sesi/${r.id}`),
    onError: e => setErr(e instanceof ApiError ? e.friendly : 'Gagal membuat percobaan ulang.'),
  })
  const assess = useMutation({
    mutationFn: ({ qid, correct }: { qid: number; correct: boolean }) => post(`/attempts/${id}/answers/${qid}/self-assess`, { correct }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['results', id] }); qc.invalidateQueries({ queryKey: ['dashboard'] }); qc.invalidateQueries({ queryKey: ['evaluation'] }) },
  })

  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const { attempt: a, items, topic_breakdown } = q.data
  const wrong = items.filter((i: any) => i.is_correct === false).length

  return (
    <>
      <PageHeader title="Hasil latihan" sub={a.title} />
      <Card>
        <div className="flex flex-wrap items-center gap-6">
          <div>
            <p className="text-sm text-muted">Skor</p>
            <p className="text-5xl font-extrabold tracking-tight">{a.score === null ? '—' : pct(a.score)}</p>
          </div>
          <dl className="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
            <dt className="text-muted">Benar</dt><dd className="font-semibold">{a.correct_count} dari {a.graded_count} soal dinilai</dd>
            <dt className="text-muted">Durasi</dt><dd className="font-semibold">{a.duration_seconds != null ? mmss(a.duration_seconds) : '—'}</dd>
            {a.ungraded_count > 0 && (<><dt className="text-muted">Belum dinilai</dt><dd className="font-semibold">{a.ungraded_count} uraian (nilai sendiri di bawah)</dd></>)}
          </dl>
        </div>
        {a.timed_out && <p role="note" className="mt-3 rounded-lg bg-amber-soft p-2 text-sm text-amber">Waktu habis. Hanya jawaban yang tersimpan sebelum tenggat yang dinilai.</p>}
        <div className="mt-4 flex flex-wrap gap-2">
          <Button variant="secondary" loading={retry.isPending} onClick={() => { setErr(''); retry.mutate(false) }}>Ulangi semua soal</Button>
          {wrong > 0 && <Button loading={retry.isPending} onClick={() => { setErr(''); retry.mutate(true) }}>Ulangi {wrong} soal yang salah</Button>}
          <LinkButton to="/app/evaluasi" variant="ghost">Lihat analisis kelemahan</LinkButton>
        </div>
        {err && <p role="alert" className="mt-2 text-sm text-danger">{err}</p>}
      </Card>

      {topic_breakdown.length > 0 && (
        <Card className="mt-4">
          <h2 className="font-bold">Per topik pada sesi ini</h2>
          <ul className="mt-2 divide-y divide-line">{topic_breakdown.map((t: any) => (<li key={t.topic_id} className="flex justify-between py-2"><span>{t.title}</span><span className="font-semibold">{t.correct}/{t.answered} ({pct(t.accuracy)})</span></li>))}</ul>
        </Card>
      )}

      <h2 className="mb-3 mt-8 text-xl font-bold">Pembahasan</h2>
      <ol className="space-y-5">
        {items.map((it: any, i: number) => {
          const qn = it.question
          const isEssay = qn.type === 'essay'
          return (
            <li key={qn.id} className="rounded-2xl border border-line bg-surface p-5">
              <div className="flex flex-wrap items-center gap-2 text-xs">
                <span className="font-bold">Soal {i + 1}</span>
                <Badge tone="brand">{DIFFICULTY[qn.difficulty]}</Badge><Badge>{QTYPE[qn.type]}</Badge>
                {it.is_correct === true && <Badge tone="ok">Benar</Badge>}
                {it.is_correct === false && <Badge tone="danger">{it.chosen_option_id || it.answer_text ? 'Salah' : 'Tidak dijawab'}</Badge>}
                {it.is_correct === null && <Badge tone="amber">Menunggu penilaian</Badge>}
              </div>
              <div className="mt-2"><RichText html={qn.body} /></div>

              {!isEssay && (
                <ul className="mt-3 space-y-1.5">
                  {qn.options.map((o: any) => {
                    const correct = it.correct_option_ids.includes(o.id)
                    const chosen = it.chosen_option_id === o.id
                    return (
                      <li key={o.id} className={cx('flex gap-2 rounded-lg border p-2.5 text-sm', correct ? 'border-ok bg-ok-soft' : chosen ? 'border-danger bg-danger-soft' : 'border-line')}>
                        <strong>{o.label}.</strong><MathText text={o.text} />
                        <span className="ml-auto shrink-0 font-semibold">{correct ? '✓ Kunci' : chosen ? '✗ Pilihanmu' : ''}</span>
                      </li>
                    )
                  })}
                </ul>
              )}
              {isEssay && (
                <div className="mt-3 rounded-lg border border-line p-3 text-sm">
                  <p className="font-semibold">Jawabanmu</p>
                  <p className="mt-1 whitespace-pre-wrap text-muted">{it.answer_text || '(kosong)'}</p>
                  <p className="mt-3 font-semibold">Sudah sesuai dengan pembahasan?</p>
                  <div className="mt-1 flex gap-2">
                    <Button size="sm" variant={it.is_correct === true ? 'primary' : 'secondary'} onClick={() => assess.mutate({ qid: qn.id, correct: true })}>Sesuai</Button>
                    <Button size="sm" variant={it.is_correct === false ? 'danger' : 'secondary'} onClick={() => assess.mutate({ qid: qn.id, correct: false })}>Belum sesuai</Button>
                  </div>
                </div>
              )}

              <div className="mt-4 space-y-2">
                {it.explanation.filter((e: any) => e.html && e.html.trim()).map((e: any) => (
                  <details key={e.key} className="rounded-lg border border-line" open={e.key === 'correct_answer' || e.key === 'solution_steps' || (it.is_correct === false && e.key === 'common_mistakes')}>
                    <summary className="cursor-pointer px-3 py-2 font-semibold">{e.label}</summary>
                    <div className="px-3 pb-3"><RichText html={e.html} /></div>
                  </details>
                ))}
              </div>
              {qn.tags.length > 0 && <p className="mt-3 text-xs text-muted">Kompetensi: {qn.tags.join(', ')}</p>}
            </li>
          )
        })}
      </ol>
      <p className="mt-8"><Link to="/app/latihan" className="font-semibold text-brand-strong underline">Kembali ke bank soal</Link></p>
    </>
  )
}
