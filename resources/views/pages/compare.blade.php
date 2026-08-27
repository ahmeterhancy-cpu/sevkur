@extends('layouts.app')

@section('title', __('shop.compare'))

@section('content')

<x-breadcrumb :items="[__('shop.compare') => null]" :title="__('shop.compare')" />

<div class="container-x py-10 lg:py-14">
    @if ($products->isEmpty())
        <div class="py-20 text-center">
            <x-ui.icon name="compare" :size="56" class="text-line mx-auto" />
            <p class="mt-4 text-muted">{{ __('shop.compare_empty') }}</p>
            <a href="{{ localized_route('shop') }}" class="btn btn-primary mt-6">{{ __('shop.continue_shopping') }}</a>
        </div>
    @else
        @php
            // Karsilastirmada gosterilecek ozellik satirlari
            $usedAttributes = $attributes->filter(function ($attribute) use ($products) {
                return $products->contains(fn ($p) => $p->attributeValues->contains('attribute_id', $attribute->id));
            });
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full min-w-[45rem] border-collapse text-[0.9375rem]">
                <tbody>
                <tr>
                    <th class="w-40 text-left align-top p-3"></th>
                    @foreach ($products as $product)
                        <td class="p-3 align-top border-l border-line-soft">
                            <a href="{{ localized_route('product', ['product' => $product->slug]) }}" class="block aspect-square bg-cream mb-3">
                                <img src="{{ media_url($product->cover) }}" alt="{{ $product->t('name') }}" class="w-full h-full object-cover" loading="lazy">
                            </a>
                            <a href="{{ localized_route('product', ['product' => $product->slug]) }}" class="font-medium link-underline">{{ $product->t('name') }}</a>
                            <button type="button" class="block mt-2 text-[0.8125rem] text-muted hover:text-sale"
                                    data-toggle-list="{{ localized_route('compare.toggle') }}" data-product-id="{{ $product->id }}" data-badge="compare">
                                {{ __('shop.remove') }}
                            </button>
                        </td>
                    @endforeach
                </tr>

                <tr class="border-t border-line">
                    <th class="text-left p-3 text-muted font-normal">{{ __('shop.price') }}</th>
                    @foreach ($products as $product)
                        <td class="p-3 border-l border-line-soft">
                            <span class="price-now">{{ money($product->price) }}</span>
                            @if ($product->isOnSale())
                                <span class="price-old ml-2">{{ money($product->compare_at_price) }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>

                <tr class="border-t border-line">
                    <th class="text-left p-3 text-muted font-normal">{{ __('shop.availability') }}</th>
                    @foreach ($products as $product)
                        <td class="p-3 border-l border-line-soft {{ $product->isInStock() ? 'text-success' : 'text-sale' }}">
                            {{ $product->isInStock() ? __('shop.in_stock') : __('shop.out_of_stock') }}
                        </td>
                    @endforeach
                </tr>

                <tr class="border-t border-line">
                    <th class="text-left p-3 text-muted font-normal">{{ __('shop.reviews') }}</th>
                    @foreach ($products as $product)
                        <td class="p-3 border-l border-line-soft">
                            @if ($product->rating_count > 0)
                                <x-stars :rating="$product->rating_avg" :show-count="true" :count="$product->rating_count" />
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>

                <tr class="border-t border-line">
                    <th class="text-left p-3 text-muted font-normal">{{ __('shop.brand') }}</th>
                    @foreach ($products as $product)
                        <td class="p-3 border-l border-line-soft">{{ $product->brand?->t('name') ?: '—' }}</td>
                    @endforeach
                </tr>

                @foreach ($usedAttributes as $attribute)
                    <tr class="border-t border-line">
                        <th class="text-left p-3 text-muted font-normal">{{ $attribute->t('name') }}</th>
                        @foreach ($products as $product)
                            @php($values = $product->attributeValues->where('attribute_id', $attribute->id))
                            <td class="p-3 border-l border-line-soft">
                                {{ $values->isNotEmpty() ? $values->map(fn ($v) => $v->t('name'))->join(', ') : '—' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach

                <tr class="border-t border-line">
                    <th class="p-3"></th>
                    @foreach ($products as $product)
                        <td class="p-3 border-l border-line-soft">
                            @if ($product->isInStock() && $product->variants->isEmpty())
                                <form method="POST" action="{{ localized_route('cart.add') }}" data-add-to-cart>
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn-primary btn-sm w-full">{{ __('shop.add_to_cart') }}</button>
                                </form>
                            @else
                                <a href="{{ localized_route('product', ['product' => $product->slug]) }}" class="btn btn-ghost btn-sm w-full">{{ __('shop.view') }}</a>
                            @endif
                        </td>
                    @endforeach
                </tr>
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
