@extends('layouts.app')

@section('title', __('shop.payment_method'))

@section('content')
<div class="container-x py-10 lg:py-14">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-[1.75rem] font-medium">{{ __('shop.payment_method') }}</h1>
            <p class="text-muted mt-2">{{ __('shop.order_number') }}: <strong>{{ $order->number }}</strong> — {{ money_base($order->grand_total) }}</p>
            <p class="text-[0.875rem] text-muted mt-1">{{ __('shop.redirecting_to_payment') }}</p>
        </div>

        <iframe src="{{ $iframeUrl }}" id="paytriframe" frameborder="0" scrolling="no"
                style="width:100%;min-height:700px" title="PayTR"></iframe>
    </div>
</div>

@push('scripts')
<script>
    // PayTR iframe yuksekligini icerige gore ayarlar.
    window.addEventListener('message', function (event) {
        if (typeof event.data === 'string' && event.data.indexOf('height') === 0) {
            const height = parseInt(event.data.split(':')[1], 10);
            if (height > 0) document.getElementById('paytriframe').style.height = height + 'px';
        }
    });
</script>
@endpush
@endsection
