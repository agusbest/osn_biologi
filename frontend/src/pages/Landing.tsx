import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { get } from '../lib/api'
import { usePageMeta } from '../lib/meta'
import { rupiah, LEVEL } from '../lib/format'
import { Badge, cx, ErrorState, LinkButton, Skeleton } from '../components/ui'

/** Kurva Michaelis–Menten interaktif: contoh nyata dari modul gratis "Memahami Enzim". */
function EnzymeCurve() {
  const [s, setS] = useState(30)
  const [mode, setMode] = useState<'none' | 'comp' | 'noncomp'>('none')
  const W = 360, H = 220, pad = 34
  const base = { vmax: 100, km: 20 }
  const cur = mode === 'comp' ? { vmax: 100, km: 50 } : mode === 'noncomp' ? { vmax: 60, km: 20 } : base
  const v = (S: number, p = cur) => (p.vmax * S) / (p.km + S)
  const x = (S: number) => pad + (S / 100) * (W - pad - 12)
  const y = (val: number) => H - pad - (val / 110) * (H - pad - 14)
  const path = (p: typeof cur) => Array.from({ length: 51 }, (_, i) => `${i ? 'L' : 'M'}${x(i * 2).toFixed(1)},${y(v(i * 2, p)).toFixed(1)}`).join(' ')
  const sat = Math.round((v(s) / cur.vmax) * 100)
  const modes = [
    ['none', 'Tanpa inhibitor'],
    ['comp', 'Inhibitor kompetitif'],
    ['noncomp', 'Inhibitor nonkompetitif'],
  ] as const
  const note = {
    none: 'Laju naik cepat lalu mendatar: semua enzim sedang sibuk (jenuh).',
    comp: 'Vmax tetap, tetapi dibutuhkan lebih banyak substrat untuk separuh Vmax (Km tampak naik).',
    noncomp: 'Vmax turun karena sebagian enzim tak berfungsi, sedangkan Km tetap.',
  }[mode]

  return (
    <figure className="rounded-2xl border border-line bg-surface p-4 sm:p-5">
      <figcaption className="mb-2 text-sm font-semibold">Geser konsentrasi substrat, lihat lajunya</figcaption>
      <svg viewBox={`0 0 ${W} ${H}`} role="img" aria-label={`Grafik laju reaksi enzim terhadap konsentrasi substrat. Pada substrat ${s}, laju mencapai ${sat}% dari laju maksimum.`} className="w-full">
        <line x1={pad} y1={H - pad} x2={W - 8} y2={H - pad} stroke="var(--line)" strokeWidth="2" />
        <line x1={pad} y1={H - pad} x2={pad} y2={10} stroke="var(--line)" strokeWidth="2" />
        <text x={W - 8} y={H - 10} textAnchor="end" fontSize="11" fill="var(--muted)">konsentrasi substrat [S]</text>
        <text x={pad + 6} y={20} fontSize="11" fill="var(--muted)">laju reaksi v</text>
        <path d={path(base)} fill="none" stroke="var(--muted)" strokeWidth="2" strokeDasharray="5 5" opacity=".6" />
        <path key={mode} d={path(cur)} fill="none" stroke="var(--brand)" strokeWidth="4" strokeLinecap="round" className="draw-once" style={{ ['--len' as any]: 420 }} />
        <line x1={x(s)} y1={y(v(s))} x2={x(s)} y2={H - pad} stroke="var(--brand)" strokeWidth="1.5" strokeDasharray="3 3" />
        <circle cx={x(s)} cy={y(v(s))} r="7" fill="var(--amber)" stroke="var(--surface)" strokeWidth="3" />
      </svg>
      <label className="mt-1 block text-sm">
        <span className="sr-only">Konsentrasi substrat</span>
        <input type="range" min={0} max={100} value={s} onChange={e => setS(+e.target.value)} className="w-full accent-[var(--brand)]" aria-valuetext={`Substrat ${s}, laju ${sat}% dari maksimum`} />
      </label>
      <p className="mt-1 text-sm"><strong>{sat}%</strong> dari laju maksimum pada kurva ini. <span className="text-muted">{note}</span></p>
      <div className="mt-3 flex flex-wrap gap-2" role="group" aria-label="Jenis inhibitor">
        {modes.map(([k, l]) => (
          <button key={k} onClick={() => setMode(k)} aria-pressed={mode === k}
            className={cx('rounded-full border px-3 py-1 text-sm font-medium', mode === k ? 'border-brand bg-brand text-on-brand' : 'border-line hover:bg-sunken')}>{l}</button>
        ))}
      </div>
    </figure>
  )
}

