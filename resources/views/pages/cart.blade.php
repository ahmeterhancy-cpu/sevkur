@extends('layouts.app')

@section('title', __('shop.cart_title'))
@section('page_key', 'cart')

@section('content')

<x-breadcrumb :items="[__('shop.cart_title') => null]" :title="__('shop.cart_title')" />

<div class="container-x py-10 lg:py-14">
    @if ($cart->isEmpty())
        <div class="py-20 text-center">
            <x-ui.icon name="cart" :size="56" class="text-line mx-auto" />
            <p class="mt-4 text-muted">{{ __('shop.empty_cart') }}</p>
            <a href="{{ localized_route('shop') }}" class="btn btn-primary mt-6">{{ __('shop.continue_shopping') }}</a>
        </div>
    @else
        <div class="grid lg:grid-cols-[1fr_22rem] gap-10">
            <div>
                {{-- Baslik satiri --}}
                <div class="hidden md:grid grid-cols-[1fr_8rem_7rem_7rem_2rem] gap-4 pb-3 border-b border-line text-[0.8125rem] uppercase tracking-widest text-muted">
                    <span>{{ __('shop.product') }}</span>
                    <span>{{ __('shop.price') }}</span>
                    <span>{{ __('shop.quantity') }}</span>
                    <span class="text-right">{{ __('shop.total') }}</span>
                    <span></span>
                </div>

                @foreach ($cart->items as $item)
                    <div class="grid md:grid-cols-[1fr_8rem_7rem_7rem_2rem] gap-4 items-center py-5 border-b border-line-soft" data-cart-row>
                        <div class="flex gap-4">
                            <a href="{{ localized_route('product', ['product' => $item->product->slug]) }}" class="w-24 h-24 bg-cream shrink-0">
                                <img src="{{ media_url($item->product->cover) }}" alt="{{ $item->product->t('name') }}"
                                     class="w-full h-full object-cover" loading="lazy">
                            </a>
                            <div class="min-w-0">
                                <a href="{{ localized_route('product', ['product' => $item->product->slug]) }}" class="font-medium link-underline">
                                    {{ $item->product->t('name') }}
                                </a>
                                @if ($item->variant)
                                    <span class="block text-[0.875rem] text-muted">{{ $item->variant->t('name') }}</span>
                                @endif
                                @if ($item->product->sku)
                                    <span class="block text-[0.8125rem] text-muted">{{ __('shop.sku') }}: {{ $item->variant?->sku ?: $item->product->sku }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="md:block flex justify-between">
                            <span class="md:hidden text-muted text-[0.875rem]">{{ __('shop.price') }}</span>
                            <span>{{ money($item->unit_price) }}</span>
                        </div>

                        <div class="flex items-center border border-line rounded-full h-11 w-fit">
                            <button type="button" data-qty="-1" class="w-9 h-full flex items-center justify-center" aria-label="-">
                                <x-ui.icon name="minus" :size="14" />
                            </button>
                            <input type="number" value="{{ $item->quantity }}" min="1" max="99"
                                   class="w-10 text-center border-0 focus:outline-none"
                                   data-cart-qty="{{ localized_route('cart.update') }}" data-item-id="{{ $item->id }}"
                                   aria-label="{{ __('shop.quantity') }}">
                            <button type="button" data-qty="1" class="w-9 h-full flex items-center justify-center" aria-label="+">
                                <x-ui.icon name="plus" :size="14" />
                            </button>
                        </div>

                        <div class="md:text-right flex justify-between">
                            <span class="md:hidden text-muted text-[0.875rem]">{{ __('shop.total') }}</span>
                            <span class="font-medium">{{ money($item->lineTotal()) }}</span>
                        </div>

                        <button type="button" class="text-muted hover:text-sale justify-self-end"
                                data-cart-remove="{{ localized_route('cart.remove') }}" data-item-id="{{ $item->id }}"
                                aria-label="{{ __('shop.remove') }}">
                            <x-ui.icon name="trash" :size="18" />
                        </button>
                    </div>
                @endforeach

                {{-- Kupon --}}
                <div class="mt-8 flex flex-col sm:flex-row gap-3 sm:items-center justify-between">
                    <form method="POST" action="{{ localized_route('cart.coupon') }}" class="flex gap-2 flex-1 max-w-sm">
                        @csrf
                        <input type="text" name="code" value="{{ $cart->coupon_code }}" placeholder="{{ __('shop.coupon_code') }}"
                               class="field rounded-full" aria-label="{{ __('shop.coupon_code') }}">
                        <button type="submit" class="btn btn-ghost whitespace-nowrap">{{ __('shop.apply') }}</button>
                    </form>

                    <a href="{{ localized_route('shop') }}" class="btn btn-ghost">{{ __('shop.continue_shopping') }}</a>
                </div>

                @if ($cart->coupon_code && $totals['coupon'])
                    <form method="POST" action="{{ localized_route('cart.coupon.remove') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="text-[0.875rem] text-muted hover:text-sale">
                            {{ $cart->coupon_code }} — {{ __('shop.remove') }}
                        </button>
                    </form>
                @endif
            </div>

            {{-- Ozet --}}
            <aside class="bg-cream p-6 h-fit lg:sticky lg:top-24">
                <h2 class="text-[1.125rem] font-medium mb-5">{{ __('shop.order_summary') }}</h2>

                <div class="space-y-3 text-[0.9375rem]">
                    <div class="flex justify-between">
                        <span class="text-muted">{{ __('shop.subtotal') }}</span>
                        <span data-total="subtotal">{{ money($totals['subtotal']) }}</span>
                    </div>

                    @if ($totals['discount'] > 0)
                        <div class="flex justify-between text-success">
                            <span>{{ __('shop.discount') }}</span>
                            <span data-total="discount">-{{ money($totals['discount']) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-muted">{{ __('shop.shipping') }}</span>
                        <span class="text-[0.875rem] text-muted">{{ __('shop.calculated_at_checkout') }}</span>
                    </div>

                    <div class="flex justify-between pt-3 border-t border-line text-[1.125rem] font-medium">
                        <span>{{ __('shop.total') }}</span>
                        <span data-total="total">{{ money($totals['total']) }}</span>
                    </div>
                </div>

                <a href="{{ localized_route('checkout') }}" class="btn btn-primary w-full mt-6">{{ __('shop.proceed_to_checkout') }}</a>

                <p class="mt-4 text-[0.8125rem] text-muted flex gap-2">
                    <x-ui.icon name="shield" :size="16" class="shrink-0 text-brand" />
                    {{ __('shop.secure_payment') }}
                </p>
            </aside>
        </div>
    @endif
</div>

@endsection
