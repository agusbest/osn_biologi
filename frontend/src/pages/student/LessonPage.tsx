import { Link, useParams } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { del, get, post } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY } from '../../lib/format'
import { Badge, Button, ErrorState, LinkButton, Loading } from '../../components/ui'
import { MathText, RichText } from '../../components/RichText'

const SECTIONS: [string, string][] = [
  ['objectives', 'Target pembelajaran'],
  ['explanation', 'Penjelasan sederhana'],
  ['key_concepts', 'Konsep wajib dipahami'],
  ['osn_thinking', 'Cara berpikir ala OSN'],
  ['worked_example', 'Contoh soal'],
  ['practice', 'Latihan mandiri'],
  ['summary', 'Ringkasan cepat'],
]

export default function LessonPage() {
  const { id } = useParams()
  const qc = useQueryClient()
  const q = useQuery({ queryKey: ['lesson', id], queryFn: () => get(`/lessons/${id}`), retry: false })
  usePageMeta(q.data?.lesson.title ?? 'Materi', { noindex: true })

  const toggle = useMutation({
    mutationFn: (done: boolean) => (done ? post(`/lessons/${id}/complete`) : del(`/lessons/${id}/complete`)),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['lesson', id] }); qc.invalidateQueries({ queryKey: ['curriculum'] }); qc.invalidateQueries({ queryKey: ['dashboard'] }) },
  })

  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const { lesson: l, module: m, topic: t, progress, prev, next } = q.data
  const done = progress === 'completed'

  return (
    <article>
      <nav aria-label="Jalur" className="text-sm text-muted"><Link to="/app/kurikulum" className="hover:underline">Kurikulum</Link> › {t.title} › {m.title}</nav>
      <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">{l.title}</h1>
      <div className="mt-3 flex flex-wrap items-center gap-2">
        <Badge tone="brand">{DIFFICULTY[l.difficulty]}</Badge>
        <Badge>{l.est_minutes} menit baca</Badge>
        {done && <Badge tone="ok">Selesai</Badge>}
      </div>

      {SECTIONS.map(([key, title]) => l[key] && l[key].trim() && (
        <section key={key} className="mt-9" aria-labelledby={`s-${key}`}>
          <h2 id={`s-${key}`} className="mb-2 text-xl font-bold">{title}</h2>
          <RichText html={l[key]} />
          {key === 'practice' && (
            <LinkButton to={`/app/latihan?module_id=${m.id}`} variant="secondary" size="sm" className="mt-3">Kerjakan soal modul ini</LinkButton>
          )}
        </section>
      ))}

      {l.glossary.length > 0 && (
        <section className="mt-9" aria-labelledby="s-glos">
          <h2 id="s-glos" className="mb-3 text-xl font-bold">Glosarium</h2>
          <dl className="grid gap-x-8 gap-y-3 sm:grid-cols-2">
            {l.glossary.map((g: any) => (<div key={g.term}><dt className="font-semibold">{g.term}</dt><dd className="text-sm text-muted"><MathText text={g.definition} /></dd></div>))}
          </dl>
        </section>
      )}

      {l.reference_list.length > 0 && (
        <section className="mt-9" aria-labelledby="s-ref">
          <h2 id="s-ref" className="mb-2 text-xl font-bold">Referensi</h2>
          <ul className="list-disc space-y-1 pl-5 text-sm text-muted">
            {l.reference_list.map((r: any, i: number) => (<li key={i}>{r.url ? <a className="underline" href={r.url} target="_blank" rel="noopener noreferrer">{r.text}</a> : r.text}</li>))}
          </ul>
        </section>
      )}

      {l.resources.length > 0 && (
        <section className="mt-9"><h2 className="mb-2 text-xl font-bold">Lampiran gambar</h2>
          <div className="grid gap-4 sm:grid-cols-2">{l.resources.map((r: any) => (<figure key={r.id}><img src={r.url} alt={r.alt_text || ''} loading="lazy" className="rounded-xl border border-line" />{r.caption && <figcaption className="mt-1 text-sm text-muted">{r.caption}</figcaption>}</figure>))}</div>
        </section>
      )}

      <div className="mt-12 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-6">
        {prev ? (prev.locked ? <span className="text-sm text-muted">‹ {prev.title} (terkunci)</span> : <LinkButton to={`/app/materi/${prev.id}`} variant="secondary">‹ Sebelumnya</LinkButton>) : <span />}
        <Button variant={done ? 'secondary' : 'primary'} loading={toggle.isPending} onClick={() => toggle.mutate(!done)}>{done ? 'Batalkan tanda selesai' : 'Tandai selesai'}</Button>
        {next ? (next.locked ? <Link to="/app/paket" className="text-sm font-semibold text-amber underline">Materi berikutnya butuh paket</Link> : <LinkButton to={`/app/materi/${next.id}`} variant={done ? 'primary' : 'secondary'}>Berikutnya ›</LinkButton>) : <span />}
      </div>
      {toggle.isError && <p role="alert" className="mt-2 text-sm text-danger">Gagal menyimpan progres. Coba lagi.</p>}
    </article>
  )
}
