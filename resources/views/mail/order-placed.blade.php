@php($locale = $order->locale)

<x-mail::message>
# {{ __('shop.mail_order_greeting', ['name' => $order->customerName()], $locale) }}

{{ __('shop.mail_order_intro', [], $locale) }}

**{{ __('shop.order_number', [], $locale) }}:** {{ $order->number }}
**{{ __('shop.order_date', [], $locale) }}:** {{ $order->created_at->translatedFormat('d.m.Y H:i') }}

<x-mail::table>
| {{ __('shop.product', [], $locale) }} | {{ __('shop.quantity', [], $locale) }} | {{ __('shop.total', [], $locale) }} |
| :--- | :---: | ---: |
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ money_base($item->line_total) }} |
@endforeach
</x-mail::table>

**{{ __('shop.subtotal', [], $locale) }}:** {{ money_base($order->subtotal) }}
@if ((float) $order->discount_total > 0)
**{{ __('shop.discount', [], $locale) }}:** -{{ money_base($order->discount_total) }}
@endif
**{{ __('shop.shipping', [], $locale) }}:** {{ (float) $order->shipping_total > 0 ? money_base($order->shipping_total) : __('shop.free', [], $locale) }}
**{{ __('shop.total', [], $locale) }}:** {{ money_base($order->grand_total) }}

<x-mail::button :url="localized_route('track', [], $locale)">
{{ __('shop.track_order', [], $locale) }}
</x-mail::button>

{{ __('shop.mail_order_outro', [], $locale) }}

{{ setting('site_name', __('shop.brand', [], $locale)) }}
</x-mail::message>
