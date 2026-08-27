@php($gallery = $product->gallery())

<div class="grid md:grid-cols-2">
    <div class="bg-cream" data-gallery>
        <div class="aspect-square">
            <img src="{{ media_url($gallery[0] ?? null) }}" alt="{{ $product->t('name') }}"
                 class="w-full h-full object-cover" data-gallery-main>
        </div>
        @if (count($gallery) > 1)
            <div class="grid grid-cols-4 gap-2 p-3">
                @foreach (array_slice($gallery, 0, 4) as $path)
                    <button type="button" data-gallery-thumb="{{ media_url($path) }}"
                            class="aspect-square bg-white border-2 {{ $loop->first ? 'is-active' : 'border-transparent' }} overflow-hidden">
                        <img src="{{ media_url($path) }}" alt="" class="w-full h-full object-cover">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    <div class="p-7 lg:p-9 relative">
        <button type="button" data-modal-close class="absolute top-4 right-4 p-2 hover:text-brand" aria-label="{{ __('shop.close') }}">
            <x-ui.icon name="close" :size="22" />
        </button>

        <h2 class="text-[1.375rem] font-medium leading-snug pr-10">{{ $product->t('name') }}</h2>

        @if ($product->rating_count > 0)
            <div class="mt-2"><x-stars :rating="$product->rating_avg" :size="15" :show-count="true" :count="$product->rating_count" /></div>
        @endif

        <div class="mt-4 flex items-end gap-3">
            <span class="text-[1.5rem] font-medium leading-none">{{ money($product->price) }}</span>
            @if ($product->isOnSale())
                <span class="price-old">{{ money($product->compare_at_price) }}</span>
            @endif
        </div>

        @if ($product->t('short_description'))
            <p class="mt-4 text-[0.9375rem] text-ink-soft">{{ $product->t('short_description') }}</p>
        @endif

        <form method="POST" action="{{ localized_route('cart.add') }}" data-add-to-cart data-no-open class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            @if ($product->variants->isNotEmpty())
                <select name="variant_id" class="field" required>
                    <option value="">{{ __('shop.select_variant') }}</option>
                    @foreach ($product->variants as $variant)
                        <option value="{{ $variant->id }}" @disabled(! $variant->isInStock())>{{ $variant->label() }}</option>
                    @endforeach
                </select>
            @endif

            <div class="flex gap-3">
                <div class="flex items-center border border-line rounded-full h-[3rem]">
                    <button type="button" data-qty="-1" class="w-10 h-full" aria-label="-"><x-ui.icon name="minus" :size="15" /></button>
                    <input type="number" name="quantity" value="1" min="1" max="99" class="w-10 text-center border-0 focus:outline-none">
                    <button type="button" data-qty="1" class="w-10 h-full" aria-label="+"><x-ui.icon name="plus" :size="15" /></button>
                </div>
                <button type="submit" class="btn btn-primary flex-1 {{ $product->isInStock() ? '' : 'is-disabled' }}" @disabled(! $product->isInStock())>
                    {{ $product->isInStock() ? __('shop.add_to_cart') : __('shop.out_of_stock') }}
                </button>
            </div>
        </form>

        <a href="{{ localized_route('product', ['product' => $product->slug]) }}"
           class="mt-5 inline-flex items-center gap-2 text-[0.8125rem] uppercase tracking-widest link-underline">
            {{ __('shop.view') }} <x-ui.icon name="arrow-right" :size="15" />
        </a>
    </div>
</div>
