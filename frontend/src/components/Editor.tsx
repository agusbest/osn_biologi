import { useEffect, useRef, useState } from 'react'
import { EditorContent, useEditor } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import Image from '@tiptap/extension-image'
import { api } from '../lib/api'
import { RichText } from './RichText'
import { cx } from './ui'

type Props = { value: string; onChange: (html: string) => void; label: string; lessonId?: number; minimal?: boolean }

const Btn = ({ on, active, label, children }: { on: () => void; active?: boolean; label: string; children: React.ReactNode }) => (
  <button type="button" onClick={on} aria-label={label} title={label} aria-pressed={active}
    className={cx('rounded-md px-2 py-1 text-sm font-semibold hover:bg-sunken', active && 'bg-brand-soft text-brand-strong')}>{children}</button>
)

/** Editor rich-text (TipTap). Rumus ditulis sebagai $...$ atau $$...$$ dan dirender KaTeX di pratinjau & halaman siswa. */
export function Editor({ value, onChange, label, lessonId, minimal }: Props) {
  const [preview, setPreview] = useState(false)
  const [busy, setBusy] = useState(false)
  const [err, setErr] = useState('')
  const fileRef = useRef<HTMLInputElement>(null)

  const editor = useEditor({
    extensions: [StarterKit.configure({ link: { openOnClick: false } }), Image],
    content: value || '',
    onUpdate: ({ editor }) => onChange(editor.isEmpty ? '' : editor.getHTML()),
  })

  useEffect(() => () => { editor?.destroy() }, [editor])
  if (!editor) return null

  const insertText = (t: string) => editor.chain().focus().insertContent(t).run()
  const setLink = () => {
    const prev = editor.getAttributes('link').href as string | undefined
    const url = window.prompt('Alamat tautan (https://…). Kosongkan untuk menghapus.', prev || 'https://')
    if (url === null) return
    if (url === '' || url === 'https://') editor.chain().focus().unsetLink().run()
    else editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
  }
  const upload = async (file: File) => {
    const alt = window.prompt('Teks alternatif gambar (wajib, untuk pembaca layar):', '')
    if (!alt) { setErr('Teks alternatif wajib diisi.'); return }
    setBusy(true); setErr('')
    try {
      const fd = new FormData()
      fd.append('file', file); fd.append('alt_text', alt)
      if (lessonId) fd.append('lesson_id', String(lessonId))
      const r = await api('/admin/uploads', { method: 'POST', form: fd })
      editor.chain().focus().setImage({ src: r.url, alt }).run()
    } catch (e: any) {
      setErr(e?.errors?.file?.[0] || e?.message || 'Unggah gagal')
    } finally { setBusy(false) }
  }

  return (
    <div className="rounded-lg border border-line bg-surface">
      <div className="flex flex-wrap items-center gap-1 border-b border-line p-1.5" role="toolbar" aria-label={`Format ${label}`}>
        <Btn label="Tebal" active={editor.isActive('bold')} on={() => editor.chain().focus().toggleBold().run()}>B</Btn>
        <Btn label="Miring" active={editor.isActive('italic')} on={() => editor.chain().focus().toggleItalic().run()}><em>I</em></Btn>
        {!minimal && <Btn label="Judul bagian" active={editor.isActive('heading', { level: 3 })} on={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}>H</Btn>}
        <Btn label="Daftar poin" active={editor.isActive('bulletList')} on={() => editor.chain().focus().toggleBulletList().run()}>• Daftar</Btn>
        <Btn label="Daftar angka" active={editor.isActive('orderedList')} on={() => editor.chain().focus().toggleOrderedList().run()}>1. Daftar</Btn>
        {!minimal && <Btn label="Kutipan / catatan" active={editor.isActive('blockquote')} on={() => editor.chain().focus().toggleBlockquote().run()}>❝</Btn>}
        <Btn label="Tautan" active={editor.isActive('link')} on={setLink}>Tautan</Btn>
        <Btn label="Sisipkan rumus sebaris" on={() => insertText('$x$')}>$ƒ$</Btn>
        <Btn label="Sisipkan rumus blok" on={() => insertText('$$x$$')}>$$ƒ$$</Btn>
        <Btn label="Unggah gambar" on={() => fileRef.current?.click()}>{busy ? 'Mengunggah…' : 'Gambar'}</Btn>
        <input ref={fileRef} type="file" accept="image/png,image/jpeg,image/webp,image/gif" className="hidden"
          onChange={e => { const f = e.target.files?.[0]; if (f) upload(f); e.target.value = '' }} />
        <span className="ml-auto" />
        <Btn label="Pratinjau" active={preview} on={() => setPreview(p => !p)}>{preview ? 'Edit' : 'Pratinjau'}</Btn>
      </div>
      {err && <p role="alert" className="px-3 pt-2 text-xs font-medium text-danger">{err}</p>}
      {preview ? <div className="p-3"><RichText html={editor.getHTML()} /></div> : <div className="editor-area" aria-label={label}><EditorContent editor={editor} /></div>}
    </div>
  )
}
