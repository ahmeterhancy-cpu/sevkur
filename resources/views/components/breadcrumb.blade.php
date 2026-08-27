@props(['items' => [], 'title' => null])

<section class="bg-cream">
    <div class="container-x py-8 lg:py-12">
        @if ($title)
            <h1 class="text-[1.75rem] lg:text-[2.25rem] font-medium">{{ $title }}</h1>
        @endif

        <nav aria-label="breadcrumb" class="mt-2 text-[0.875rem] text-muted">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ localized_route('home') }}" class="hover:text-ink">{{ __('shop.home') }}</a></li>
                @foreach ($items as $label => $href)
                    <li aria-hidden="true">/</li>
                    <li>
                        @if ($href && ! $loop->last)
                            <a href="{{ $href }}" class="hover:text-ink">{{ $label }}</a>
                        @else
                            <span class="text-ink">{{ $label }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
</section>
