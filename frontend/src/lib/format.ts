export const rupiah = (n: number) => (n === 0 ? 'Gratis' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n))
export const dateID = (d?: string | null) => (d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—')
export const dateTimeID = (d?: string | null) => (d ? new Date(d).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : '—')
export const pct = (n?: number | null) => (n === null || n === undefined ? '—' : `${Math.round(n)}%`)
export const DIFFICULTY: Record<string, string> = { fondasi: 'Fondasi', pendalaman: 'Pendalaman', tantangan: 'Tantangan OSN' }
export const QTYPE: Record<string, string> = { mcq: 'Pilihan ganda', truefalse: 'Benar/Salah', essay: 'Uraian' }
export const STATUS_LABEL: Record<string, string> = { draft: 'Draft', review: 'Ditinjau', published: 'Terbit', archived: 'Diarsipkan' }
export const LEVEL: Record<number, string> = { 0: 'Free', 1: 'Full Course', 2: 'Premium' }
export const mmss = (s: number) => `${Math.floor(s / 60)}:${String(Math.max(0, Math.floor(s % 60))).padStart(2, '0')}`
