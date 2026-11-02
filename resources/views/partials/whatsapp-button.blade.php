{{--
    Kayan WhatsApp dugmesi.

    Katalogda fiyat yok; musteri "Fiyat sorun" diyerek geliyor. Urun
    sayfasindan yazarken hangi urun icin sordugunu elle yazmak zorunda
    kalmasin diye mesaj hazir geliyor: urun sayfasi @section('whatsapp_message')
    ile urun adini bildirir, diger sayfalarda genel metin kullanilir.
--}}
@php
    $waMessage = trim($__env->yieldContent('whatsapp_message')) ?: __('shop.whatsapp_general');
    $waUrl = whatsapp_url($waMessage);
@endphp

@if ($waUrl)
    <a href="{{ $waUrl }}" target="_blank" rel="noopener"
       class="wa-fab"
       aria-label="{{ __('shop.whatsapp_write') }}"
       title="{{ __('shop.whatsapp_write') }}">
        {{-- Marka zemini uzerinde ikon HER ZAMAN beyaz: Tailwind text-white
             tema eslemesi yuzunden guvenilmez. --}}
        <span style="color: #ffffff; display: flex">
            <x-ui.icon name="whatsapp-solid" :size="26" />
        </span>
        <span class="wa-fab__label">{{ __('shop.whatsapp_write') }}</span>
    </a>
@endif
