@props(['name', 'size' => 20, 'stroke' => 1.5])

@php
    $paths = [
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user' => '<path d="M20 21a8 8 0 1 0-16 0"/><circle cx="12" cy="8" r="4"/>',
        'heart' => '<path d="M12 20s-7-4.35-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.65 12 20 12 20Z"/>',
        'compare' => '<path d="M4 7h9M4 17h9"/><path d="m16 4 4 3-4 3"/><path d="m8 14-4 3 4 3"/>',
        'cart' => '<path d="M4 5h2l2.2 10.4a1 1 0 0 0 1 .8h7.7a1 1 0 0 0 1-.77L20 8H7"/><circle cx="10" cy="19.5" r="1.2"/><circle cx="17.5" cy="19.5" r="1.2"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-left' => '<path d="m15 6-6 6 6 6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'eye' => '<path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="2.5"/>',
        'trash' => '<path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/>',
        'check' => '<path d="m5 13 4 4L19 7"/>',
        'truck' => '<path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17.5" cy="18" r="1.6"/>',
        'shield' => '<path d="M12 3 5 6v6c0 4 3 7.4 7 9 4-1.6 7-5 7-9V6l-7-3Z"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-.6 4"/><path d="M20 5v6h-6"/>',
        'headset' => '<path d="M4 13a8 8 0 1 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2Z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2Z"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a12 12 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'pin' => '<path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'star' => '<path d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8Z"/>',
        'facebook' => '<path d="M14 8h2V5h-2a4 4 0 0 0-4 4v2H8v3h2v6h3v-6h2.2l.8-3H13V9a1 1 0 0 1 1-1Z"/>',
        'instagram' => '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.4"/><circle cx="16.8" cy="7.2" r="0.9" fill="currentColor" stroke="none"/>',
        'youtube' => '<rect x="3" y="6" width="18" height="12" rx="4"/><path d="m11 9.5 4 2.5-4 2.5z"/>',
        'whatsapp' => '<path d="M20 12a8 8 0 0 1-11.9 7L4 20l1.1-3.9A8 8 0 1 1 20 12Z"/><path d="M9.5 9.8c.3 2 2.7 4.4 4.7 4.7l1-1.2 1.6.8-.5 1.4c-2.7.6-6.6-3.3-6-6l1.4-.5.8 1.6z" fill="currentColor" stroke="none"/>',
        'arrow-right' => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'package' => '<path d="m12 3 8 4v10l-8 4-8-4V7l8-4Z"/><path d="m4 7 8 4 8-4M12 11v10"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7"/><rect x="13" y="4" width="7" height="7"/><rect x="4" y="13" width="7" height="7"/><rect x="13" y="13" width="7" height="7"/>',
        'list' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'inline-block shrink-0']) }}
     width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none"
     stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">
    {!! $paths[$name] ?? '' !!}
</svg>
