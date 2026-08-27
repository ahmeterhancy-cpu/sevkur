@php($items = $layoutAnnouncements ?? collect())

@if ($items->isNotEmpty())
    <div class="bg-ink text-white text-[0.8125rem] py-2.5 overflow-hidden">
        <div class="marquee" aria-label="{{ __('shop.brand') }}">
            @for ($pass = 0; $pass < 2; $pass++)
                <div class="marquee__track" @if($pass === 1) aria-hidden="true" @endif>
                    @foreach ($items as $item)
                        @php($text = $item->t('text'))
                        @if ($text !== '')
                            <span class="flex items-center gap-3 whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand inline-block"></span>
                                @if ($item->link)
                                    <a href="{{ $item->link }}" class="link-underline">{{ $text }}</a>
                                @else
                                    {{ $text }}
                                @endif
                            </span>
                        @endif
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
@endif
