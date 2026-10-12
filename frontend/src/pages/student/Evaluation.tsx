import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { get } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY, dateTimeID, pct } from '../../lib/format'
import { Badge, Card, EmptyState, ErrorState, LinkButton, Loading, PageHeader } from '../../components/ui'

const tone = { dasar: 'danger', aplikasi: 'amber', lanjutan: 'ok' } as const

export default function Evaluation() {
  usePageMeta('Evaluasi', { noindex: true })
  const q = useQuery({ queryKey: ['evaluation'], queryFn: () => get('/evaluation') })
  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const d = q.data

  if (d.topic_scores.length === 0) {
    return (<><PageHeader title="Evaluasi" /><EmptyState title="Belum ada data evaluasi" action={<LinkButton to="/app/latihan">Mulai latihan</LinkButton>}>Kirim satu sesi latihan agar skor per topik dan rekomendasi muncul di sini.</EmptyState></>)
  }

  return (
    <>
      <PageHeader title="Evaluasi dan analisis kelemahan" sub={d.accuracy !== null ? `Akurasi keseluruhan ${pct(d.accuracy)}.` : undefined} />

      <Card>
        <h2 className="font-bold">Rekap nilai per topik</h2>
        <div className="mt-3 h-72" role="img" aria-label={'Akurasi per topik: ' + d.topic_scores.map((t: any) => `${t.title} ${Math.round(t.accuracy)} persen`).join(', ')}>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={d.topic_scores} margin={{ left: -10, right: 8 }}>
              <CartesianGrid vertical={false} stroke="var(--line)" />
              <XAxis dataKey="title" tick={{ fill: 'var(--muted)', fontSize: 11 }} interval={0} tickFormatter={(s: string) => (s.length > 12 ? s.slice(0, 11) + '…' : s)} />
              <YAxis domain={[0, 100]} unit="%" tick={{ fill: 'var(--muted)', fontSize: 12 }} />
              <Tooltip formatter={(v: any, _n: any, p: any) => [`${v}% (${p.payload.correct}/${p.payload.answered})`, 'Akurasi']} contentStyle={{ background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 8 }} />
              <Bar dataKey="accuracy" radius={[6, 6, 0, 0]}>{d.topic_scores.map((t: any) => <Cell key={t.topic_id} fill={t.accuracy < 60 ? 'var(--amber)' : 'var(--brand)'} />)}</Bar>
            </BarChart>
          </ResponsiveContainer>
        </div>
      </Card>

      <h2 className="mb-3 mt-8 text-xl font-bold">Rekomendasi</h2>
      {d.recommendations.length === 0 ? (
        <p className="text-muted">Rekomendasi muncul setelah kamu menjawab cukup banyak soal pada satu topik.</p>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {d.recommendations.map((r: any) => (
            <Card key={r.topic_id}>
              <div className="flex items-start justify-between gap-2"><h3 className="font-bold">{r.title}</h3><Badge tone={tone[r.tier as keyof typeof tone] ?? 'neutral'}>{r.label}</Badge></div>
              <p className="text-sm text-muted">Akurasi {pct(r.accuracy)} dari {r.answered} soal</p>
              <ul className="mt-3 list-disc space-y-1 pl-5 text-sm">{r.actions.map((a: string) => <li key={a}>{a}</li>)}</ul>
              {r.modules.length > 0 && (
                <div className="mt-3"><p className="text-sm font-semibold">Materi yang disarankan</p>
                  <ul className="mt-1 space-y-1 text-sm">{r.modules.map((m: any) => (<li key={m.id}>{m.first_lesson_id ? <Link className="text-brand-strong underline" to={`/app/materi/${m.first_lesson_id}`}>{m.title}</Link> : m.title}</li>))}</ul></div>
              )}
              <LinkButton to={`/app/latihan?topic_id=${r.topic_id}`} variant="secondary" size="sm" className="mt-4">Latihan topik ini</LinkButton>
            </Card>
          ))}
        </div>
      )}

      <div className="mt-8 grid gap-4 lg:grid-cols-2">
        <Card>
          <h2 className="font-bold">Riwayat jawaban salah</h2>
          {d.wrong_history.length === 0 ? <p className="mt-2 text-sm text-muted">Belum ada jawaban salah. Bagus!</p> : (
            <ul className="mt-2 divide-y divide-line">
              {d.wrong_history.map((w: any, i: number) => (
                <li key={i} className="py-2.5">
                  <p className="line-clamp-2 text-sm">{w.excerpt}</p>
                  <p className="mt-0.5 text-xs text-muted">{w.topic} · {DIFFICULTY[w.difficulty]} · {dateTimeID(w.submitted_at)} · <Link className="underline" to={`/app/latihan/hasil/${w.attempt_id}`}>Lihat pembahasan</Link></p>
                </li>
              ))}
            </ul>
          )}
        </Card>
        <Card>
          <h2 className="font-bold">Percobaan terakhir</h2>
          <ul className="mt-2 divide-y divide-line">
            {d.recent_attempts.map((a: any) => (
              <li key={a.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                <span><Link className="font-medium underline" to={`/app/latihan/hasil/${a.id}`}>{a.title}</Link><span className="block text-xs text-muted">{dateTimeID(a.submitted_at)}</span></span>
                <span className="font-semibold">{a.score === null ? '—' : pct(a.score)}</span>
              </li>
            ))}
          </ul>
        </Card>
      </div>
    </>
  )
}
