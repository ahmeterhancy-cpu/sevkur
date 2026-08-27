@if ($cart->isEmpty())
    <div class="flex flex-col items-center justify-center text-center gap-4 py-16 px-6">
        <x-ui.icon name="cart" :size="48" class="text-line" />
        <p class="text-muted">{{ __('shop.empty_cart') }}</p>
        <a href="{{ localized_route('shop') }}" class="btn btn-primary">{{ __('shop.continue_shopping') }}</a>
    </div>
@else
    <div class="divide-y divide-line-soft">
        @foreach ($cart->items as $item)
            <div class="flex gap-3 p-5">
                <a href="{{ localized_route('product', ['product' => $item->product->slug]) }}" class="w-20 h-20 bg-cream shrink-0">
                    <img src="{{ media_url($item->product->cover) }}" alt="{{ $item->product->t('name') }}" class="w-full h-full object-cover" loading="lazy">
                </a>
                <div class="flex-1 min-w-0">
                    <a href="{{ localized_route('product', ['product' => $item->product->slug]) }}" class="block text-[0.9375rem] leading-snug hover:text-brand truncate">
                        {{ $item->product->t('name') }}
                    </a>
                    @if ($item->variant)
                        <span class="block text-[0.8125rem] text-muted">{{ $item->variant->t('name') }}</span>
                    @endif
                    <span class="block text-[0.875rem] text-muted mt-1">
                        {{ $item->quantity }} × {{ money($item->unit_price) }}
                    </span>
                </div>
                <button type="button" class="p-1 h-fit text-muted hover:text-sale"
                        data-cart-remove="{{ localized_route('cart.remove') }}" data-item-id="{{ $item->id }}"
                        aria-label="{{ __('shop.remove') }}">
                    <x-ui.icon name="trash" :size="18" />
                </button>
            </div>
        @endforeach
    </div>

    <div class="p-5 border-t border-line sticky bottom-0 bg-white">
        <div class="flex items-center justify-between mb-1">
            <span class="text-muted">{{ __('shop.subtotal') }}</span>
            <span class="font-medium" data-total="subtotal">{{ money($totals['subtotal']) }}</span>
        </div>
        @if ($totals['discount'] > 0)
            <div class="flex items-center justify-between mb-1 text-success">
                <span>{{ __('shop.discount') }}</span>
                <span data-total="discount">-{{ money($totals['discount']) }}</span>
            </div>
        @endif
        <p class="text-[0.8125rem] text-muted mb-4">{{ __('shop.calculated_at_checkout') }}</p>

        <div class="grid grid-cols-2 gap-2">
            <a href="{{ localized_route('cart') }}" class="btn btn-ghost">{{ __('shop.cart') }}</a>
            <a href="{{ localized_route('checkout') }}" class="btn btn-primary">{{ __('shop.proceed_to_checkout') }}</a>
        </div>
    </div>
@endif
