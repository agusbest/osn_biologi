import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { get } from '../../lib/api'
import { useAuth } from '../../lib/auth'
import { usePageMeta } from '../../lib/meta'
import { pct } from '../../lib/format'
import { Card, EmptyState, ErrorState, LinkButton, Loading, PageHeader, ProgressBar } from '../../components/ui'

function Stat({ label, value, sub }: { label: string; value: string; sub?: string }) {
  return (
    <Card>
      <p className="text-sm text-muted">{label}</p>
      <p className="mt-1 text-3xl font-extrabold tracking-tight">{value}</p>
      {sub && <p className="mt-0.5 text-sm text-muted">{sub}</p>}
    </Card>
  )
}

export default function Dashboard() {
  usePageMeta('Dashboard', { noindex: true })
  const { user } = useAuth()
  const q = useQuery({ queryKey: ['dashboard'], queryFn: () => get('/dashboard') })
  if (q.isLoading) return <Loading />
  if (q.isError) return <ErrorState error={q.error} onRetry={() => q.refetch()} />
  const d = q.data
  const daily = Math.min(100, (d.daily.studied_minutes / Math.max(1, d.daily.target_minutes)) * 100)

  return (
    <>
      <PageHeader title={`Halo, ${user?.name.split(' ')[0]}`} sub="Ringkasan belajarmu hari ini." />

      <section aria-label="Lanjutkan belajar" className="rounded-3xl bg-brand p-6 text-on-brand sm:p-8">
        {d.continue ? (
          <div className="flex flex-wrap items-center justify-between gap-5">
            <div>
              <p className="text-sm opacity-90">{d.continue.status === 'sedang' ? 'Kamu berhenti di sini' : 'Materi berikutnya untukmu'}</p>
              <p className="mt-1 text-2xl font-bold sm:text-3xl">{d.continue.title}</p>
            </div>
            <Link to={`/app/materi/${d.continue.lesson_id}`} className="rounded-xl bg-surface px-7 py-4 text-lg font-bold text-brand-strong hover:opacity-90">Lanjutkan belajar</Link>
          </div>
        ) : (
          <div className="flex flex-wrap items-center justify-between gap-4">
            <div>
              <p className="text-2xl font-bold">{d.lessons_total > 0 ? 'Semua materi yang bisa kamu akses sudah selesai' : 'Belum ada materi yang tersedia'}</p>
              <p className="mt-1 opacity-90">{d.lessons_total > 0 ? 'Latih kemampuanmu dengan bank soal atau buka paket untuk materi lain.' : 'Pengajar sedang menyiapkan materi. Coba lagi nanti.'}</p>
            </div>
            <Link to="/app/latihan" className="rounded-xl bg-surface px-6 py-3 font-bold text-brand-strong">Buka bank soal</Link>
          </div>
        )}
      </section>

      <div className="mt-6 grid gap-4 sm:grid-cols-3">
        <Stat label="Progres kursus" value={pct(d.progress_percent)} sub={`${d.lessons_done} dari ${d.lessons_total} materi`} />
        <Stat label="Modul selesai" value={`${d.modules_done}/${d.modules_total}`} sub="Dari paket yang aktif" />
        <Stat label="Akurasi latihan" value={d.accuracy === null ? '—' : pct(d.accuracy)} sub={d.accuracy === null ? 'Belum ada latihan yang dikirim' : 'Semua soal yang sudah dinilai'} />
      </div>

      <Card className="mt-4">
        <div className="flex items-baseline justify-between gap-2">
          <h2 className="font-bold">Target belajar hari ini</h2>
          <span className="text-sm text-muted">{d.daily.studied_minutes} dari {d.daily.target_minutes} menit</span>
        </div>
        <div className="mt-3"><ProgressBar value={daily} label="Target belajar harian" tone={daily >= 100 ? 'brand' : 'amber'} /></div>
        <p className="mt-2 text-sm text-muted">Dihitung dari estimasi waktu materi yang selesai hari ini dan lama latihan. <Link className="underline" to="/app/profil">Ubah target</Link></p>
      </Card>

      <div className="mt-4 grid gap-4 lg:grid-cols-2">
        <Card>
          <h2 className="font-bold">Skor per topik</h2>
          {d.topic_scores.length === 0 ? (
            <div className="mt-3"><EmptyState title="Belum ada skor" action={<LinkButton to="/app/latihan" size="sm">Mulai latihan</LinkButton>}>Kerjakan satu latihan, hasilnya muncul di sini.</EmptyState></div>
          ) : (
            <div className="mt-3 h-64" role="img" aria-label={'Skor per topik: ' + d.topic_scores.map((t: any) => `${t.title} ${Math.round(t.accuracy)} persen`).join(', ')}>
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={d.topic_scores} layout="vertical" margin={{ left: 0, right: 16 }}>
                  <CartesianGrid horizontal={false} stroke="var(--line)" />
                  <XAxis type="number" domain={[0, 100]} tick={{ fill: 'var(--muted)', fontSize: 12 }} unit="%" />
                  <YAxis type="category" dataKey="title" width={110} tick={{ fill: 'var(--muted)', fontSize: 12 }} />
                  <Tooltip formatter={(v: any) => `${v}%`} contentStyle={{ background: 'var(--surface)', border: '1px solid var(--line)', borderRadius: 8 }} />
                  <Bar dataKey="accuracy" name="Akurasi" radius={[0, 6, 6, 0]}>
                    {d.topic_scores.map((t: any) => <Cell key={t.topic_id} fill={t.accuracy < 60 ? 'var(--amber)' : 'var(--brand)'} />)}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
          )}
        </Card>
        <Card>
          <h2 className="font-bold">Topik yang perlu diperkuat</h2>
          {d.weak_topics.length === 0 ? (
            <p className="mt-3 text-sm text-muted">Belum ada topik lemah. Topik muncul di sini setelah beberapa soal dijawab dengan skor rendah.</p>
          ) : (
            <ul className="mt-3 divide-y divide-line">
              {d.weak_topics.map((t: any) => (
                <li key={t.topic_id} className="flex items-center justify-between gap-3 py-3">
                  <div><p className="font-semibold">{t.title}</p><p className="text-sm text-muted">Akurasi {pct(t.accuracy)} dari {t.answered} soal</p></div>
                  <LinkButton to={`/app/latihan?topic_id=${t.topic_id}`} variant="secondary" size="sm">Latih</LinkButton>
                </li>
              ))}
            </ul>
          )}
          <Link to="/app/evaluasi" className="mt-3 inline-block text-sm font-semibold text-brand-strong underline">Lihat rekomendasi lengkap</Link>
        </Card>
      </div>
    </>
  )
}
