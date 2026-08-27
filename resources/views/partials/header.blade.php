@php
    $favorites = app(\App\Services\FavoritesService::class);
    $cartService = app(\App\Services\CartService::class);
    $currentCurrency = app(\App\Services\CurrencyService::class)->current();
    $cartCount = $cartService->count();
    $wishCount = $favorites->wishlistCount();
    $compareCount = $favorites->compareCount();
    $freeOver = (float) setting('free_shipping_over', 0);
@endphp

{{-- ------------------------------------------------------------- ust serit --}}
<div class="hidden lg:block border-b border-line text-[0.8125rem] text-muted">
    <div class="container-x flex items-center justify-between h-11">
        <div class="flex items-center gap-2">
            <x-ui.icon name="truck" :size="16" />
            @if ($freeOver > 0)
                <span>{{ __('shop.free_shipping_over', ['amount' => money($freeOver)]) }}</span>
            @else
                <span>{{ setting_t('top_bar_text', __('shop.footer_about')) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-6">
            @if (setting('phone'))
                <a href="tel:{{ preg_replace('/\s+/', '', setting('phone')) }}" class="flex items-center gap-2 hover:text-ink">
                    <x-ui.icon name="phone" :size="15" /> {{ setting('phone') }}
                </a>
            @endif

            <a href="{{ localized_route('track') }}" class="hover:text-ink">{{ __('shop.track_order') }}</a>

            {{-- Para birimi --}}
            <div class="relative has-drop">
                <button type="button" class="flex items-center gap-1 hover:text-ink" aria-haspopup="true">
                    {{ $currentCurrency->code }} <x-ui.icon name="chevron-down" :size="14" />
                </button>
                <div class="drop right-0 left-auto min-w-[9rem]">
                    @foreach ($layoutCurrencies as $currency)
                        <form method="POST" action="{{ localized_route('currency.switch') }}">
                            @csrf
                            <input type="hidden" name="code" value="{{ $currency->code }}">
                            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-cream {{ $currency->code === $currentCurrency->code ? 'text-ink font-medium' : '' }}">
                                {{ $currency->symbol }} {{ $currency->code }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            {{-- Dil --}}
            <div class="relative has-drop">
                <button type="button" class="flex items-center gap-1 hover:text-ink uppercase" aria-haspopup="true">
                    {{ app()->getLocale() }} <x-ui.icon name="chevron-down" :size="14" />
                </button>
                <div class="drop right-0 left-auto min-w-[10rem]">
                    @foreach (\App\Support\Locales::nativeNames() as $code => $native)
                        <a href="{{ locale_switch_url($code) }}"
                           class="block px-4 py-2 hover:bg-cream {{ $code === app()->getLocale() ? 'text-ink font-medium' : '' }}">
                            {{ $native }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- --------------------------------------------------------------- ana serit --}}
<header class="site-header bg-white" data-sticky-header>
    <div class="container-x">
        <div class="flex items-center gap-4 lg:gap-10 h-[4.5rem] lg:h-[5.5rem]">
            <button type="button" class="lg:hidden -ml-1 p-2" data-panel-open="menu-panel" aria-label="{{ __('shop.menu') }}">
                <x-ui.icon name="menu" :size="24" />
            </button>

            <a href="{{ localized_route('home') }}" class="flex items-center gap-2 shrink-0">
                @if (setting('logo'))
                    <img src="{{ media_url(setting('logo')) }}" alt="{{ setting('site_name', __('shop.brand')) }}" class="h-8 lg:h-9 w-auto">
                @else
                    <span class="text-[1.35rem] lg:text-[1.6rem] font-semibold tracking-tight leading-none">
                        {{ setting('site_name', 'Sevkur') }}<span class="text-brand">.</span>
                    </span>
                @endif
            </a>

            {{-- Arama --}}
            <form action="{{ localized_route('search') }}" method="GET" class="hidden lg:flex flex-1 max-w-xl relative">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="{{ __('shop.search_placeholder') }}"
                       class="field pr-12 rounded-full border-line h-12" aria-label="{{ __('shop.search') }}">
                <button type="submit" class="absolute right-1 top-1 bottom-1 w-10 flex items-center justify-center rounded-full hover:text-brand" aria-label="{{ __('shop.search') }}">
                    <x-ui.icon name="search" :size="20" />
                </button>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-3">
                <button type="button" class="lg:hidden p-2" data-panel-open="search-panel" aria-label="{{ __('shop.search') }}">
                    <x-ui.icon name="search" :size="22" />
                </button>

                <a href="{{ auth()->check() ? localized_route('account.index') : localized_route('login') }}"
                   class="hidden sm:flex items-center gap-2 p-2 hover:text-brand" title="{{ __('shop.my_account') }}">
                    <x-ui.icon name="user" :size="22" />
                    <span class="hidden xl:block text-[0.8125rem] leading-tight">
                        <span class="block text-muted">{{ __('shop.my_account') }}</span>
                        <span class="block font-medium">{{ auth()->check() ? auth()->user()->firstName() : __('shop.login') }}</span>
                    </span>
                </a>

                <a href="{{ localized_route('compare') }}" class="relative hidden sm:block p-2 hover:text-brand" title="{{ __('shop.compare') }}">
                    <x-ui.icon name="compare" :size="22" />
                    <span data-compare-count class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-ink text-white text-[0.6875rem] flex items-center justify-center {{ $compareCount === 0 ? 'hidden' : '' }}">{{ $compareCount }}</span>
                </a>

                <a href="{{ localized_route('wishlist') }}" class="relative p-2 hover:text-brand" title="{{ __('shop.wishlist') }}">
                    <x-ui.icon name="heart" :size="22" />
                    <span data-wishlist-count class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-ink text-white text-[0.6875rem] flex items-center justify-center {{ $wishCount === 0 ? 'hidden' : '' }}">{{ $wishCount }}</span>
                </a>

                <button type="button" class="relative p-2 hover:text-brand" data-panel-open="mini-cart-panel" title="{{ __('shop.cart') }}">
                    <x-ui.icon name="cart" :size="22" />
                    <span data-cart-count class="absolute -top-0.5 -right-0.5 min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-brand text-white text-[0.6875rem] flex items-center justify-center {{ $cartCount === 0 ? 'hidden' : '' }}">{{ $cartCount }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------- gezinme --}}
    <nav class="hidden lg:block border-t border-line" aria-label="{{ __('shop.menu') }}">
        <div class="container-x">
            <ul class="flex items-center gap-8 h-14 text-[0.9375rem]">
                {{-- Tum kategoriler mega menusu --}}
                <li class="relative has-mega">
                    <button type="button" class="flex items-center gap-2 font-medium h-14">
                        <x-ui.icon name="menu" :size="18" />
                        {{ __('shop.all_categories') }}
                        <x-ui.icon name="chevron-down" :size="14" />
                    </button>
                    <div class="mega">
                        <div class="container-x py-8 grid grid-cols-4 gap-8">
                            @foreach ($layoutCategories as $category)
                                <div>
                                    <a href="{{ localized_route('category', ['category' => $category->slug]) }}"
                                       class="block font-medium mb-3 hover:text-brand">{{ $category->t('name') }}</a>
                                    <ul class="space-y-2 text-[0.875rem] text-muted">
                                        @foreach ($category->activeChildren as $child)
                                            <li>
                                                <a href="{{ localized_route('category', ['category' => $child->slug]) }}"
                                                   class="hover:text-ink">{{ $child->t('name') }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </li>

                <li><a href="{{ localized_route('home') }}" class="link-underline">{{ __('shop.home') }}</a></li>
                <li><a href="{{ localized_route('shop') }}" class="link-underline">{{ __('shop.shop') }}</a></li>

                @foreach ($layoutMenu as $item)
                    <li class="relative {{ $item->children->isNotEmpty() ? 'has-drop' : '' }}">
                        <a href="{{ $item->href() }}" class="link-underline flex items-center gap-1">
                            {{ $item->t('label') }}
                            @if ($item->children->isNotEmpty())
                                <x-ui.icon name="chevron-down" :size="14" />
                            @endif
                        </a>
                        @if ($item->children->isNotEmpty())
                            <div class="drop">
                                @foreach ($item->children as $child)
                                    <a href="{{ $child->href() }}" class="block px-4 py-2 text-[0.875rem] hover:bg-cream">{{ $child->t('label') }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach

                <li><a href="{{ localized_route('blog') }}" class="link-underline">{{ __('shop.blog') }}</a></li>
                <li><a href="{{ localized_route('contact') }}" class="link-underline">{{ __('shop.contact') }}</a></li>

                <li class="ml-auto flex items-center gap-2 text-[0.8125rem] text-muted">
                    <x-ui.icon name="headset" :size="18" class="text-brand" />
                    <span>{{ __('shop.need_help') }}</span>
                    <a href="{{ localized_route('faq') }}" class="font-medium text-ink link-underline">{{ __('shop.faq') }}</a>
                </li>
            </ul>
        </div>
    </nav>
</header>
