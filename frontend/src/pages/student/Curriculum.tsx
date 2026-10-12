import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { get } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY } from '../../lib/format'
import { Badge, EmptyState, ErrorState, Loading, PageHeader, ProgressBar } from '../../components/ui'

const statusTone: Record<string, 'neutral' | 'brand' | 'ok' | 'amber'> = { belum: 'neutral', sedang: 'brand', selesai: 'ok', terkunci: 'amber' }
const statusLabel: Record<string, string> = { belum: 'Belum', sedang: 'Sedang', selesai: 'Selesai', terkunci: 'Terkunci' }

export default function Curriculum() {
  usePageMeta('Kurikulum', { noindex: true })
  const q = useQuery({ queryKey: ['curriculum'], queryFn: () => get('/curriculum') })
  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const topics = q.data.topics
  if (!topics.length) return <EmptyState title="Kurikulum belum tersedia">Admin belum menerbitkan bidang apa pun.</EmptyState>

  return (
    <>
      <PageHeader title="Kurikulum" sub="Tujuh bidang teori dan satu jalur praktikum. Modul terkunci terbuka dengan paket yang sesuai." />
      <div className="space-y-3">
        {topics.map((t: any) => {
          const total = t.modules.reduce((n: number, m: any) => n + m.lesson_count, 0)
          const done = t.modules.reduce((n: number, m: any) => n + m.completed_count, 0)
          return (
            <details key={t.id} className="group rounded-2xl border border-line bg-surface" open={t.modules.some((m: any) => m.status === 'sedang')}>
              <summary className="flex cursor-pointer list-none flex-wrap items-center gap-3 p-4">
                <span className="w-8 text-lg font-bold text-brand" aria-hidden>{t.number ? String(t.number).padStart(2, '0') : 'P'}</span>
                <span className="min-w-0 flex-1">
                  <span className="block font-bold">{t.title}</span>
                  <span className="block text-sm text-muted">{t.modules.length ? `${t.modules.length} modul` : 'Modul sedang disiapkan'}</span>
                </span>
                {total > 0 && <span className="w-28"><ProgressBar value={(done / total) * 100} label={`Progres ${t.title}`} /></span>}
                <span aria-hidden className="text-muted transition-transform group-open:rotate-90">›</span>
              </summary>
              <div className="border-t border-line p-4">
                <p className="mb-3 text-sm text-muted">{t.description}</p>
                {t.modules.length === 0 && <p className="text-sm text-muted">Belum ada modul yang terbit pada bidang ini.</p>}
                <ul className="space-y-3">
                  {t.modules.map((m: any) => (
                    <li key={m.id} className="rounded-xl border border-line p-3">
                      <div className="flex flex-wrap items-center justify-between gap-2">
                        <p className="font-semibold">{m.title}</p>
                        <Badge tone={statusTone[m.status]}>{m.locked ? '🔒 ' : ''}{statusLabel[m.status]}</Badge>
                      </div>
                      {m.summary && <p className="mt-1 text-sm text-muted">{m.summary}</p>}
                      <ul className="mt-2 divide-y divide-line">
                        {m.lessons.map((l: any) => (
                          <li key={l.id} className="flex flex-wrap items-center justify-between gap-2 py-2">
                            {l.locked ? (
                              <span className="text-muted">{l.title}</span>
                            ) : (
                              <Link to={`/app/materi/${l.id}`} className="font-medium text-brand-strong hover:underline">{l.title}</Link>
                            )}
                            <span className="flex items-center gap-2 text-xs text-muted">
                              {DIFFICULTY[l.difficulty]} · {l.est_minutes} mnt
                              {l.locked ? <Link to="/app/paket" className="font-semibold text-amber underline">Buka dengan paket</Link> : <Badge tone={statusTone[l.status]}>{statusLabel[l.status]}</Badge>}
                            </span>
                          </li>
                        ))}
                      </ul>
                    </li>
                  ))}
                </ul>
              </div>
            </details>
          )
        })}
      </div>
    </>
  )
}
