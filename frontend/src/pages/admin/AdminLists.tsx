import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { ApiError, del, get, post, put, qs } from '../../lib/api'
import { usePageMeta } from '../../lib/meta'
import { DIFFICULTY, LEVEL, QTYPE, STATUS_LABEL } from '../../lib/format'
import { Button, EmptyState, ErrorState, Field, Input, LinkButton, Loading, Modal, PageHeader, Select, Textarea } from '../../components/ui'
import { FormErrors, StatusBadge, TRANSITIONS } from './shared'
import { patch } from '../../lib/api'

type Kind = 'topics' | 'modules' | 'lessons' | 'questions' | 'quizzes'

const TITLES: Record<Kind, string> = { topics: 'Bidang', modules: 'Modul', lessons: 'Materi', questions: 'Soal', quizzes: 'Kuis' }
const EDITOR: Partial<Record<Kind, string>> = { lessons: '/admin/materi', questions: '/admin/soal', quizzes: '/admin/kuis' }

function useOptions(path: string, enabled: boolean) {
  return useQuery({ queryKey: ['opt', path], queryFn: () => get(path + '?per_page=200'), enabled })
}

export default function AdminLists({ kind }: { kind: Kind }) {
  usePageMeta(TITLES[kind], { noindex: true })
  const qc = useQueryClient()
  const nav = useNavigate()
  const [f, setF] = useState({ q: '', status: '' })
  const [page, setPage] = useState(1)
  const [edit, setEdit] = useState<any>(null)
  const [rowErr, setRowErr] = useState<Record<number, string[]>>({})

  const list = useQuery({ queryKey: ['admin', kind, f, page], queryFn: () => get(`/admin/${kind}` + qs({ ...f, page, per_page: 30 })) })
  const topics = useOptions('/admin/topics', kind === 'modules')
  const reload = () => qc.invalidateQueries({ queryKey: ['admin', kind] })

  const remove = useMutation({
    mutationFn: (id: number) => del(`/admin/${kind}/${id}`),
    onSuccess: reload,
    onError: e => alert(e instanceof ApiError ? e.friendly : 'Gagal menghapus'),
  })
  const move = async (id: number, to: string) => {
    setRowErr(r => ({ ...r, [id]: [] }))
    try { await patch(`/admin/${kind}/${id}/status`, { status: to }); reload() }
    catch (e) { setRowErr(r => ({ ...r, [id]: e instanceof ApiError ? [e.message, ...(e.errors.status ?? [])] : ['Gagal'] })) }
  }

  const rows = list.data?.data ?? []
  const title = (r: any) => (kind === 'questions' ? (r.body || '').replace(/<[^>]+>/g, '').slice(0, 90) || `Soal #${r.id}` : r.title)
  const meta = (r: any) => {
    switch (kind) {
      case 'topics': return `${r.number ? 'Bidang ' + r.number : 'Praktikum'} · ${r.type === 'practicum' ? 'Jalur praktikum' : 'Teori'}`
      case 'modules': return `${r.topic?.title ?? ''} · ${LEVEL[r.access_level]}`
      case 'lessons': return `${r.module?.title ?? ''} · ${DIFFICULTY[r.difficulty]} · ${r.est_minutes} mnt`
      case 'questions': return `${r.topic?.title ?? ''} · ${QTYPE[r.type]} · ${DIFFICULTY[r.difficulty]} · ${LEVEL[r.access_level]}`
      case 'quizzes': return `${r.duration_minutes ? r.duration_minutes + ' menit' : 'Tanpa batas waktu'} · ${LEVEL[r.access_level]}`
    }
  }

  const addNew = () => (EDITOR[kind] ? nav(`${EDITOR[kind]}/baru`) : setEdit({}))

  return (
    <>
      <PageHeader title={TITLES[kind]} sub="Konten baru dimulai sebagai draft dan hanya terlihat siswa setelah diterbitkan.">
        <Button onClick={addNew}>Tambah {TITLES[kind].toLowerCase()}</Button>
      </PageHeader>
      <div className="mb-4 flex flex-wrap gap-2">
        <Input aria-label="Cari" placeholder="Cari…" value={f.q} onChange={e => { setF({ ...f, q: e.target.value }); setPage(1) }} className="max-w-xs" />
        <Select aria-label="Status" value={f.status} onChange={e => { setF({ ...f, status: e.target.value }); setPage(1) }} className="max-w-44">
          <option value="">Semua status</option>
          {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </Select>
      </div>

      {list.isLoading && <Loading />}
      {list.isError && <ErrorState error={list.error} onRetry={() => list.refetch()} />}
      {list.data && rows.length === 0 && <EmptyState title={`Belum ada ${TITLES[kind].toLowerCase()}`} action={<Button onClick={addNew}>Tambah {TITLES[kind].toLowerCase()}</Button>} />}
      <ul className="divide-y divide-line rounded-2xl border border-line bg-surface">
        {rows.map((r: any) => (
          <li key={r.id} className="p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="min-w-0">
                {EDITOR[kind] ? <Link to={`${EDITOR[kind]}/${r.id}`} className="font-semibold hover:underline">{title(r)}</Link> : <button className="text-left font-semibold hover:underline" onClick={() => setEdit(r)}>{title(r)}</button>}
                <p className="text-sm text-muted">{meta(r)}</p>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <StatusBadge status={r.status} />
                {(TRANSITIONS[r.status] ?? []).map(([to, label]) => <Button key={to} size="sm" variant="secondary" onClick={() => move(r.id, to)}>{label}</Button>)}
                {!EDITOR[kind] && <Button size="sm" variant="ghost" onClick={() => setEdit(r)}>Ubah</Button>}
                <Button size="sm" variant="ghost" onClick={() => { if (confirm('Hapus permanen? Tindakan ini tidak bisa dibatalkan.')) remove.mutate(r.id) }}>Hapus</Button>
              </div>
            </div>
            {rowErr[r.id]?.length > 0 && <ul role="alert" className="mt-2 list-disc rounded-lg bg-danger-soft py-2 pl-7 pr-3 text-sm text-danger">{rowErr[r.id].map((e, i) => <li key={i}>{e}</li>)}</ul>}
          </li>
        ))}
      </ul>
      {list.data && list.data.last_page > 1 && (
        <div className="mt-4 flex items-center justify-center gap-3">
          <Button variant="secondary" size="sm" disabled={page <= 1} onClick={() => setPage(page - 1)}>‹</Button>
          <span className="text-sm text-muted">Halaman {page} dari {list.data.last_page}</span>
          <Button variant="secondary" size="sm" disabled={page >= list.data.last_page} onClick={() => setPage(page + 1)}>›</Button>
        </div>
      )}

      {(kind === 'topics' || kind === 'modules') && edit && (
        <SimpleForm kind={kind} item={edit} topics={topics.data?.data ?? []} onClose={() => setEdit(null)} onSaved={() => { setEdit(null); reload() }} />
      )}
    </>
  )
}

function SimpleForm({ kind, item, topics, onClose, onSaved }: { kind: 'topics' | 'modules'; item: any; topics: any[]; onClose: () => void; onSaved: () => void }) {
  const isNew = !item.id
  const [v, setV] = useState<any>(kind === 'topics'
    ? { title: '', number: '', type: 'theory', description: '', sort: '', ...item }
    : { topic_id: '', title: '', summary: '', access_level: 0, sort: '', ...item })
  const set = (k: string) => (e: React.ChangeEvent<any>) => setV({ ...v, [k]: e.target.value })
  const m = useMutation({
    mutationFn: () => {
      const body: any = kind === 'topics'
        ? { title: v.title, number: v.number === '' || v.number === null ? null : +v.number, type: v.type, description: v.description || null, sort: v.sort === '' || v.sort === null ? null : +v.sort }
        : { topic_id: +v.topic_id, title: v.title, summary: v.summary || null, access_level: +v.access_level, sort: v.sort === '' || v.sort === null ? null : +v.sort }
      return isNew ? post(`/admin/${kind}`, body) : put(`/admin/${kind}/${item.id}`, body)
    },
    onSuccess: onSaved,
  })
  return (
    <Modal open onClose={onClose} title={`${isNew ? 'Tambah' : 'Ubah'} ${TITLES[kind].toLowerCase()}`}>
      <form onSubmit={e => { e.preventDefault(); m.mutate() }} className="space-y-4">
        <FormErrors error={m.error} />
        {kind === 'modules' && (
          <Field label="Bidang" htmlFor="tp"><Select id="tp" required value={v.topic_id} onChange={set('topic_id')}><option value="">Pilih bidang</option>{topics.map(t => <option key={t.id} value={t.id}>{t.title}</option>)}</Select></Field>
        )}
        <Field label="Judul" htmlFor="ti"><Input id="ti" required value={v.title} onChange={set('title')} /></Field>
        {kind === 'topics' ? (
          <>
            <div className="grid grid-cols-2 gap-3">
              <Field label="Nomor bidang" htmlFor="nm" hint="Kosongkan untuk jalur praktikum."><Input id="nm" type="number" min={0} max={99} value={v.number ?? ''} onChange={set('number')} /></Field>
              <Field label="Jenis" htmlFor="ty"><Select id="ty" value={v.type} onChange={set('type')}><option value="theory">Teori</option><option value="practicum">Jalur praktikum</option></Select></Field>
            </div>
            <Field label="Deskripsi" htmlFor="ds"><Textarea id="ds" value={v.description ?? ''} onChange={set('description')} /></Field>
          </>
        ) : (
          <>
            <Field label="Ringkasan" htmlFor="sm"><Textarea id="sm" value={v.summary ?? ''} onChange={set('summary')} /></Field>
            <Field label="Level akses" htmlFor="al" hint="Siswa tanpa paket yang sesuai tidak bisa membuka isi modul (dijaga di server)."><Select id="al" value={v.access_level} onChange={set('access_level')}>{Object.entries(LEVEL).map(([k, l]) => <option key={k} value={k}>{l}</option>)}</Select></Field>
          </>
        )}
        <Field label="Urutan" htmlFor="so" hint="Angka lebih kecil tampil lebih dulu."><Input id="so" type="number" min={0} value={v.sort ?? ''} onChange={set('sort')} /></Field>
        <div className="flex justify-end gap-2"><Button type="button" variant="secondary" onClick={onClose}>Batal</Button><Button type="submit" loading={m.isPending}>Simpan</Button></div>
      </form>
    </Modal>
  )
}

export { LinkButton }
