@php
    $socials = [
        'facebook' => setting('social_facebook'),
        'instagram' => setting('social_instagram'),
        'youtube' => setting('social_youtube'),
        // Ayri bir WhatsApp baglantisi girilmediyse magaza numarasindan
        // uretilen bag kullanilir; numara zaten ayarlarda duruyor.
        'whatsapp' => setting('social_whatsapp') ?: whatsapp_url(),
    ];
@endphp

{{-- --------------------------------------------------------------- guvenceler --}}
<section class="border-t border-line">
    <div class="container-x grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-8 py-12">
        @foreach ([
            ['truck', __('shop.free_shipping_over', ['amount' => money((float) setting('free_shipping_over', 5000))]), setting_t('promise_shipping', 'Türkiye geneli kurumsal kargo')],
            ['refresh', setting_t('promise_return_title', '14 gün içinde iade'), setting_t('promise_return', 'Koşulsuz değişim ve iade hakkı')],
            ['shield', setting_t('promise_secure_title', 'Güvenli ödeme'), setting_t('promise_secure', '3D Secure ile korumalı alışveriş')],
            ['headset', setting_t('promise_support_title', 'Uzman desteği'), setting_t('promise_support', 'Hafta içi 09:00 - 18:00 arası')],
        ] as [$icon, $title, $text])
            <div class="flex gap-4 reveal">
                <x-ui.icon :name="$icon" :size="30" class="text-brand shrink-0" :stroke="1.2" />
                <div>
                    <h3 class="text-[0.9375rem] font-medium">{{ $title }}</h3>
                    <p class="text-[0.875rem] text-muted">{{ $text }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ------------------------------------------------------------------ bulten --}}
<section class="bg-cream">
    <div class="container-x py-14 grid lg:grid-cols-2 gap-8 items-center">
        <div>
            <span class="eyebrow">{{ __('shop.subscribe') }}</span>
            <h2 class="section-title mt-2">{{ __('shop.newsletter_title') }}</h2>
            <p class="section-sub mt-2 max-w-md">{{ __('shop.newsletter_text') }}</p>
        </div>
        <form action="{{ localized_route('newsletter') }}" method="POST" data-newsletter class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="email" name="email" required placeholder="{{ __('shop.email_placeholder') }}"
                   class="field h-13 rounded-full flex-1 bg-white" aria-label="{{ __('shop.email_placeholder') }}">
            <button type="submit" class="btn btn-primary">{{ __('shop.subscribe') }}</button>
        </form>
    </div>
</section>

{{-- --------------------------------------------------------------- alt bilgi --}}
<footer class="bg-ink text-white/70 pt-16">
    <div class="container-x grid gap-10 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <a href="{{ localized_route('home') }}" class="inline-block mb-4">
                @if (setting('logo_light'))
                    <img src="{{ media_url(setting('logo_light')) }}" alt="{{ setting('site_name', __('shop.brand')) }}" class="h-9 w-auto">
                @else
                    <span class="text-[1.6rem] font-semibold tracking-tight" style="color:#ffffff">
                        {{ setting('site_name', 'Sevkur') }}<span class="text-brand">.</span>
                    </span>
                @endif
            </a>
            <p class="text-[0.9375rem] leading-relaxed">{{ setting_t('footer_about', __('shop.footer_about')) }}</p>

            <div class="flex items-center gap-3 mt-6">
                @foreach ($socials as $network => $url)
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener"
                           class="w-9 h-9 rounded-full border border-white/20 flex items-center justify-center hover:bg-brand hover:border-brand"
                           style="color:#ffffff" aria-label="{{ ucfirst($network) }}">
                            <x-ui.icon :name="$network" :size="17" />
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-[0.9375rem] font-medium mb-4" style="color:#ffffff">{{ __('shop.quick_links') }}</h3>
            <ul class="space-y-2.5 text-[0.9375rem]">
                <li><a href="{{ localized_route('shop') }}" class="hover:text-white">{{ __('shop.shop') }}</a></li>
                <li><a href="{{ localized_route('brands') }}" class="hover:text-white">{{ __('shop.brands') }}</a></li>
                <li><a href="{{ localized_route('blog') }}" class="hover:text-white">{{ __('shop.blog') }}</a></li>
                <li><a href="{{ localized_route('contact') }}" class="hover:text-white">{{ __('shop.contact') }}</a></li>
                <li><a href="{{ localized_route('faq') }}" class="hover:text-white">{{ __('shop.faq') }}</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-[0.9375rem] font-medium mb-4" style="color:#ffffff">{{ __('shop.customer_service') }}</h3>
            <ul class="space-y-2.5 text-[0.9375rem]">
                <li><a href="{{ localized_route('track') }}" class="hover:text-white">{{ __('shop.track_order') }}</a></li>
                <li><a href="{{ localized_route('account.index') }}" class="hover:text-white">{{ __('shop.my_account') }}</a></li>
                <li><a href="{{ localized_route('wishlist') }}" class="hover:text-white">{{ __('shop.wishlist') }}</a></li>
                @foreach ($layoutFooterPages as $page)
                    <li><a href="{{ localized_route('page', ['page' => $page->slug]) }}" class="hover:text-white">{{ $page->t('title') }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-[0.9375rem] font-medium mb-4" style="color:#ffffff">{{ __('shop.contact_info') }}</h3>
            <ul class="space-y-3 text-[0.9375rem]">
                @if (setting('address'))
                    <li class="flex gap-3"><x-ui.icon name="pin" :size="18" class="shrink-0 mt-0.5 text-brand" /><span>{{ setting('address') }}</span></li>
                @endif
                @if (setting('phone'))
                    <li class="flex gap-3"><x-ui.icon name="phone" :size="18" class="shrink-0 mt-0.5 text-brand" /><a href="tel:{{ preg_replace('/\s+/', '', setting('phone')) }}" class="hover:text-white">{{ setting('phone') }}</a></li>
                @endif
                @if (setting('email'))
                    <li class="flex gap-3"><x-ui.icon name="mail" :size="18" class="shrink-0 mt-0.5 text-brand" /><a href="mailto:{{ setting('email') }}" class="hover:text-white">{{ setting('email') }}</a></li>
                @endif
                @if (setting_t('working_hours'))
                    <li class="flex gap-3"><x-ui.icon name="clock" :size="18" class="shrink-0 mt-0.5 text-brand" /><span>{{ setting_t('working_hours') }}</span></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="container-x mt-12 py-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-[0.875rem]">
        <p>&copy; {{ date('Y') }} {{ setting('site_name', __('shop.brand')) }}. {{ __('shop.rights_reserved') }}</p>
        <p class="flex items-center gap-2">
            <x-ui.icon name="shield" :size="16" class="text-brand" />
            {{ __('shop.secure_payment') }}
        </p>
    </div>
</footer>
