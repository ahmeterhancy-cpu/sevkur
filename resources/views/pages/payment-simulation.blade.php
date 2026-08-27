@extends('layouts.app')

@section('title', __('shop.simulation_title'))

@section('content')
<div class="container-x py-16">
    <div class="max-w-lg mx-auto border border-line p-8 text-center">
        <x-ui.icon name="shield" :size="42" class="text-brand mx-auto" />
        <h1 class="text-[1.5rem] font-medium mt-4">{{ __('shop.simulation_title') }}</h1>
        <p class="text-muted mt-3 text-[0.9375rem]">{{ __('shop.simulation_text') }}</p>

        <div class="mt-6 p-4 bg-cream text-left text-[0.9375rem] space-y-1">
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.order_number') }}</span><strong>{{ $order->number }}</strong></div>
            <div class="flex justify-between"><span class="text-muted">{{ __('shop.order_total') }}</span><strong>{{ money_base($order->grand_total) }}</strong></div>
        </div>

        <div class="grid sm:grid-cols-2 gap-3 mt-6">
            <form method="POST" action="{{ localized_route('checkout.simulate', ['order' => $order->number]) }}">
                @csrf
                <input type="hidden" name="result" value="success">
                <button type="submit" class="btn btn-primary w-full">{{ __('shop.simulate_success') }}</button>
            </form>
            <form method="POST" action="{{ localized_route('checkout.simulate', ['order' => $order->number]) }}">
                @csrf
                <input type="hidden" name="result" value="fail">
                <button type="submit" class="btn btn-ghost w-full">{{ __('shop.simulate_fail') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
