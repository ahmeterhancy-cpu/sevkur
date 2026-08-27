@extends('layouts.app')

@section('title', __('shop.thank_you'))

@section('content')
<div class="container-x py-14 lg:py-20">
    <div class="max-w-2xl mx-auto">
        <div class="text-center">
            <span class="w-16 h-16 rounded-full bg-success-soft text-success flex items-center justify-center mx-auto">
                <x-ui.icon name="check" :size="30" />
            </span>
            <h1 class="text-[1.875rem] font-medium mt-5">{{ __('shop.thank_you') }}</h1>
            <p class="text-muted mt-2">{{ __('shop.order_received') }}</p>
        </div>

        <div class="grid sm:grid-cols-4 gap-4 mt-10 p-6 bg-cream text-[0.9375rem]">
            <div>
                <span class="block text-muted text-[0.8125rem]">{{ __('shop.order_number') }}</span>
                <strong>{{ $order->number }}</strong>
            </div>
            <div>
                <span class="block text-muted text-[0.8125rem]">{{ __('shop.order_date') }}</span>
                <strong>{{ $order->created_at->translatedFormat('d M Y') }}</strong>
            </div>
            <div>
                <span class="block text-muted text-[0.8125rem]">{{ __('shop.payment_status') }}</span>
                <strong>{{ __('shop.payment_'.($order->payment_status === 'failed' ? 'failed_status' : $order->payment_status)) }}</strong>
            </div>
            <div>
                <span class="block text-muted text-[0.8125rem]">{{ __('shop.order_total') }}</span>
                <strong>{{ money_base($order->grand_total) }}</strong>
            </div>
        </div>

        <h2 class="text-[1.125rem] font-medium mt-10 mb-4">{{ __('shop.order_details') }}</h2>

        <div class="divide-y divide-line-soft border-t border-b border-line">
            @foreach ($order->items as $item)
                <div class="flex items-center gap-4 py-4">
                    <div class="w-16 h-16 bg-cream shrink-0">
                        <img src="{{ media_url($item->image) }}" alt="" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block">{{ $item->name }}</span>
                        @if ($item->options)
                            <span class="block text-[0.8125rem] text-muted">{{ implode(' / ', array_filter($item->options)) }}</span>
                        @endif
                        <span class="block text-[0.8125rem] text-muted">{{ $item->quantity }} × {{ money_base($item->unit_price) }}</span>
                    </div>
                    <span class="font-medium">{{ money_base($item->line_total) }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 space-y-2 text-[0.9375rem]">
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.subtotal') }}</span><span>{{ money_base($order->subtotal) }}</span></div>
            @if ((float) $order->discount_total > 0)
                <div class="flex justify-between text-success"><span>{{ __('shop.discount') }}</span><span>-{{ money_base($order->discount_total) }}</span></div>
            @endif
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.shipping') }}</span><span>{{ (float) $order->shipping_total > 0 ? money_base($order->shipping_total) : __('shop.free') }}</span></div>
            <div class="flex justify-between pt-3 border-t border-line text-[1.125rem] font-medium">
                <span>{{ __('shop.total') }}</span><span>{{ money_base($order->grand_total) }}</span>
            </div>
        </div>

        <div class="mt-6 p-5 bg-cream text-[0.9375rem]">
            <span class="block text-muted text-[0.8125rem] mb-1">{{ __('shop.address') }}</span>
            {{ $order->customerName() }} — {{ $order->customer_phone }}<br>
            {{ implode(', ', array_filter([
                $order->shipping_address['address'] ?? null,
                $order->shipping_address['district'] ?? null,
                $order->shipping_address['city'] ?? null,
                $order->shipping_address['postcode'] ?? null,
            ])) }}
        </div>

        <div class="flex flex-wrap justify-center gap-3 mt-8">
            <a href="{{ localized_route('shop') }}" class="btn btn-primary">{{ __('shop.continue_shopping') }}</a>
            @auth
                <a href="{{ localized_route('account.orders') }}" class="btn btn-ghost">{{ __('shop.my_orders') }}</a>
            @endauth
        </div>
    </div>
</div>
@endsection
