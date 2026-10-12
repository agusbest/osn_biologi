<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_removes_scripts_handlers_and_dangerous_urls(): void
    {
        $out = HtmlSanitizer::clean(
            '<p onclick="x()">a</p><script>alert(1)</script><a href="javascript:alert(1)">l</a>'
            . '<img src="x" onerror="alert(1)"><iframe src="//evil"></iframe><svg onload=alert(1)></svg>'
        );

        foreach (['<script', 'onclick', 'javascript:', 'onerror', 'iframe', 'svg', 'alert'] as $bad) {
            $this->assertStringNotContainsString($bad, $out);
        }
        $this->assertStringContainsString('<p>a</p>', $out);
    }

    public function test_keeps_math_text_and_basic_formatting(): void
    {
        $out = HtmlSanitizer::clean('<p>Hasil <strong>$q^2 = 0{,}09$</strong> dan $$v=\frac{a}{b}$$</p>');
        $this->assertStringContainsString('$q^2 = 0{,}09$', $out);
        $this->assertStringContainsString('\frac{a}{b}', $out);
        $this->assertStringContainsString('<strong>', $out);
    }

    public function test_links_get_safe_rel_and_encoded_javascript_is_removed(): void
    {
        $out = HtmlSanitizer::clean('<a href="https://ok.example">ok</a><a href=" jav&#x61;script:alert(1)">bad</a>');
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $out);
        $this->assertStringNotContainsString('javascript', $out);
    }

    public function test_handles_null_and_empty(): void
    {
        $this->assertNull(HtmlSanitizer::clean(null));
        $this->assertSame('', HtmlSanitizer::clean('   '));
    }
}
