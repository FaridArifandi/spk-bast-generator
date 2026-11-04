@if ($paginator->hasPages())
<nav class="pager" role="navigation" aria-label="Halaman">
    <span class="muted small">Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }}</span>
    <span class="row">
        @if ($paginator->onFirstPage())<span class="dis">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="dis">{{ $element }}</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="cur">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>@else<span class="dis">›</span>@endif
    </span>
</nav>
@endif
