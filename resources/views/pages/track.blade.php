@extends('layouts.app')

@section('title', __('shop.track_title'))

@section('content')

<x-breadcrumb :items="[__('shop.track_title') => null]" :title="__('shop.track_title')" />

<div class="container-x py-10 lg:py-14">
    <div class="max-w-xl mx-auto">
        <p class="text-muted">{{ __('shop.track_text') }}</p>

        <form method="POST" action="{{ localized_route('track') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="field-label" for="number">{{ __('shop.order_number') }} *</label>
                <input id="number" type="text" name="number" required class="field" value="{{ old('number') }}" placeholder="SVK-260101-ABCDE">
            </div>
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required class="field" value="{{ old('email') }}">
            </div>
            <button type="submit" class="btn btn-primary">{{ __('shop.track_button') }}</button>
        </form>

        @if ($notFound)
            <p class="mt-6 p-4 bg-cream text-sale text-[0.9375rem]">{{ __('shop.track_not_found') }}</p>
        @endif

        @if ($order)
            <div class="mt-8 border border-line p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span class="block text-[0.8125rem] text-muted">{{ __('shop.order_number') }}</span>
                        <strong>{{ $order->number }}</strong>
                    </div>
                    <span class="badge {{ $order->status === 'delivered' ? 'badge-new' : ($order->status === 'cancelled' ? 'badge-sale' : 'badge-trend') }}">
                        {{ __('shop.status_'.$order->status) }}
                    </span>
                </div>

                <div class="mt-5 divide-y divide-line-soft">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-3 py-3">
                            <div class="w-14 h-14 bg-cream shrink-0">
                                <img src="{{ media_url($item->image) }}" alt="" class="w-full h-full object-cover">
                            </div>
                            <span class="flex-1 text-[0.9375rem]">{{ $item->name }}</span>
                            <span class="text-[0.875rem] text-muted">×{{ $item->quantity }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-line flex justify-between text-[1.0625rem] font-medium">
                    <span>{{ __('shop.total') }}</span>
                    <span>{{ money_base($order->grand_total) }}</span>
                </div>

                @if ($order->tracking_number)
                    <p class="mt-4 text-[0.9375rem]">
                        <span class="text-muted">{{ __('shop.shipping') }}:</span> {{ $order->tracking_number }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
