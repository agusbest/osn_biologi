import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { get } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { Card, ErrorState, Loading, PageHeader } from '../../components/ui'

export default function AdminHome() {
  usePageMeta('Ringkasan admin', { noindex: true })
  const q = useQuery({ queryKey: ['admin-stats'], queryFn: () => get('/admin/stats') })
  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const c = q.data.counts
  const tiles: [string, number, string][] = [
    ['Siswa', c.students, '/admin/siswa'], ['Enrollment aktif', c.active_enrollments, '/admin/siswa'],
    ['Pembayaran menunggu', c.pending_payments, '/admin/pembayaran'], ['Materi terbit', c.published_lessons, '/admin/materi'],
    ['Soal terbit', c.published_questions, '/admin/soal'], ['Konten dalam tinjauan', c.in_review, '/admin/materi'],
  ]
  return (
    <>
      <PageHeader title="Ringkasan" sub="Status konten, siswa, dan titik sulit belajar." />
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-3">
        {tiles.map(([l, n, to]) => (
          <Link key={l} to={to} className="rounded-2xl border border-line bg-surface p-4 hover:border-brand"><p className="text-sm text-muted">{l}</p><p className="text-3xl font-extrabold">{n}</p></Link>
        ))}
      </div>
      <div className="mt-6 grid gap-4 lg:grid-cols-2">
        <Card>
          <h2 className="font-bold">Soal yang paling sering dijawab salah</h2>
          {q.data.hardest_questions.length === 0 ? <p className="mt-2 text-sm text-muted">Belum cukup data (butuh beberapa jawaban per soal).</p> : (
            <ul className="mt-2 divide-y divide-line">{q.data.hardest_questions.map((x: any) => (
              <li key={x.id} className="py-2.5"><Link to={`/admin/soal/${x.id}`} className="text-sm underline">{x.excerpt || `Soal #${x.id}`}</Link><p className="text-xs text-muted">{x.wrong} salah dari {x.answered} jawaban ({x.wrong_rate}%)</p></li>
            ))}</ul>
          )}
        </Card>
        <Card>
          <h2 className="font-bold">Materi tersulit (akurasi terendah)</h2>
          {q.data.hardest_modules.length === 0 ? <p className="mt-2 text-sm text-muted">Belum cukup data. Hubungkan soal ke modul agar statistik ini muncul.</p> : (
            <ul className="mt-2 divide-y divide-line">{q.data.hardest_modules.map((m: any) => (
              <li key={m.id} className="flex justify-between py-2.5 text-sm"><span>{m.title}</span><span className="font-semibold">{m.accuracy}% <span className="font-normal text-muted">({m.answered} jawaban)</span></span></li>
            ))}</ul>
          )}
        </Card>
      </div>
    </>
  )
}
