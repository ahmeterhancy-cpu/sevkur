@if ($paginator->hasPages())
    <nav class="flex items-center justify-center gap-1.5" role="navigation" aria-label="pagination">
        @if ($paginator->onFirstPage())
            <span class="icon-btn opacity-40"><x-ui.icon name="chevron-left" :size="18" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="icon-btn" rel="prev" aria-label="prev">
                <x-ui.icon name="chevron-left" :size="18" />
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-2 text-muted">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="icon-btn is-active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="icon-btn">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="icon-btn" rel="next" aria-label="next">
                <x-ui.icon name="chevron-right" :size="18" />
            </a>
        @else
            <span class="icon-btn opacity-40"><x-ui.icon name="chevron-right" :size="18" /></span>
        @endif
    </nav>
@endif
