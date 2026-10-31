@if ($paginator->hasPages())
<nav aria-label="Pagination">
    <ul class="pagination">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if ($paginator->onFirstPage())
                <span class="page-link" aria-disabled="true" aria-label="Previous page">
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
            @endif
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            @if ($paginator->onFirstPage()) </span> @else </a> @endif
        </li>
        @if (method_exists($paginator, 'lastPage'))
            @php
                $pageCount = min(3, $paginator->lastPage());
                $firstPage = max(1, min($paginator->currentPage() - 1, $paginator->lastPage() - $pageCount + 1));
            @endphp
            @for ($page = $firstPage; $page < $firstPage + $pageCount; $page++)
                <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                    @if ($page === $paginator->currentPage())
                        <span class="page-link" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $paginator->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                </li>
            @endfor
        @endif
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if ($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
            @else
                <span class="page-link" aria-disabled="true" aria-label="Next page">
            @endif
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            @if ($paginator->hasMorePages()) </a> @else </span> @endif
        </li>
    </ul>
</nav>
@endif
