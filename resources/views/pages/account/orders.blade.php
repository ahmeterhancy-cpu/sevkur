@extends('layouts.app')

@section('title', __('shop.my_orders'))

@section('content')

<x-breadcrumb :items="[__('shop.my_account') => localized_route('account.index'), __('shop.my_orders') => null]" :title="__('shop.my_orders')" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[16rem_1fr] gap-8 lg:gap-12">
    @include('partials.account-nav')

    <div>
        @if ($orders->isEmpty())
            <p class="py-16 text-center text-muted border border-line">{{ __('shop.no_orders') }}</p>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    <div class="border border-line p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <div>
                                <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_number') }}</span>
                                <strong>{{ $order->number }}</strong>
                            </div>
                            <div>
                                <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_date') }}</span>
                                {{ $order->created_at->translatedFormat('d M Y') }}
                            </div>
                            <div>
                                <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_total') }}</span>
                                <strong>{{ money_base($order->grand_total) }}</strong>
                            </div>
                            <span class="badge ml-auto {{ $order->status === 'delivered' ? 'badge-new' : ($order->status === 'cancelled' ? 'badge-sale' : 'badge-trend') }}">
                                {{ __('shop.status_'.$order->status) }}
                            </span>
                        </div>

                        <div class="flex gap-2 mt-4">
                            @foreach ($order->items->take(5) as $item)
                                <div class="w-14 h-14 bg-cream shrink-0">
                                    <img src="{{ media_url($item->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                                </div>
                            @endforeach
                            @if ($order->items->count() > 5)
                                <div class="w-14 h-14 bg-cream flex items-center justify-center text-[0.8125rem] text-muted">
                                    +{{ $order->items->count() - 5 }}
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4">
                            <a href="{{ localized_route('account.order', ['order' => $order->number]) }}" class="btn btn-ghost btn-sm">{{ __('shop.order_details') }}</a>
                            @if ($order->isCancellable() && ! $order->isPaid())
                                <form method="POST" action="{{ localized_route('account.order.cancel', ['order' => $order->number]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm hover:border-sale hover:text-sale">{{ __('shop.cancel_order') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-10">{{ $orders->links('partials.pagination') }}</div>
        @endif
    </div>
</div>
@endsection
