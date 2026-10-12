import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { get } from '../lib/api'
import { usePageMeta } from '../lib/meta'
import { DIFFICULTY } from '../lib/format'
import { Badge, EmptyState, ErrorState, LinkButton, Loading } from '../components/ui'
import { RichText } from '../components/RichText'

export function Demo() {
  usePageMeta('Materi demo gratis', { description: 'Baca satu materi lengkap OSN Biologi secara gratis.' })
  const q = useQuery({ queryKey: ['demo'], queryFn: () => get('/public/demo'), retry: false })
  if (q.isLoading) return <Loading />
  if (q.isError) return <div className="px-4 py-16"><EmptyState title="Materi demo belum tersedia" action={<LinkButton to="/">Kembali ke beranda</LinkButton>}>Admin belum menerbitkan materi gratis.</EmptyState></div>
  const l = q.data.lesson
  return (
    <article className="mx-auto max-w-3xl px-4 py-10">
      <p className="text-sm text-muted">{l.topic} · {l.module}</p>
      <h1 className="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">{l.title}</h1>
      <div className="mt-3 flex flex-wrap gap-2"><Badge tone="brand">{DIFFICULTY[l.difficulty]}</Badge><Badge>{l.est_minutes} menit baca</Badge></div>
      <h2 className="mt-8 text-lg font-bold">Target pembelajaran</h2>
      <RichText html={l.objectives} />
      <h2 className="mt-8 text-lg font-bold">Penjelasan sederhana</h2>
      <RichText html={l.explanation} />
      <h2 className="mt-8 text-lg font-bold">Konsep wajib dipahami</h2>
      <RichText html={l.key_concepts} />
      <h2 className="mt-8 text-lg font-bold">Ringkasan cepat</h2>
      <RichText html={l.summary} />
      <div className="mt-10 rounded-2xl border border-line bg-sunken p-6">
        <p className="font-bold">Lanjut ke bagian “cara berpikir ala OSN”, contoh soal, dan latihan</p>
        <p className="mt-1 text-sm text-muted">Daftar gratis untuk menandai progres dan mengerjakan soal latihan.</p>
        <LinkButton to="/daftar" className="mt-4">Daftar gratis</LinkButton>
      </div>
    </article>
  )
}

const LEGAL = {
  syarat: {
    title: 'Syarat Penggunaan',
    body: [
      ['Penerimaan', 'Dengan membuat akun atau memakai OSN Biology Academy, Anda menyetujui syarat ini.'],
      ['Akun', 'Anda bertanggung jawab menjaga kerahasiaan kata sandi. Satu akun untuk satu orang; berbagi akun dapat menyebabkan akses dibatasi.'],
      ['Konten dan hak cipta', 'Materi, soal, dan pembahasan di platform dilindungi hak cipta. Dilarang menyalin, menjual ulang, atau mendistribusikan tanpa izin tertulis.'],
      ['Penggunaan wajar', 'Dilarang mengakses konten dengan cara otomatis, membagikan kunci jawaban, atau mencoba menembus pembatasan akses paket.'],
      ['Batasan layanan', 'Platform ini membantu belajar mandiri dan tidak menjamin hasil kompetisi. Praktikum virtual tidak menggantikan praktik laboratorium nyata.'],
      ['Perubahan', 'Syarat dapat diperbarui. Perubahan penting akan diumumkan di platform.'],
    ],
  },
  privasi: {
    title: 'Kebijakan Privasi',
    body: [
      ['Data yang kami simpan', 'Nama, email, kata sandi (disimpan dalam bentuk hash), progres belajar, jawaban latihan, serta riwayat pembayaran.'],
      ['Tujuan penggunaan', 'Menjalankan akun, menampilkan progres dan analisis kelemahan, memverifikasi pembayaran, dan menjaga keamanan layanan.'],
      ['Berbagi data', 'Data tidak dijual. Data hanya dibagikan bila diwajibkan hukum atau kepada penyedia layanan yang diperlukan untuk menjalankan platform.'],
      ['Hak Anda', 'Anda dapat meminta salinan, koreksi, atau penghapusan data akun dengan menghubungi admin.'],
      ['Keamanan', 'Akses dibatasi per akun, kata sandi di-hash, dan aktivitas administratif dicatat dalam audit log.'],
    ],
  },
  refund: {
    title: 'Ketentuan Refund',
    body: [
      ['Paket Free', 'Tidak ada pembayaran sehingga tidak ada refund.'],
      ['Paket berbayar', 'Ketentuan refund (jangka waktu, syarat, dan cara pengajuan) ditetapkan oleh pengelola sebelum platform dirilis. Isi bagian ini dengan kebijakan final.'],
      ['Cara mengajukan', 'Hubungi admin dengan menyertakan email akun dan referensi pembayaran.'],
    ],
  },
} as const

export function Legal({ kind }: { kind: keyof typeof LEGAL }) {
  const d = LEGAL[kind]
  usePageMeta(d.title)
  return (
    <article className="mx-auto max-w-2xl px-4 py-12">
      <h1 className="text-3xl font-extrabold tracking-tight">{d.title}</h1>
      <p role="note" className="mt-4 rounded-lg bg-amber-soft p-3 text-sm text-amber">Draf awal untuk pengembangan. Tinjau dan sesuaikan dengan konsultan hukum sebelum platform dibuka untuk umum.</p>
      <div className="mt-6 space-y-6">
        {d.body.map(([h, t]: readonly string[]) => (<section key={h}><h2 className="text-lg font-bold">{h}</h2><p className="mt-1 text-muted">{t}</p></section>))}
      </div>
    </article>
  )
}

export function NotFound() {
  usePageMeta('Halaman tidak ditemukan', { noindex: true })
  return <div className="px-4 py-24"><EmptyState title="Halaman tidak ditemukan" action={<Link to="/" className="font-semibold text-brand-strong underline">Kembali ke beranda</Link>}>Alamat yang Anda buka tidak ada atau sudah dipindahkan.</EmptyState></div>
}

export { ErrorState }
