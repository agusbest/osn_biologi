<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitizer HTML berbasis allowlist untuk keluaran rich-text editor (cegah XSS).
 * Rumus KaTeX ditulis sebagai teks ($...$), jadi tidak butuh tag khusus.
 */
class HtmlSanitizer
{
    private const TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'li',
        'h2', 'h3', 'h4', 'blockquote', 'code', 'pre', 'a', 'img', 'table', 'thead',
        'tbody', 'tr', 'th', 'td', 'hr', 'figure', 'figcaption', 'span', 'div',
    ];

    private const ATTRS = [
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    /** Tag berbahaya: dibuang beserta isinya. */
    private const DROP = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input',
        'button', 'textarea', 'select', 'link', 'meta', 'base', 'noscript', 'template',
        'frame', 'frameset', 'applet', 'audio', 'video', 'canvas',
    ];

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementsByTagName('div')->item(0);
        if (! $root) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES, 'UTF-8');
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                /** @var DOMElement $child */
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP, true)) {
                    $node->removeChild($child);
                    continue;
                }

                self::walk($child);

                if (! in_array($tag, self::TAGS, true)) {
                    // Tag tak dikenal: buang tag, pertahankan isi yang sudah bersih.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                self::cleanAttributes($child, $tag);
            } elseif ($child->nodeType !== XML_TEXT_NODE) {
                $node->removeChild($child); // komentar, CDATA, PI, dsb.
            }
        }
    }

    private static function cleanAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ATTRS[$tag] ?? [];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            if (! in_array($name, $allowed, true)) {
                $el->removeAttribute($attr->name);
                continue;
            }
            if ($name === 'href' && ! self::safeUrl($attr->value, '#^(https?://|mailto:|/(?!/)|\#)#i')) {
                $el->removeAttribute($attr->name);
            }
            if ($name === 'src' && ! self::safeUrl($attr->value, '#^(https?://|/(?!/))#i')) {
                $el->removeAttribute($attr->name);
            }
            if (in_array($name, ['width', 'height', 'colspan', 'rowspan'], true) && ! ctype_digit($attr->value)) {
                $el->removeAttribute($attr->name);
            }
        }

        if ($tag === 'a' && $el->hasAttribute('href')) {
            $el->setAttribute('rel', 'noopener noreferrer nofollow');
            $el->setAttribute('target', '_blank');
        }
        if ($tag === 'img' && ! $el->hasAttribute('src')) {
            $el->parentNode?->removeChild($el);
        }
    }

    private static function safeUrl(string $url, string $pattern): bool
    {
        $compact = preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $compact !== '' && (bool) preg_match($pattern, $compact);
    }
}