const pillars = [
  ['Pelajari', 'Materi ringkas dengan urutan tetap: target, penjelasan, konsep wajib, lalu cara berpikir ala OSN.'],
  ['Latih', 'Soal bertahap dari fondasi hingga tantangan. Pilih topik, tingkat, dan jenis soal sendiri.'],
  ['Ukur', 'Nilai dihitung di server, lengkap dengan pembahasan tujuh bagian untuk setiap soal.'],
  ['Perbaiki', 'Topik lemah terdeteksi otomatis, lalu kamu diarahkan ke materi dan latihan yang tepat.'],
]

const faqs = [
  ['Apakah materinya mengikuti silabus resmi OSN?', 'Kerangkanya terdiri dari tujuh bidang teori ditambah jalur eksperimen dan praktikum. Silabus resmi dapat berubah tiap tahun, jadi selalu cocokkan dengan pengumuman penyelenggara OSN.'],
  ['Apakah praktikum virtual menggantikan praktik di laboratorium?', 'Tidak. Jalur praktikum membantu merancang eksperimen dan membaca data, tetapi tidak menggantikan latihan di laboratorium nyata.'],
  ['Bagaimana cara membayar paket berbayar?', 'Pembayaran saat ini diverifikasi manual oleh admin. Kamu mengirim data pembayaran dari halaman Paket, lalu akses terbuka setelah diverifikasi.'],
  ['Apakah saya bisa mencoba dulu?', 'Bisa. Paket Free membuka materi demo dan sejumlah soal latihan tanpa biaya.'],
  ['Bagaimana jika saya ingin refund?', 'Ketentuannya ada di halaman Ketentuan Refund. Baca sebelum membeli.'],
  ['Bisa dipakai di HP?', 'Bisa. Tampilan dirancang mulai dari layar 360 px sampai desktop.'],
]

