@extends('layouts.app')

@section('title', __('shop.checkout_title'))
@section('page_key', 'checkout')

@section('content')

<x-breadcrumb :items="[__('shop.cart_title') => localized_route('cart'), __('shop.checkout_title') => null]" :title="__('shop.checkout_title')" />

<form method="POST" action="{{ localized_route('checkout.store') }}" class="container-x py-10 lg:py-14 grid lg:grid-cols-[1fr_24rem] gap-10">
    @csrf

    <div class="space-y-10">
        {{-- ------------------------------------------------------ kayitli adres --}}
        @if ($addresses->isNotEmpty())
            <section>
                <h2 class="text-[1.125rem] font-medium mb-4">{{ __('shop.use_saved_address') }}</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach ($addresses as $address)
                        {{-- Not: Blade yorumlari, ham PHP bloklari cozuldukten SONRA silinir;
                             bu yuzden yorum icine PHP direktifi yazilmaz. --}}
                        @php
                            $snapshot = json_encode($address->toSnapshot(), JSON_UNESCAPED_UNICODE);
                        @endphp
                        <button type="button" class="text-left border border-line p-4 hover:border-ink transition-colors"
                                data-fill-address="{{ $snapshot }}">
                            <span class="block font-medium">{{ $address->title ?: $address->city }}</span>
                            <span class="block text-[0.875rem] text-muted mt-1">{{ $address->fullName() }} — {{ $address->oneLine() }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ------------------------------------------------------------ bilgiler --}}
        <section>
            <h2 class="text-[1.125rem] font-medium mb-4">{{ __('shop.billing_details') }}</h2>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="field-label" for="first_name">{{ __('shop.first_name') }} *</label>
                    <input id="first_name" type="text" name="first_name" required maxlength="60" class="field"
                           value="{{ old('first_name', auth()->user()?->firstName()) }}">
                </div>
                <div>
                    <label class="field-label" for="last_name">{{ __('shop.last_name') }} *</label>
                    <input id="last_name" type="text" name="last_name" required maxlength="60" class="field"
                           value="{{ old('last_name', auth()->user()?->lastName()) }}">
                </div>
                <div>
                    <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                    <input id="email" type="email" name="email" required maxlength="120" class="field"
                           value="{{ old('email', auth()->user()?->email) }}">
                </div>
                <div>
                    <label class="field-label" for="phone">{{ __('shop.phone') }} *</label>
                    <input id="phone" type="tel" name="phone" required maxlength="30" class="field"
                           value="{{ old('phone', auth()->user()?->phone) }}">
                </div>
                <div>
                    <label class="field-label" for="city">{{ __('shop.city') }} *</label>
                    <input id="city" type="text" name="city" required maxlength="60" class="field" value="{{ old('city') }}">
                </div>
                <div>
                    <label class="field-label" for="district">{{ __('shop.district') }}</label>
                    <input id="district" type="text" name="district" maxlength="60" class="field" value="{{ old('district') }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="address">{{ __('shop.address') }} *</label>
                    <textarea id="address" name="address" rows="3" required maxlength="400" class="field">{{ old('address') }}</textarea>
                </div>
                <div>
                    <label class="field-label" for="postcode">{{ __('shop.postcode') }}</label>
                    <input id="postcode" type="text" name="postcode" maxlength="20" class="field" value="{{ old('postcode') }}">
                </div>
            </div>

            @auth
                <label class="flex items-center gap-2.5 mt-4 text-[0.9375rem] cursor-pointer">
                    <input type="checkbox" name="save_address" value="1" class="w-4 h-4 accent-black" checked>
                    {{ __('shop.save_address') }}
                </label>
            @endauth
        </section>

        {{-- --------------------------------------------------------- fatura --}}
        <section>
            <label class="flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                <input type="checkbox" name="billing_same" value="1" class="w-4 h-4 accent-black" checked data-billing-toggle>
                {{ __('shop.billing_same') }}
            </label>

            <div class="grid sm:grid-cols-2 gap-4 mt-4 hidden" data-billing-fields>
                <div>
                    <label class="field-label" for="billing_city">{{ __('shop.city') }}</label>
                    <input id="billing_city" type="text" name="billing_city" maxlength="60" class="field" value="{{ old('billing_city') }}">
                </div>
                <div>
                    <label class="field-label" for="billing_district">{{ __('shop.district') }}</label>
                    <input id="billing_district" type="text" name="billing_district" maxlength="60" class="field" value="{{ old('billing_district') }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="billing_address">{{ __('shop.address') }}</label>
                    <textarea id="billing_address" name="billing_address" rows="3" maxlength="400" class="field">{{ old('billing_address') }}</textarea>
                </div>
                <div>
                    <label class="field-label" for="billing_postcode">{{ __('shop.postcode') }}</label>
                    <input id="billing_postcode" type="text" name="billing_postcode" maxlength="20" class="field" value="{{ old('billing_postcode') }}">
                </div>
            </div>
        </section>

        {{-- ---------------------------------------------------------- kargo --}}
        <section>
            <h2 class="text-[1.125rem] font-medium mb-4">{{ __('shop.shipping_method') }}</h2>
            <div class="space-y-3">
                @foreach ($methods as $method)
                    <label class="flex items-start gap-3 border border-line p-4 cursor-pointer has-[:checked]:border-ink">
                        <input type="radio" name="shipping_method_id" value="{{ $method->id }}" class="mt-1 accent-black"
                               @checked($loop->first) data-shipping-price="{{ $method->costFor($totals['subtotal'] - $totals['discount']) }}">
                        <span class="flex-1">
                            <span class="flex items-center justify-between gap-3">
                                <span class="font-medium">{{ $method->t('name') }}</span>
                                <span>
                                    {{-- Kargo ucreti sunucu tarafinda hesaplanir. --}}
                                    @php
                                        $cost = $method->costFor($totals['subtotal'] - $totals['discount']);
                                    @endphp
                                    {{ $cost > 0 ? money($cost) : __('shop.free') }}
                                </span>
                            </span>
                            @if ($method->t('description'))
                                <span class="block text-[0.875rem] text-muted mt-0.5">{{ $method->t('description') }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- ---------------------------------------------------------- odeme --}}
        <section>
            <h2 class="text-[1.125rem] font-medium mb-4">{{ __('shop.payment_method') }}</h2>
            <div class="border border-line p-4 flex items-start gap-3">
                <x-ui.icon name="shield" :size="22" class="text-brand shrink-0 mt-0.5" />
                <div>
                    <p class="font-medium">PayTR</p>
                    <p class="text-[0.875rem] text-muted mt-0.5">{{ __('shop.secure_payment') }}</p>
                    <p class="text-[0.875rem] text-muted">{{ __('shop.payment_currency_note') }}</p>
                    @if ($simulation)
                        <p class="text-[0.8125rem] text-sale mt-2">{{ __('shop.simulation_text') }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section>
            <label class="field-label" for="note">{{ __('shop.order_note') }}</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="field">{{ old('note') }}</textarea>
        </section>
    </div>

    {{-- --------------------------------------------------------------- ozet --}}
    <aside class="bg-cream p-6 h-fit lg:sticky lg:top-24">
        <h2 class="text-[1.125rem] font-medium mb-5">{{ __('shop.your_order') }}</h2>

        <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
            @foreach ($cart->items as $item)
                <div class="flex gap-3">
                    <div class="w-16 h-16 bg-white shrink-0">
                        <img src="{{ media_url($item->product->cover) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="block text-[0.9375rem] truncate">{{ $item->product->t('name') }}</span>
                        @if ($item->variant)
                            <span class="block text-[0.8125rem] text-muted">{{ $item->variant->t('name') }}</span>
                        @endif
                        <span class="block text-[0.8125rem] text-muted">{{ $item->quantity }} × {{ money($item->unit_price) }}</span>
                    </div>
                    <span class="text-[0.9375rem] font-medium">{{ money($item->lineTotal()) }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 pt-5 border-t border-line space-y-3 text-[0.9375rem]">
            <div class="flex justify-between">
                <span class="text-muted">{{ __('shop.subtotal') }}</span>
                <span>{{ money($totals['subtotal']) }}</span>
            </div>
            @if ($totals['discount'] > 0)
                <div class="flex justify-between text-success">
                    <span>{{ __('shop.discount') }}</span>
                    <span>-{{ money($totals['discount']) }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-muted">{{ __('shop.shipping') }}</span>
                <span data-summary-shipping>{{ $totals['shipping'] > 0 ? money($totals['shipping']) : __('shop.free') }}</span>
            </div>
            <div class="flex justify-between pt-3 border-t border-line text-[1.125rem] font-medium">
                <span>{{ __('shop.total') }}</span>
                <span data-summary-total>{{ money($totals['total']) }}</span>
            </div>
        </div>

        <label class="flex items-start gap-2.5 mt-5 text-[0.875rem] cursor-pointer">
            <input type="checkbox" name="terms" value="1" required class="mt-0.5 w-4 h-4 accent-black">
            <span>{{ __('shop.terms_accept') }}</span>
        </label>

        <button type="submit" class="btn btn-primary btn-lg w-full mt-5">{{ __('shop.place_order') }}</button>
    </aside>
</form>

@push('scripts')
<script>
    (function () {
        // Fatura adresi alanlarini ac/kapat
        const toggle = document.querySelector('[data-billing-toggle]');
        const fields = document.querySelector('[data-billing-fields]');
        if (toggle && fields) {
            const sync = () => fields.classList.toggle('hidden', toggle.checked);
            toggle.addEventListener('change', sync);
            sync();
        }

        // Kayitli adresi forma doldur
        document.querySelectorAll('[data-fill-address]').forEach(function (button) {
            button.addEventListener('click', function () {
                const data = JSON.parse(button.dataset.fillAddress);
                Object.entries(data).forEach(function ([key, value]) {
                    const input = document.querySelector('[name="' + key + '"]');
                    if (input && value) input.value = value;
                });
            });
        });

        // Kargo secimi degisince toplami guncelle
        @php
            $jsSubtotal = $totals['subtotal'] - $totals['discount'];
            $jsCurrency = app(\App\Services\CurrencyService::class)->current();
            $jsRate = $jsCurrency->effectiveRate();
            $jsSymbol = $jsCurrency->symbol;
            $jsFree = __('shop.free');
        @endphp
        const subtotal = @json($jsSubtotal);
        const shippingOut = document.querySelector('[data-summary-shipping]');
        const totalOut = document.querySelector('[data-summary-total]');
        const rate = @json($jsRate);
        const symbol = @json($jsSymbol);
        const freeLabel = @json($jsFree);

        const fmt = (tryAmount) => (tryAmount * rate).toLocaleString('tr-TR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }) + ' ' + symbol;

        document.querySelectorAll('[data-shipping-price]').forEach(function (input) {
            input.addEventListener('change', function () {
                const cost = parseFloat(input.dataset.shippingPrice || '0');
                if (shippingOut) shippingOut.textContent = cost > 0 ? fmt(cost) : freeLabel;
                if (totalOut) totalOut.textContent = fmt(subtotal + cost);
            });
        });
    })();
</script>
@endpush

@endsection
