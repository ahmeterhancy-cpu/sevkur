@extends('layouts.app')

@section('title', __('shop.payment_failed'))

@section('content')
<div class="container-x py-16">
    <div class="max-w-lg mx-auto text-center">
        <h1 class="text-[1.75rem] font-medium">{{ __('shop.payment_failed') }}</h1>
        <p class="text-muted mt-3">{{ __('shop.payment_failed_text') }}</p>
        <p class="mt-2 text-[0.9375rem]">{{ __('shop.order_number') }}: <strong>{{ $order->number }}</strong></p>

        <div class="flex flex-wrap justify-center gap-3 mt-7">
            <a href="{{ localized_route('shop') }}" class="btn btn-primary">{{ __('shop.continue_shopping') }}</a>
            <a href="{{ localized_route('contact') }}" class="btn btn-ghost">{{ __('shop.contact') }}</a>
        </div>
    </div>
</div>
@endsection
