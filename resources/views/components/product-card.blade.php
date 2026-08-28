@props(['product'])

@php
    $favorites = app(\App\Services\FavoritesService::class);
    $inWishlist = in_array($product->id, $favorites->wishlistIds(), true);
    $inCompare = in_array($product->id, $favorites->compareIds(), true);
    $url = localized_route('product', ['product' => $product->slug]);
@endphp

<article {{ $attributes->class(['product-card group reveal']) }}>
    <div class="product-card__media">
        <a href="{{ $url }}" class="block w-full h-full" aria-label="{{ $product->t('name') }}">
            <img src="{{ media_url($product->cover) }}" alt="{{ $product->t('name') }}"
                 class="is-primary w-full h-full object-cover" loading="lazy">
            @if ($product->hover_image)
                <img src="{{ media_url($product->hover_image) }}" alt="" aria-hidden="true"
                     class="product-card__hover w-full h-full object-cover" loading="lazy">
            @endif
        </a>

        {{-- Rozetler --}}
        <div class="absolute top-3 left-3 flex flex-col gap-1.5 items-start">
            @if ($product->isOnSale())
                <span class="badge badge-sale">-%{{ $product->discount_percent }}</span>
            @endif
            @if ($product->is_new)
                <span class="badge badge-new">{{ __('shop.badge_new') }}</span>
            @endif
            @if ($product->is_trending)
                <span class="badge badge-trend">{{ __('shop.badge_trending') }}</span>
            @endif
        </div>

        {{-- Yan islemler --}}
        <div class="product-card__actions">
            <button type="button" class="icon-btn {{ $inWishlist ? 'is-active' : '' }}"
                    data-toggle-list="{{ localized_route('wishlist.toggle') }}"
                    data-product-id="{{ $product->id }}" data-badge="wishlist"
                    aria-pressed="{{ $inWishlist ? 'true' : 'false' }}"
                    title="{{ __('shop.add_to_wishlist') }}">
                <x-ui.icon name="heart" :size="17" />
            </button>
            <button type="button" class="icon-btn {{ $inCompare ? 'is-active' : '' }}"
                    data-toggle-list="{{ localized_route('compare.toggle') }}"
                    data-product-id="{{ $product->id }}" data-badge="compare"
                    aria-pressed="{{ $inCompare ? 'true' : 'false' }}"
                    title="{{ __('shop.add_to_compare') }}">
                <x-ui.icon name="compare" :size="17" />
            </button>
            <button type="button" class="icon-btn"
                    data-quick-view="{{ localized_route('product.quick', ['product' => $product->slug]) }}"
                    title="{{ __('shop.quick_view') }}">
                <x-ui.icon name="eye" :size="17" />
            </button>
        </div>

        {{-- Sepete ekle --}}
        <div class="absolute inset-x-3 bottom-3 translate-y-3 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300">
            @if ($product->isInStock())
                @if ($product->variants->isNotEmpty())
                    <a href="{{ $url }}" class="btn btn-primary w-full">{{ __('shop.select_variant') }}</a>
                @else
                    <form method="POST" action="{{ localized_route('cart.add') }}" data-add-to-cart>
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn btn-primary w-full">{{ __('shop.add_to_cart') }}</button>
                    </form>
                @endif
            @else
                <span class="btn btn-ghost w-full is-disabled">{{ __('shop.out_of_stock') }}</span>
            @endif
        </div>
    </div>

    <div class="pt-5 text-center">
        @if ($product->category)
            <a href="{{ localized_route('category', ['category' => $product->category->slug]) }}"
               class="text-[0.75rem] uppercase tracking-widest text-muted hover:text-brand">
                {{ $product->category->t('name') }}
            </a>
        @endif

        <h3 class="mt-1.5 text-[1.0625rem] font-normal leading-snug tracking-[-0.01em]">
            <a href="{{ $url }}" class="link-underline">{{ $product->t('name') }}</a>
        </h3>

        <div class="mt-1.5 flex items-center justify-center gap-2">
            <span class="price-now">{{ money($product->price) }}</span>
            @if ($product->isOnSale())
                <span class="price-old">{{ money($product->compare_at_price) }}</span>
            @endif
        </div>

        @if ($product->rating_count > 0)
            <div class="mt-1.5 flex justify-center">
                <x-stars :rating="$product->rating_avg" :show-count="true" :count="$product->rating_count" />
            </div>
        @endif

        @if ($product->delivery_days)
            <p class="mt-2 inline-flex items-center gap-1.5 text-[0.8125rem] text-success">
                <x-ui.icon name="truck" :size="15" />
                {{ __('shop.delivery_in_days', ['count' => $product->delivery_days]) }}
            </p>
        @endif
    </div>
</article>
