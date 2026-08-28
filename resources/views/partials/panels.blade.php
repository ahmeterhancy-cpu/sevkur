@php
    $cartService = app(\App\Services\CartService::class);
    $panelCart = $cartService->current();
    $panelTotals = $cartService->totals();
@endphp

{{-- --------------------------------------------------------------- mini sepet --}}
<div class="panel" id="mini-cart-panel" aria-hidden="true" role="dialog" aria-label="{{ __('shop.cart') }}">
    <div class="panel__backdrop"></div>
    <div class="panel__body">
        <div class="flex items-center justify-between px-5 h-16 border-b border-line">
            <h2 class="font-medium">{{ __('shop.cart') }}</h2>
            <button type="button" data-panel-close aria-label="{{ __('shop.close') }}" class="p-2 hover:text-brand">
                <x-ui.icon name="close" :size="22" />
            </button>
        </div>
        <div data-mini-cart class="flex-1 overflow-y-auto">
            @include('partials.mini-cart', ['cart' => $panelCart, 'totals' => $panelTotals])
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------------ arama --}}
<div class="panel panel--top" id="search-panel" aria-hidden="true" role="dialog" aria-label="{{ __('shop.search') }}">
    <div class="panel__backdrop"></div>
    <div class="panel__body">
        <div class="container-x py-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-medium">{{ __('shop.search') }}</h2>
                <button type="button" data-panel-close aria-label="{{ __('shop.close') }}" class="p-2 hover:text-brand">
                    <x-ui.icon name="close" :size="22" />
                </button>
            </div>
            <form action="{{ localized_route('search') }}" method="GET" class="relative">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('shop.search_placeholder') }}"
                       class="field h-14 pr-14 rounded-full" aria-label="{{ __('shop.search') }}">
                <button type="submit" class="absolute right-2 top-2 bottom-2 w-11 flex items-center justify-center rounded-full bg-ink text-white">
                    <x-ui.icon name="search" :size="20" />
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ------------------------------------------------------------- mobil menu --}}
<div class="panel panel--left" id="menu-panel" aria-hidden="true" role="dialog" aria-label="{{ __('shop.menu') }}">
    <div class="panel__backdrop"></div>
    <div class="panel__body">
        <div class="flex items-center justify-between px-5 h-16 border-b border-line">
            <span class="font-medium">{{ __('shop.menu') }}</span>
            <button type="button" data-panel-close aria-label="{{ __('shop.close') }}" class="p-2 hover:text-brand">
                <x-ui.icon name="close" :size="22" />
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-2">
            <a href="{{ localized_route('home') }}" class="block px-5 py-3 border-b border-line-soft">{{ __('shop.home') }}</a>
            <a href="{{ localized_route('shop') }}" class="block px-5 py-3 border-b border-line-soft">{{ __('shop.shop') }}</a>



            @foreach ($layoutMenu as $item)
                @php $submenu = $item->submenu(); @endphp
                <div data-submenu-item class="border-b border-line-soft">
                    <div class="flex items-center">
                        <a href="{{ $item->href() }}" class="flex-1 px-5 py-3">{{ $item->t('label') }}</a>
                        @if ($submenu !== [])
                            <button type="button" data-submenu-toggle class="px-4 py-3" aria-label="{{ $item->t('label') }}">
                                <x-ui.icon name="chevron-down" :size="18" />
                            </button>
                        @endif
                    </div>
                    @if ($submenu !== [])
                        <div class="submenu bg-cream">
                            @foreach ($submenu as $child)
                                <a href="{{ $child['href'] }}"
                                   class="block pl-9 pr-5 py-2.5 text-[0.875rem] text-muted">{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <a href="{{ localized_route('blog') }}" class="block px-5 py-3 border-b border-line-soft">{{ __('shop.blog') }}</a>
            <a href="{{ localized_route('contact') }}" class="block px-5 py-3 border-b border-line-soft">{{ __('shop.contact') }}</a>
            <a href="{{ localized_route('track') }}" class="block px-5 py-3 border-b border-line-soft">{{ __('shop.track_order') }}</a>
            <a href="{{ auth()->check() ? localized_route('account.index') : localized_route('login') }}" class="block px-5 py-3 border-b border-line-soft">
                {{ auth()->check() ? __('shop.my_account') : __('shop.login') }}
            </a>
        </div>

        <div class="px-5 py-4 border-t border-line space-y-3">
            <div>
                <span class="text-[0.75rem] uppercase tracking-widest text-muted">{{ __('shop.language') }}</span>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach (\App\Support\Locales::nativeNames() as $code => $native)
                        <a href="{{ locale_switch_url($code) }}"
                           class="btn btn-sm {{ $code === app()->getLocale() ? 'btn-primary' : 'btn-ghost' }}">{{ $native }}</a>
                    @endforeach
                </div>
            </div>
            <div>
                <span class="text-[0.75rem] uppercase tracking-widest text-muted">{{ __('shop.currency') }}</span>
                <div class="flex flex-wrap gap-2 mt-2">
                    @foreach ($layoutCurrencies as $currency)
                        <form method="POST" action="{{ localized_route('currency.switch') }}">
                            @csrf
                            <input type="hidden" name="code" value="{{ $currency->code }}">
                            <button type="submit" class="btn btn-sm {{ $currency->code === app(\App\Services\CurrencyService::class)->current()->code ? 'btn-primary' : 'btn-ghost' }}">
                                {{ $currency->code }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
