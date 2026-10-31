<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

class AdminPaginationTest extends TestCase
{
    public function test_numbered_pagination_shows_at_most_three_pages_and_icon_navigation(): void
    {
        foreach ([1 => [1, 2, 3], 54 => [53, 54, 55], 107 => [105, 106, 107]] as $current => $expected) {
            $paginator = new LengthAwarePaginator([], 1070, 10, $current, ['path' => '/records']);
            $html = $paginator->links()->toHtml();
            preg_match_all('/class="page-link"[^>]*>\s*(\d+)\s*</', $html, $numbers);
            $this->assertSame($expected, array_map('intval', $numbers[1]));
            $this->assertStringContainsString('aria-label="Previous page"', $html);
            $this->assertStringContainsString('aria-label="Next page"', $html);
            $this->assertSame(2, substr_count($html, '<svg'));
            $this->assertStringNotContainsString('>Previous<', $html);
            $this->assertStringNotContainsString('>Next<', $html);
        }
    }

    public function test_short_and_simple_pagination_keep_working(): void
    {
        $twoPages = new LengthAwarePaginator([], 20, 10, 1);
        $this->assertStringContainsString('aria-label="Page 2"', $twoPages->links()->toHtml());
        $onePage = new LengthAwarePaginator([], 1, 10, 1);
        $this->assertStringNotContainsString('<nav', $onePage->links()->toHtml());
        $simple = new Paginator(range(1, 11), 10);
        $this->assertStringContainsString('rel="next"', $simple->links()->toHtml());
    }
}
