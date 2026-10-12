import { useEffect, useRef } from 'react'
import DOMPurify from 'dompurify'
import renderMathInElement from 'katex/contrib/auto-render'
import { cx } from './ui'

const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'blockquote', 'code', 'pre', 'a', 'img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'hr', 'figure', 'figcaption', 'span', 'div']
const ALLOWED_ATTR = ['href', 'src', 'alt', 'title', 'target', 'rel', 'colspan', 'rowspan', 'width', 'height']

export const sanitize = (html: string) => DOMPurify.sanitize(html, { ALLOWED_TAGS, ALLOWED_ATTR, ALLOW_DATA_ATTR: false })

/** Menampilkan HTML dari server (sudah disanitasi server) + sanitasi ulang di klien, lalu merender rumus KaTeX. */
export function RichText({ html, className }: { html?: string | null; className?: string }) {
  const ref = useRef<HTMLDivElement>(null)
  useEffect(() => {
    if (!ref.current) return
    ref.current.innerHTML = sanitize(html || '')
    try {
      renderMathInElement(ref.current, {
        delimiters: [
          { left: '$$', right: '$$', display: true },
          { left: '\\[', right: '\\]', display: true },
          { left: '$', right: '$', display: false },
          { left: '\\(', right: '\\)', display: false },
        ],
        throwOnError: false,
      })
    } catch { /* rumus rusak dibiarkan sebagai teks */ }
  }, [html])
  return <div ref={ref} className={cx('prose-bio', className)} />
}

/** Teks polos dengan rumus (mis. teks opsi jawaban). */
export function MathText({ text, className }: { text: string; className?: string }) {
  const ref = useRef<HTMLSpanElement>(null)
  useEffect(() => {
    if (!ref.current) return
    ref.current.textContent = text
    try { renderMathInElement(ref.current, { delimiters: [{ left: '$', right: '$', display: false }], throwOnError: false }) } catch { /* ignore */ }
  }, [text])
  return <span ref={ref} className={className} />
}
