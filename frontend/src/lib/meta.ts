import { useEffect } from 'react'

/** Judul halaman + robots. Halaman belajar privat otomatis noindex. */
export function usePageMeta(title: string, opts: { noindex?: boolean; description?: string } = {}) {
  useEffect(() => {
    document.title = title.includes('OSN Biology Academy') ? title : `${title} — OSN Biology Academy`
    let robots = document.querySelector('meta[name="robots"]') as HTMLMetaElement | null
    if (opts.noindex) {
      if (!robots) { robots = document.createElement('meta'); robots.name = 'robots'; document.head.appendChild(robots) }
      robots.content = 'noindex, nofollow'
    } else robots?.remove()
    if (opts.description) document.querySelector('meta[name="description"]')?.setAttribute('content', opts.description)
  }, [title, opts.noindex, opts.description])
}
