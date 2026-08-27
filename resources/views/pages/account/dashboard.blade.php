@extends('layouts.app')

@section('title', __('shop.dashboard'))

@section('content')

<x-breadcrumb :items="[__('shop.my_account') => null]" :title="__('shop.hello_user', ['name' => auth()->user()->firstName()])" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[16rem_1fr] gap-8 lg:gap-12">
    @include('partials.account-nav')

    <div>
        <div class="grid sm:grid-cols-3 gap-4">
            @foreach ([
                [$orderCount, __('shop.order_count'), 'package', localized_route('account.orders')],
                [$addressCount, __('shop.address_count'), 'pin', localized_route('account.addresses')],
                [$wishlistCount, __('shop.wishlist_count'), 'heart', localized_route('wishlist')],
            ] as [$value, $label, $icon, $href])
                <a href="{{ $href }}" class="border border-line p-5 hover:border-ink transition-colors">
                    <x-ui.icon :name="$icon" :size="22" class="text-brand" />
                    <span class="block text-[1.75rem] font-medium mt-3 leading-none">{{ $value }}</span>
                    <span class="block text-[0.875rem] text-muted mt-1">{{ $label }}</span>
                </a>
            @endforeach
        </div>

        <h2 class="text-[1.125rem] font-medium mt-10 mb-4">{{ __('shop.my_orders') }}</h2>

        @if ($orders->isEmpty())
            <p class="py-10 text-center text-muted border border-line">{{ __('shop.no_orders') }}</p>
        @else
            <div class="border border-line divide-y divide-line-soft">
                @foreach ($orders as $order)
                    <a href="{{ localized_route('account.order', ['order' => $order->number]) }}"
                       class="flex flex-wrap items-center gap-3 p-4 hover:bg-cream">
                        <span class="font-medium">{{ $order->number }}</span>
                        <span class="text-[0.875rem] text-muted">{{ $order->created_at->translatedFormat('d M Y') }}</span>
                        <span class="badge {{ $order->status === 'delivered' ? 'badge-new' : ($order->status === 'cancelled' ? 'badge-sale' : 'badge-trend') }}">
                            {{ __('shop.status_'.$order->status) }}
                        </span>
                        <span class="ml-auto font-medium">{{ money_base($order->grand_total) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
