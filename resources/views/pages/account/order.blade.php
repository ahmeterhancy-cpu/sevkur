@extends('layouts.app')

@section('title', $order->number)

@section('content')

<x-breadcrumb :items="[__('shop.my_orders') => localized_route('account.orders'), $order->number => null]" :title="$order->number" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[16rem_1fr] gap-8 lg:gap-12">
    @include('partials.account-nav')

    <div>
        <div class="grid sm:grid-cols-4 gap-4 p-5 bg-cream text-[0.9375rem]">
            <div>
                <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_date') }}</span>
                <strong>{{ $order->created_at->translatedFormat('d M Y H:i') }}</strong>
            </div>
            <div>
                <span class="block text-[0.8125rem] text-muted">{{ __('shop.payment_status') }}</span>
                <strong>{{ __('shop.payment_'.($order->payment_status === 'failed' ? 'failed_status' : $order->payment_status)) }}</strong>
            </div>
            <div>
                <span class="block text-[0.8125rem] text-muted">{{ __('shop.shipping') }}</span>
                <strong>{{ $order->shippingMethod?->t('name') ?: '—' }}</strong>
            </div>
            <div>
                <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_total') }}</span>
                <strong>{{ money_base($order->grand_total) }}</strong>
            </div>
        </div>

        <div class="mt-6 border border-line divide-y divide-line-soft">
            @foreach ($order->items as $item)
                <div class="flex items-center gap-4 p-4">
                    <div class="w-16 h-16 bg-cream shrink-0">
                        <img src="{{ media_url($item->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
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

        <div class="mt-5 max-w-sm ml-auto space-y-2 text-[0.9375rem]">
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.subtotal') }}</span><span>{{ money_base($order->subtotal) }}</span></div>
            @if ((float) $order->discount_total > 0)
                <div class="flex justify-between text-success"><span>{{ __('shop.discount') }}</span><span>-{{ money_base($order->discount_total) }}</span></div>
            @endif
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.shipping') }}</span><span>{{ (float) $order->shipping_total > 0 ? money_base($order->shipping_total) : __('shop.free') }}</span></div>
            <div class="flex justify-between pt-3 border-t border-line text-[1.125rem] font-medium">
                <span>{{ __('shop.total') }}</span><span>{{ money_base($order->grand_total) }}</span>
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4 mt-8">
            <div class="border border-line p-5">
                <h3 class="font-medium mb-2">{{ __('shop.billing_details') }}</h3>
                <p class="text-[0.9375rem] text-muted">
                    {{ $order->customerName() }}<br>
                    {{ $order->customer_phone }}<br>
                    {{ $order->customer_email }}<br>
                    {{ implode(', ', array_filter([
                        $order->shipping_address['address'] ?? null,
                        $order->shipping_address['district'] ?? null,
                        $order->shipping_address['city'] ?? null,
                    ])) }}
                </p>
            </div>

            @if ($order->events->isNotEmpty())
                <div class="border border-line p-5">
                    <h3 class="font-medium mb-2">{{ __('shop.order_details') }}</h3>
                    <ul class="space-y-2 text-[0.875rem] text-muted">
                        @foreach ($order->events as $event)
                            <li class="flex gap-2">
                                <span class="text-ink">{{ $event->created_at->translatedFormat('d M H:i') }}</span>
                                <span>{{ $event->message ?: $event->type }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if ($order->isCancellable() && ! $order->isPaid())
            <form method="POST" action="{{ localized_route('account.order.cancel', ['order' => $order->number]) }}" class="mt-6">
                @csrf
                <button type="submit" class="btn btn-ghost hover:border-sale hover:text-sale">{{ __('shop.cancel_order') }}</button>
            </form>
        @endif
    </div>
</div>
@endsection