export default function Landing() {
  usePageMeta('OSN Biology Academy — Belajar Biologi untuk OSN', { description: 'E-course Biologi untuk persiapan OSN SMA/MA: materi ringkas, bank soal bertahap, pembahasan tujuh bagian, dan rekomendasi perbaikan per topik.' })
  const q = useQuery({ queryKey: ['landing'], queryFn: () => get('/public/landing') })

  return (
    <>
      <section className="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-12 md:grid-cols-[1.1fr_1fr] md:pt-20">
        <div>
          <h1 className="text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
            Persiapan OSN Biologi yang membaca grafik, bukan menghafal jawaban.
          </h1>
          <p className="mt-5 max-w-xl text-lg text-muted">
            Satu sistem belajar untuk siswa SMA/MA: pelajari materinya, latih dengan soal bertahap, ukur kemampuanmu per topik, lalu perbaiki bagian yang masih lemah.
          </p>
          <div className="mt-7 flex flex-wrap gap-3">
            <LinkButton to="/daftar" size="lg">Mulai belajar gratis</LinkButton>
            <LinkButton to="/demo" size="lg" variant="secondary">Baca materi demo</LinkButton>
          </div>
          <p className="mt-4 text-sm text-muted">Tanpa kartu kredit. Paket Free membuka materi demo dan soal latihan.</p>
        </div>
        <EnzymeCurve />
      </section>

      <section aria-labelledby="alur" className="border-y border-line bg-sunken">
        <div className="mx-auto max-w-6xl px-4 py-14">
          <h2 id="alur" className="max-w-2xl text-2xl font-bold sm:text-3xl">Belajar sebagai siklus, bukan tumpukan PDF</h2>
          <ol className="mt-8 grid gap-px overflow-hidden rounded-2xl border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
            {pillars.map(([t, d]) => (
              <li key={t} className="bg-surface p-5">
                <p className="text-xl font-bold text-brand-strong">{t}</p>
                <p className="mt-2 text-sm text-muted">{d}</p>
              </li>
            ))}
          </ol>
        </div>
      </section>

      <section id="kurikulum" className="mx-auto max-w-6xl scroll-mt-20 px-4 py-16">
        <h2 className="text-2xl font-bold sm:text-3xl">Kurikulum: tujuh bidang dan satu jalur praktikum</h2>
        <p className="mt-2 max-w-2xl text-muted">Isi materi ditulis bertahap oleh pengajar. Modul yang belum terbit tidak ditampilkan kepada siswa.</p>
        {q.isLoading && <div className="mt-8 grid gap-3 sm:grid-cols-2">{Array.from({ length: 8 }).map((_, i) => <Skeleton key={i} className="h-20" />)}</div>}
        {q.isError && <div className="mt-8"><ErrorState error={q.error} onRetry={() => q.refetch()} /></div>}
        {q.data && (
          <ul className="mt-8 grid gap-x-10 sm:grid-cols-2">
            {q.data.topics.map((t: any) => (
              <li key={t.id} className="flex gap-4 border-t border-line py-4">
                <span className="w-8 shrink-0 text-lg font-bold text-brand" aria-hidden>{t.number ? String(t.number).padStart(2, '0') : 'P'}</span>
                <div>
                  <h3 className="font-semibold">{t.title}</h3>
                  <p className="mt-0.5 text-sm text-muted">{t.description}</p>
                  <p className="mt-1 text-xs text-muted">{t.module_count ? `${t.module_count} modul terbit` : 'Modul sedang disiapkan'}</p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      {q.data?.demo && (
        <section className="bg-brand text-on-brand">
          <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-10">
            <div>
              <h2 className="text-2xl font-bold">Coba satu materi lengkap, gratis</h2>
              <p className="mt-1 opacity-90">“{q.data.demo.title}” — sekitar {q.data.demo.est_minutes} menit baca, tanpa perlu daftar.</p>
            </div>
            <Link to="/demo" className="rounded-lg bg-surface px-5 py-3 font-semibold text-brand-strong hover:opacity-90">Baca sekarang</Link>
          </div>
        </section>
      )}

      <section id="paket" className="mx-auto max-w-6xl scroll-mt-20 px-4 py-16">
        <h2 className="text-2xl font-bold sm:text-3xl">Pilih paket</h2>
        <p className="mt-2 max-w-2xl text-muted">Harga dan masa akses diatur oleh admin dan dapat berubah.</p>
        {q.isLoading && <div className="mt-8 grid gap-4 md:grid-cols-3">{[0, 1, 2].map(i => <Skeleton key={i} className="h-72" />)}</div>}
        {q.data && (
          <div className="mt-8 grid gap-4 md:grid-cols-3">
            {q.data.plans.map((p: any) => {
              const featured = p.access_level === 1
              return (
                <div key={p.id} className={cx('flex flex-col rounded-2xl border p-6', featured ? 'border-brand bg-surface ring-2 ring-brand' : 'border-line bg-surface')}>
                  <div className="flex items-center justify-between"><h3 className="text-lg font-bold">{p.name}</h3>{featured && <Badge tone="brand">Paling lengkap untuk persiapan</Badge>}</div>
                  <p className="mt-3 text-3xl font-extrabold">{rupiah(p.price)}</p>
                  <p className="text-sm text-muted">{p.duration_days ? `Akses ${p.duration_days} hari` : 'Tanpa batas waktu'}</p>
                  <p className="mt-3 text-sm text-muted">{p.description}</p>
                  <ul className="mt-4 flex-1 space-y-2 text-sm">
                    {p.features.map((f: string) => <li key={f} className="flex gap-2"><span aria-hidden className="text-brand">✓</span>{f}</li>)}
                  </ul>
                  <LinkButton to="/daftar" variant={featured ? 'primary' : 'secondary'} className="mt-6">{p.price === 0 ? 'Daftar gratis' : `Daftar lalu pilih ${LEVEL[p.access_level] ?? p.name}`}</LinkButton>
                </div>
              )
            })}
          </div>
        )}
      </section>

      <section id="faq" className="mx-auto max-w-3xl scroll-mt-20 px-4 pb-20">
        <h2 className="text-2xl font-bold sm:text-3xl">Pertanyaan yang sering muncul</h2>
        <div className="mt-6 divide-y divide-line border-y border-line">
          {faqs.map(([qq, a]) => (
            <details key={qq} className="group py-4">
              <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold">{qq}<span aria-hidden className="text-muted group-open:rotate-45">+</span></summary>
              <p className="mt-2 text-muted">{a}</p>
            </details>
          ))}
        </div>
      </section>
    </>
  )
}
