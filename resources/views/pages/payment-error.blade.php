@extends('layouts.app')

@section('title', __('shop.payment_error'))

@section('content')
<div class="container-x py-16">
    <div class="max-w-lg mx-auto text-center">
        <h1 class="text-[1.75rem] font-medium">{{ __('shop.payment_error') }}</h1>
        <p class="text-muted mt-3">{{ __('shop.payment_error_text') }}</p>

        @if ($error && app()->isLocal())
            <p class="mt-3 text-[0.8125rem] text-sale">{{ $error }}</p>
        @endif

        <div class="flex flex-wrap justify-center gap-3 mt-7">
            <a href="{{ localized_route('checkout.pay', ['order' => $order->number]) }}" class="btn btn-primary">{{ __('shop.try_again') }}</a>
            <a href="{{ localized_route('contact') }}" class="btn btn-ghost">{{ __('shop.contact') }}</a>
        </div>
    </div>
</div>
@endsection
