@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled">&laquo; Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo; Previous</a>
        @endif

        <span class="muted">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next &raquo;</a>
        @else
            <span class="disabled">Next &raquo;</span>
        @endif
    </nav>
@endif
