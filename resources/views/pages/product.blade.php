@extends('layouts.app')

@section('title', $product->t('name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($product->t('short_description') ?: $product->t('description')), 155))
@section('og_type', 'product')
@section('og_image', media_url($product->cover))
@section('page_key', 'product')

{{-- Kayan WhatsApp dugmesi bu urunun adiyla acilsin: katalogda fiyat yok,
     musteri hangi urun icin sordugunu elle yazmasin. --}}
@section('whatsapp_message', whatsapp_message('product', ['product' => $product->t('name')]))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->t('name'),
    'image' => array_map(fn ($p) => media_url($p), $product->gallery()),
    'description' => strip_tags($product->t('short_description') ?: $product->t('description')),
    'sku' => $product->sku,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->t('name')] : null,
    'offers' => [
        '@type' => 'Offer',
        'url' => url()->current(),
        'priceCurrency' => 'TRY',
        'price' => (float) $product->price,
        'availability' => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    ],
    'aggregateRating' => $product->rating_count > 0 ? [
        '@type' => 'AggregateRating',
        'ratingValue' => (float) $product->rating_avg,
        'reviewCount' => $product->rating_count,
    ] : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')

@php
    $favorites = app(\App\Services\FavoritesService::class);
    $inWishlist = in_array($product->id, $favorites->wishlistIds(), true);
    $inCompare = in_array($product->id, $favorites->compareIds(), true);

    $crumbs = [];
    if ($product->category) {
        foreach ($product->category->breadcrumb() as $node) {
            $crumbs[$node->t('name')] = localized_route('category', ['category' => $node->slug]);
        }
    }
    $crumbs[$product->t('name')] = null;

    $gallery = $product->gallery();
@endphp

<x-breadcrumb :items="$crumbs" />

<div class="container-x py-10 lg:py-14">
    <div class="grid lg:grid-cols-[6fr_5fr] gap-8 lg:gap-16">

        {{-- ---------------------------------------------------------- galeri --}}
        <div data-gallery>
            <div class="bg-cream aspect-[4/5] overflow-hidden rounded-md">
                <img src="{{ media_url($gallery[0] ?? null) }}" alt="{{ $product->t('name') }}"
                     class="w-full h-full object-cover" data-gallery-main fetchpriority="high">
            </div>

            @if (count($gallery) > 1)
                <div class="grid grid-cols-5 gap-3 mt-3">
                    @foreach ($gallery as $path)
                        <button type="button" data-gallery-thumb="{{ media_url($path) }}"
                                class="aspect-square bg-cream border-2 {{ $loop->first ? 'is-active' : 'border-transparent' }} overflow-hidden rounded">
                            <img src="{{ media_url($path) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ----------------------------------------------------------- bilgi --}}
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-3">
                @if ($product->isOnSale())
                    <span class="badge badge-sale">-%{{ $product->discount_percent }}</span>
                @endif
                @if ($product->is_new)
                    <span class="badge badge-new">{{ __('shop.badge_new') }}</span>
                @endif
                @if ($product->brand)
                    <a href="{{ localized_route('shop', ['marka' => [$product->brand->slug]]) }}"
                       class="text-[0.75rem] uppercase tracking-widest text-muted hover:text-brand">
                        {{ $product->brand->t('name') }}
                    </a>
                @endif
            </div>

            <h1 class="text-[1.75rem] lg:text-[2.25rem] font-medium leading-tight">{{ $product->t('name') }}</h1>

            @if ($product->rating_count > 0)
                <div class="mt-3 flex items-center gap-3">
                    <x-stars :rating="$product->rating_avg" :size="16" />
                    <span class="text-[0.875rem] text-muted">{{ __('shop.review_count', ['count' => $product->rating_count]) }}</span>
                </div>
            @endif

            <div class="mt-5 flex items-end gap-3">
                @if ($product->hasPrice())
                    <span class="text-[1.75rem] font-medium leading-none" data-product-price>{{ money($product->price) }}</span>
                @else
                    <span class="text-[1.35rem] font-medium leading-none text-muted">{{ __('shop.price_on_request') }}</span>
                @endif
                @if ($product->isOnSale())
                    <span class="price-old text-[1.125rem]">{{ money($product->compare_at_price) }}</span>
                @endif
            </div>

            @if (app(\App\Services\CurrencyService::class)->current()->code !== 'TRY')
                <p class="mt-1 text-[0.8125rem] text-muted">{{ __('shop.payment_currency_note') }}</p>
            @endif

            @if ($product->t('short_description'))
                <p class="mt-5 text-[1rem] text-ink-soft leading-relaxed">{{ $product->t('short_description') }}</p>
            @endif

            {{-- Sayac --}}
            @if ($product->hasCountdown())
                <div class="mt-6 p-4 bg-cream" data-countdown="{{ $product->sale_ends_at->timestamp }}">
                    <span class="text-[0.8125rem] uppercase tracking-widest text-muted">{{ __('shop.countdown_ends') }}</span>
                    <div class="flex gap-2 mt-2">
                        @foreach (['days' => __('shop.days'), 'hours' => __('shop.hours'), 'minutes' => __('shop.minutes'), 'seconds' => __('shop.seconds')] as $unit => $label)
                            <div class="w-16 py-2 bg-white text-center rounded">
                                <span class="block text-[1.25rem] font-medium leading-none" data-unit="{{ $unit }}">00</span>
                                <span class="block text-[0.625rem] uppercase tracking-wider text-muted mt-1">{{ $label }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Sepete ekleme --}}
            @if (! $product->hasPrice())
                {{-- Fiyati girilmemis urun sepete EKLENEMEZ: CartService onu
                     reddeder. Calisir gorunen bir "Sepete ekle" dugmesi
                     musteriyi hataya goturuyordu.

                     Dugme WhatsApp sohbetini urun adi yazili olarak aciyor;
                     numara tanimli degilse iletisim sayfasina duser. --}}
                <a href="{{ ask_price_url($product->t('name')) }}"
                   @if (ask_price_is_whatsapp()) target="_blank" rel="noopener" @endif
                   class="btn btn-primary btn-lg mt-7 w-full sm:w-auto sm:min-w-[16rem]">
                    @if (ask_price_is_whatsapp())
                        <x-ui.icon name="whatsapp-solid" :size="18" />
                    @endif
                    {{ __('shop.ask_price') }}
                </a>
            @else
            <form method="POST" action="{{ localized_route('cart.add') }}" data-add-to-cart class="mt-7">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                @php
                    $axes = $product->variantAxes();
                    // Stokta olan ilk kombinasyon acilista secili gelir; her eksenden
                    // rastgele ilk degeri secmek var olmayan bir kombinasyon uretebilirdi.
                    $defaultVariant = $product->variants->firstWhere(fn ($v) => $v->isInStock())
                        ?? $product->variants->first();
                @endphp

                @if ($axes !== [])
                    {{--
                        Secenekler radyo dugmesi: JS kapaliyken de secilebilir ve
                        form sunucuya secim olarak gider. JS varsa fiyat, stok ve
                        gorsel aninda guncellenir.
                    --}}
                    <div class="mb-6 space-y-5" data-variant-picker
                         data-variants="{{ json_encode($product->variantMap(), JSON_UNESCAPED_UNICODE) }}">
                        @foreach ($axes as $axis)
                            @php $attribute = $axis['attribute']; @endphp
                            <fieldset>
                                <legend class="field-label mb-2">
                                    {{ $attribute->t('name') }}
                                    <span class="text-muted font-normal" data-axis-value="{{ $attribute->slug }}"></span>
                                </legend>

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($axis['values'] as $value)
                                        @php $inputId = 'secenek-'.$attribute->slug.'-'.$value->slug; @endphp
                                        <input type="radio" class="sr-only"
                                               name="secenek[{{ $attribute->slug }}]" value="{{ $value->slug }}"
                                               id="{{ $inputId }}" required
                                               @checked(($defaultVariant?->options[$attribute->slug] ?? null) === $value->slug)>

                                        <label for="{{ $inputId }}"
                                               class="variant-option {{ $attribute->type === 'color' ? 'variant-option--color' : '' }}"
                                               title="{{ $value->t('name') }}">
                                            @if ($attribute->type === 'color' && $value->color_hex)
                                                <span class="variant-swatch" style="background: {{ $value->color_hex }}"></span>
                                                <span class="sr-only">{{ $value->t('name') }}</span>
                                            @else
                                                {{ $value->t('name') }}
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach

                        <p class="text-[0.875rem] text-danger hidden" data-variant-unavailable>
                            {{ __('shop.variant_unavailable') }}
                        </p>
                    </div>
                @elseif ($product->variants->isNotEmpty())
                    <div class="mb-5">
                        <label class="field-label" for="variant">{{ __('shop.variant') }}</label>
                        <select name="variant_id" id="variant" class="field" required>
                            <option value="">{{ __('shop.select_variant') }}</option>
                            @foreach ($product->variants as $variant)
                                <option value="{{ $variant->id }}" @disabled(! $variant->isInStock())>
                                    {{ $variant->label() }}
                                    @if ($variant->price) — {{ money($variant->price) }} @endif
                                    @if (! $variant->isInStock()) ({{ __('shop.out_of_stock') }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex flex-wrap items-stretch gap-3">
                    <div class="flex items-center border border-line rounded-full h-[3.1rem]">
                        <button type="button" data-qty="-1" class="w-11 h-full flex items-center justify-center hover:text-brand" aria-label="-">
                            <x-ui.icon name="minus" :size="16" />
                        </button>
                        <input type="number" name="quantity" value="1" min="1" max="99"
                               class="w-12 text-center border-0 focus:outline-none" aria-label="{{ __('shop.quantity') }}">
                        <button type="button" data-qty="1" class="w-11 h-full flex items-center justify-center hover:text-brand" aria-label="+">
                            <x-ui.icon name="plus" :size="16" />
                        </button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg flex-1 min-w-[12rem] {{ $product->isInStock() ? '' : 'is-disabled' }}"
                            @disabled(! $product->isInStock())>
                        {{ $product->isInStock() ? __('shop.add_to_cart') : __('shop.out_of_stock') }}
                    </button>
                </div>
            </form>
            @endif

            <div class="mt-4 flex flex-wrap items-center gap-5 text-[0.875rem]">
                <button type="button" class="flex items-center gap-2 hover:text-brand {{ $inWishlist ? 'text-brand' : '' }}"
                        data-toggle-list="{{ localized_route('wishlist.toggle') }}" data-product-id="{{ $product->id }}" data-badge="wishlist">
                    <x-ui.icon name="heart" :size="18" /> {{ __('shop.add_to_wishlist') }}
                </button>
                <button type="button" class="flex items-center gap-2 hover:text-brand {{ $inCompare ? 'text-brand' : '' }}"
                        data-toggle-list="{{ localized_route('compare.toggle') }}" data-product-id="{{ $product->id }}" data-badge="compare">
                    <x-ui.icon name="compare" :size="18" /> {{ __('shop.add_to_compare') }}
                </button>
            </div>

            {{-- Kunye --}}
            <dl class="mt-7 pt-6 border-t border-line space-y-2 text-[0.875rem]">
                @if ($product->sku)
                    <div class="flex gap-2"><dt class="text-muted w-28">{{ __('shop.sku') }}</dt><dd>{{ $product->sku }}</dd></div>
                @endif
                @if ($product->category)
                    <div class="flex gap-2">
                        <dt class="text-muted w-28">{{ __('shop.category') }}</dt>
                        <dd><a href="{{ localized_route('category', ['category' => $product->category->slug]) }}" class="link-underline">{{ $product->category->t('name') }}</a></dd>
                    </div>
                @endif
                <div class="flex gap-2">
                    <dt class="text-muted w-28">{{ __('shop.availability') }}</dt>
                    <dd class="{{ $product->isInStock() ? 'text-success' : 'text-sale' }}">
                        {{ $product->isInStock() ? __('shop.in_stock') : __('shop.out_of_stock') }}
                        @if ($product->isLowStock())
                            — {{ __('shop.only_left', ['count' => $product->stock]) }}
                        @endif
                    </dd>
                </div>
                @if ($product->delivery_days)
                    <div class="flex gap-2">
                        <dt class="text-muted w-28">{{ __('shop.shipping') }}</dt>
                        <dd>{{ __('shop.delivery_in_days', ['count' => $product->delivery_days]) }}</dd>
                    </div>
                @endif
                @if ($product->dimensions)
                    <div class="flex gap-2"><dt class="text-muted w-28">{{ __('shop.specifications') }}</dt><dd>{{ $product->dimensions }}</dd></div>
                @endif
            </dl>

            <div class="mt-6 p-4 bg-cream flex items-center gap-3 text-[0.875rem]">
                <x-ui.icon name="shield" :size="20" class="text-brand shrink-0" />
                <span>{{ __('shop.secure_payment') }}</span>
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------- sekmeler --}}
    <div class="mt-14 lg:mt-20" data-tabs>
        <div class="flex flex-wrap gap-6 border-b border-line">
            <button type="button" data-tab="description" class="is-active pb-3 text-[0.9375rem] font-medium">{{ __('shop.description') }}</button>
            @if (! empty($product->specs))
                <button type="button" data-tab="specs" class="pb-3 text-[0.9375rem] font-medium">{{ __('shop.specifications') }}</button>
            @endif
            <button type="button" data-tab="reviews" class="pb-3 text-[0.9375rem] font-medium">
                {{ __('shop.reviews') }} ({{ $product->rating_count }})
            </button>
        </div>

        <div data-tab-panel="description" class="py-8 prose-shop max-w-3xl">
            {!! nl2br(e($product->t('description'))) !!}
        </div>

        @if (! empty($product->specs))
            <div data-tab-panel="specs" class="py-8 hidden max-w-3xl">
                <table class="w-full text-[0.9375rem]">
                    <tbody>
                    @foreach ($product->specs as $row)
                        <tr class="border-b border-line-soft">
                            <th class="text-left py-3 font-normal text-muted w-1/3">{{ $row['label'][app()->getLocale()] ?? ($row['label'][\App\Support\Locales::default()] ?? '') }}</th>
                            <td class="py-3">{{ $row['value'][app()->getLocale()] ?? ($row['value'][\App\Support\Locales::default()] ?? '') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div data-tab-panel="reviews" class="py-8 hidden">
          <div class="grid lg:grid-cols-2 gap-10">
            <div>
                @if ($product->rating_count > 0)
                    <div class="flex items-center gap-5 pb-6 border-b border-line">
                        <div class="text-center">
                            <span class="block text-[2.5rem] font-medium leading-none">{{ number_format($product->rating_avg, 1) }}</span>
                            <x-stars :rating="$product->rating_avg" :size="15" />
                        </div>
                        <div class="flex-1 space-y-1.5">
                            @foreach ($ratingBreakdown as $row)
                                <div class="flex items-center gap-2 text-[0.8125rem]">
                                    <span class="w-6 text-muted">{{ $row['stars'] }}</span>
                                    <div class="flex-1 h-1.5 bg-line rounded-full overflow-hidden">
                                        <div class="h-full bg-brand" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <span class="w-8 text-right text-muted">{{ $row['count'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="divide-y divide-line-soft">
                    @forelse ($product->approvedReviews as $review)
                        <div class="py-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-medium">{{ $review->name }}</span>
                                <span class="text-[0.8125rem] text-muted">{{ $review->created_at->translatedFormat('d M Y') }}</span>
                            </div>
                            <x-stars :rating="$review->rating" :size="14" />
                            @if ($review->title)
                                <p class="mt-2 font-medium">{{ $review->title }}</p>
                            @endif
                            <p class="mt-1 text-ink-soft">{{ $review->body }}</p>
                        </div>
                    @empty
                        <p class="py-5 text-muted">{{ __('shop.no_reviews') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Degerlendirme formu --}}
            <div>
                <h3 class="text-[1.125rem] font-medium mb-4">{{ __('shop.write_review') }}</h3>
                <form method="POST" action="{{ localized_route('product.review', ['product' => $product->slug]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <span class="field-label">{{ __('shop.your_rating') }}</span>
                        <div class="flex gap-1">
                            @for ($i = 5; $i >= 1; $i--)
                                <label class="cursor-pointer">
                                    <input type="radio" name="rating" value="{{ $i }}" class="sr-only peer" @checked($i === 5) required>
                                    <span class="px-3 py-1.5 border border-line rounded-full text-[0.875rem] peer-checked:bg-ink peer-checked:text-white block">{{ $i }} ★</span>
                                </label>
                            @endfor
                        </div>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label" for="rv-name">{{ __('shop.review_name') }} *</label>
                            <input id="rv-name" type="text" name="name" required maxlength="80" class="field"
                                   value="{{ old('name', auth()->user()->name ?? '') }}">
                        </div>
                        <div>
                            <label class="field-label" for="rv-email">{{ __('shop.review_email') }} *</label>
                            <input id="rv-email" type="email" name="email" required maxlength="120" class="field"
                                   value="{{ old('email', auth()->user()->email ?? '') }}">
                        </div>
                    </div>
                    <div>
                        <label class="field-label" for="rv-title">{{ __('shop.review_title') }}</label>
                        <input id="rv-title" type="text" name="title" maxlength="120" class="field" value="{{ old('title') }}">
                    </div>
                    <div>
                        <label class="field-label" for="rv-body">{{ __('shop.review_body') }} *</label>
                        <textarea id="rv-body" name="body" rows="5" required minlength="10" class="field">{{ old('body') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('shop.submit_review') }}</button>
                </form>
            </div>
          </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------ benzerler --}}
    @if ($related->isNotEmpty())
        <section class="mt-16 lg:mt-24">
            <h2 class="section-title text-center mb-10">{{ __('shop.related_products') }}</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 lg:gap-x-6">
                @foreach ($related->take(4) as $item)
                    <x-product-card :product="$item" style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms" />
                @endforeach
            </div>
        </section>
    @endif
</div>

@endsection
