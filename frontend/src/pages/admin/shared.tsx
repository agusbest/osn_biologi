import { useState } from 'react'
import { ApiError, patch } from '../../lib/api'
import { STATUS_LABEL } from '../../lib/format'
import { Badge, Button } from '../../components/ui'

export const TRANSITIONS: Record<string, [string, string][]> = {
  draft: [['review', 'Kirim ke tinjauan']],
  review: [['published', 'Terbitkan'], ['draft', 'Kembalikan ke draft']],
  published: [['archived', 'Arsipkan'], ['review', 'Tarik ke tinjauan']],
  archived: [['draft', 'Buka sebagai draft']],
}
export const statusTone = { draft: 'neutral', review: 'amber', published: 'ok', archived: 'danger' } as const

export function StatusBadge({ status }: { status: string }) {
  return <Badge tone={statusTone[status as keyof typeof statusTone] ?? 'neutral'}>{STATUS_LABEL[status] ?? status}</Badge>
}

/** Alur status konten: draft → review → published → archived. */
export function StatusControl({ type, id, status, onChanged }: { type: string; id: number; status: string; onChanged: (s: string) => void }) {
  const [busy, setBusy] = useState(false)
  const [errs, setErrs] = useState<string[]>([])
  const go = async (to: string) => {
    setBusy(true); setErrs([])
    try { const r = await patch(`/admin/${type}/${id}/status`, { status: to }); onChanged(r.status) }
    catch (e) {
      if (e instanceof ApiError) {
        const list = (e as any).errors?.status
        setErrs(list?.length ? [e.message, ...list] : [e.friendly])
      } else setErrs(['Gagal mengubah status.'])
    } finally { setBusy(false) }
  }
  return (
    <div>
      <div className="flex flex-wrap items-center gap-2">
        <StatusBadge status={status} />
        {(TRANSITIONS[status] ?? []).map(([to, label]) => (
          <Button key={to} size="sm" variant={to === 'published' ? 'primary' : 'secondary'} loading={busy} onClick={() => go(to)}>{label}</Button>
        ))}
      </div>
      {errs.length > 0 && (
        <ul role="alert" className="mt-2 list-disc rounded-lg bg-danger-soft py-2 pl-7 pr-3 text-sm text-danger">{errs.map((e, i) => <li key={i}>{e}</li>)}</ul>
      )}
    </div>
  )
}

export function useSave() {
  const [state, setState] = useState<{ busy: boolean; error: unknown; ok: boolean }>({ busy: false, error: null, ok: false })
  const run = async <T,>(fn: () => Promise<T>): Promise<T | undefined> => {
    setState({ busy: true, error: null, ok: false })
    try { const r = await fn(); setState({ busy: false, error: null, ok: true }); return r }
    catch (e) { setState({ busy: false, error: e, ok: false }); return undefined }
  }
  return { ...state, run }
}

export const errList = (e: unknown): string[] => {
  if (!(e instanceof ApiError)) return e ? ['Terjadi kesalahan.'] : []
  const all = Object.values(e.errors).flat()
  return all.length ? all : [e.friendly]
}

export function FormErrors({ error }: { error: unknown }) {
  const l = errList(error)
  if (!l.length) return null
  return <ul role="alert" className="list-disc rounded-lg bg-danger-soft py-2 pl-7 pr-3 text-sm text-danger">{l.map((x, i) => <li key={i}>{x}</li>)}</ul>
}
