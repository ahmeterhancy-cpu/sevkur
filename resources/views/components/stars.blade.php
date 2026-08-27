@props(['rating' => 0, 'size' => 14, 'showCount' => false, 'count' => 0])

@php($value = max(0, min(5, (float) $rating)))

<span class="inline-flex items-center gap-1.5">
    <span class="stars" role="img" aria-label="{{ number_format($value, 1) }} / 5">
        @for ($i = 1; $i <= 5; $i++)
            <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" aria-hidden="true"
                 fill="{{ $i <= round($value) ? 'currentColor' : 'none' }}"
                 stroke="currentColor" stroke-width="1.4" stroke-linejoin="round">
                <path d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8Z"/>
            </svg>
        @endfor
    </span>
    @if ($showCount)
        <span class="text-[0.8125rem] text-muted">({{ $count }})</span>
    @endif
</span>
