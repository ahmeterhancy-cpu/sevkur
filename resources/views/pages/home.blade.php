@extends('layouts.app')

{{-- Ana sayfa kendi basligini vermez: ayarlardaki "site_title" kullanilir. --}}
@section('page_key', 'home')

@section('content')

    {{-- ------------------------------------------------------------------ hero --}}
    @if ($sliders->isNotEmpty())
        {{-- Asimetrik editoryal hero: yazi fotografin UZERINDE degil, yanindaki
             krem panoda. Fotograf saga, ekranin kenarina kadar tasiyor. Yazi
             perdeye muhtac olmadigi icin okunurluk fotografa bagli degil. --}}
        <section class="hero relative bg-cream overflow-hidden"
                 data-slider="{{ max(2000, (int) setting('home_slider_interval', 6500)) }}">
            <div class="flex transition-transform duration-700 ease-out" data-slider-track>
                @foreach ($sliders as $slide)
                    <div class="w-full shrink-0" data-slide>
                        {{-- Hiza container-x'ten geliyor; fotograf negatif kenar
                             bosluguyla sagda ekranin disina tasiyor. Tasma
                             kirpiliyor, bu yuzden biraz fazlasi zararsiz —
                             100vw kaydirma cubugunu da saydigi icin hesapla
                             hizalamak yaziyi 31px kaydirmisti. --}}
                        <div class="container-x lg:grid lg:grid-cols-12 lg:items-stretch">

                            {{-- fotograf: mobilde ustte, masaustunde sagda --}}
                            <div class="lg:col-span-7 lg:order-2 relative min-h-[15rem] h-[52vw] lg:h-auto lg:min-h-[36rem]
                                        -mx-4 lg:mx-0 lg:mr-[calc((100vw-var(--spacing-container))/-2-1.5rem)]">
                                <img src="{{ media_url($slide->image) }}" alt=""
                                     class="absolute inset-0 w-full h-full object-cover"
                                     @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                            </div>

                            {{-- yazi panosu --}}
                            <div class="lg:col-span-5 lg:order-1 flex items-center hero__panel">
                                <div class="w-full max-w-[32rem] {{ $slide->text_align === 'center' ? 'mx-auto text-center' : ($slide->text_align === 'right' ? 'ml-auto text-right' : '') }}"
                                     style="color: {{ panel_ink($slide->text_color) }}">

                                    @if ($slide->t('badge'))
                                        <span class="eyebrow block">{{ $slide->t('badge') }}</span>
                                    @endif
                                    @if ($slide->t('title'))
                                        <h2 class="mt-4 text-[clamp(2rem,3.2vw,3.5rem)] leading-[1.05] font-normal tracking-[-0.03em]"
                                            style="color: inherit">
                                            {{ $slide->t('title') }}
                                        </h2>
                                    @endif
                                    @if ($slide->t('subtitle'))
                                        <p class="mt-5 text-[1.0625rem] leading-relaxed opacity-70 max-w-[38ch]">{{ $slide->t('subtitle') }}</p>
                                    @endif
                                    @if ($slide->link)
                                        <a href="{{ $slide->link }}" class="btn btn-primary btn-lg mt-9">
                                            {{ $slide->t('button_text') ?: __('shop.shop') }}
                                            <x-ui.icon name="arrow-right" :size="16" />
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($sliders->count() > 1)
                {{-- Oklar ve noktalar fotografin ortasinda degil, panonun
                     altinda: editoryal duzende gezinme yaziyla ayni sutunda. --}}
                <div class="absolute inset-x-0 bottom-7 z-10 pointer-events-none">
                    <div class="container-x flex items-center gap-4 pointer-events-auto">
                    <div class="flex gap-2">
                        <button type="button" data-slider-prev aria-label="{{ __('shop.back') }}" class="icon-btn">
                            <x-ui.icon name="chevron-left" :size="18" />
                        </button>
                        <button type="button" data-slider-next aria-label="{{ __('shop.view') }}" class="icon-btn">
                            <x-ui.icon name="chevron-right" :size="18" />
                        </button>
                    </div>

                    <div class="flex gap-2">
                        @foreach ($sliders as $slide)
                            <button type="button" data-slider-dot
                                    class="w-2.5 h-2.5 rounded-full bg-ink/25 transition-all [&.is-active]:w-7 [&.is-active]:bg-ink"
                                    aria-label="{{ $loop->iteration }}"></button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- ------------------------------------------------------------ kategoriler --}}
    @if ($featuredCategories->isNotEmpty())
        <section class="container-x section-y">
            <div class="flex flex-wrap items-end justify-between gap-4 section-head">
                <div>
                    <span class="eyebrow">{{ __('shop.categories') }}</span>
                    <h2 class="section-title mt-2">{{ setting_t('home_categories_title', 'Odalarına göre keşfet') }}</h2>
                </div>
                <a href="{{ localized_route('shop') }}" class="btn btn-ghost btn-sm">{{ __('shop.view_all') }}</a>
            </div>

            {{-- Mozaik: ilk oda buyuk kutuyu alir, digerleri cevresine yerlesir.
                 Esit bes kucuk karede oda fotograflari okunmuyordu. Bes kutu
                 4x2 izgarayi tam kapatir (2x2 + dort tekli). --}}
            @php
                // Buyuk kutu 2x2 yer kapliyor; 4x2 izgarada geriye dort hucre
                // kaliyor. Kutu sayisi yonetimden degistirilebildigi icin SON
                // kutu artan hucreleri kapatiyor, yoksa izgarada bosluk kalirdi.
                $tekliSayisi = max(0, $featuredCategories->count() - 1);
                $sonKutuSpan = max(1, 4 - max(0, $tekliSayisi - 1));
            @endphp

            <div class="grid grid-cols-2 lg:grid-cols-4 auto-rows-[11rem] lg:auto-rows-[13.5rem] gap-3 lg:gap-4">
                @foreach ($featuredCategories as $category)
                    @php($feature = $loop->first)
                    @php($sonKutu = $loop->last && ! $loop->first && $sonKutuSpan > 1)
                    <a href="{{ localized_route('category', ['category' => $category->slug]) }}"
                       class="cat-tile reveal {{ $feature ? 'cat-tile--feature col-span-2 row-span-2' : '' }} {{ $sonKutu ? 'lg:col-span-'.$sonKutuSpan : '' }}"
                       style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms">
                        <img src="{{ media_url($category->image) }}" alt="{{ $category->t('name') }}"
                             @if ($feature) fetchpriority="high" @else loading="lazy" @endif>

                        {{-- Fotograf uzerindeki yazi: Tailwind text-white tema
                             esleme yuzunden guvenilmez, renk satir ici veriliyor. --}}
                        <div class="cat-tile__body" style="color: #ffffff">
                            <h3 class="cat-tile__name">{{ $category->t('name') }}</h3>

                            @if ($category->product_total)
                                <p class="cat-tile__count">
                                    {{ trans_choice('shop.product_count', $category->product_total, ['count' => $category->product_total]) }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------ orta afis --}}
    @if ($midBanners->isNotEmpty())
        <section class="container-x pb-[clamp(3.5rem,7vw,7rem)]">
            @php($midCols = ['md:grid-cols-1', 'md:grid-cols-2', 'md:grid-cols-3'][min(3, max(1, $midBanners->count())) - 1])
            <div class="grid gap-4 lg:gap-6 {{ $midCols }}">
                @foreach ($midBanners as $banner)
                    <a href="{{ $banner->link ?: localized_route('shop') }}"
                       class="group relative block overflow-hidden bg-cream aspect-[4/3] reveal">
                        <img src="{{ media_url($banner->image) }}" alt="{{ $banner->t('title') }}"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        <div class="absolute inset-0 {{ contrast_scrim($banner->text_color) }}"></div>
                        <div class="absolute inset-0 flex flex-col justify-center px-8" style="color: {{ $banner->text_color }}">
                            @if ($banner->t('subtitle'))
                                <span class="text-[0.8125rem] uppercase tracking-widest opacity-80">{{ $banner->t('subtitle') }}</span>
                            @endif
                            <h3 class="text-[1.5rem] lg:text-[1.75rem] font-medium mt-1" style="color: inherit">{{ $banner->t('title') }}</h3>
                            <span class="mt-4 inline-flex items-center gap-2 text-[0.8125rem] uppercase tracking-widest font-medium">
                                {{ $banner->t('button_text') ?: __('shop.view_all') }}
                                <x-ui.icon name="arrow-right" :size="15" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ---------------------------------------------------------- cok satanlar --}}
    @if ($bestSellers->isNotEmpty())
        <section class="container-x pb-[clamp(3.5rem,7vw,7rem)]">
            <div class="text-center section-head">
                <h2 class="section-title">{{ setting_t('home_bestsellers_title', 'Çok Satanlar') }}</h2>
                <p class="section-sub mt-2">{{ setting_t('home_bestsellers_sub', 'Müşterilerimizin en çok tercih ettiği parçalar.') }}</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 lg:gap-4">
                @foreach ($bestSellers as $product)
                    <div class="reveal" style="transition-delay: {{ $loop->index * 40 }}ms">
                        <x-product-card :product="$product" style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------- oda raylari --}}
    {{-- Katalogda 600'u askin urun var; fiyat girilmedigi icin "Cok Satanlar"
         ve "Firsatlar" bolumleri bos kaliyor ve sayfa ince duruyordu. En dolu
         oda kategorileri birer rayda gosteriliyor. --}}
    @foreach ($categoryRails as $rail)
        @php($category = $rail['kategori'])
        {{-- Bir ray BOLUNMUS duzende: ayni yapidaki raylar art arda gelince
             sayfa tekduze oluyordu. Solda odanin kendi fotografi, sagda
             urunler. Kacinci ray oldugu yonetimden secilir. --}}
        @php($split = $loop->iteration === $railSplit)

        <section class="{{ $loop->odd ? 'bg-cream' : '' }} py-[clamp(3rem,6vw,5.5rem)]">
            <div class="container-x" data-rail>
                <div class="{{ $split ? 'grid lg:grid-cols-12 gap-6 lg:gap-8 items-stretch' : '' }}">

                    @if ($split)
                        <a href="{{ localized_route('category', ['category' => $category->slug]) }}"
                           class="cat-tile cat-tile--feature lg:col-span-4 min-h-[18rem] lg:min-h-full reveal">
                            <img src="{{ media_url($category->image) }}" alt="{{ $category->t('name') }}" loading="lazy">

                            {{-- Fotograf uzerinde beyaz yazi: baslik h3 oldugu icin
                                 rengi .cat-tile__name uzerinden miras alir. --}}
                            <div class="cat-tile__body" style="color: #ffffff">
                                {{-- span satir ici; .cat-tile__name inline-block oldugu icin ayni
                                     satira binip basligin uzerine geliyordu. --}}
                                <span class="eyebrow block" style="color: rgba(255,255,255,0.75)">{{ __('shop.categories') }}</span>
                                <h2 class="cat-tile__name mt-2">{{ $category->t('name') }}</h2>
                                <p class="cat-tile__count">
                                    {{ trans_choice('shop.product_count', $rail['toplam'], ['count' => $rail['toplam']]) }}
                                </p>
                            </div>
                        </a>
                    @endif

                    <div class="{{ $split ? 'lg:col-span-8 min-w-0' : '' }}">
                        <div class="flex flex-wrap items-end justify-between gap-4 section-head">
                            <div>
                                @unless ($split)
                                    <span class="eyebrow">{{ __('shop.categories') }}</span>
                                    <h2 class="section-title mt-2">{{ $category->t('name') }}</h2>
                                    <p class="section-sub mt-2">
                                        {{ trans_choice('shop.product_count', $rail['toplam'], ['count' => $rail['toplam']]) }}
                                    </p>
                                @else
                                    <p class="section-sub">{{ __('shop.categories') }}</p>
                                @endunless
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ localized_route('category', ['category' => $category->slug]) }}"
                                   class="btn btn-ghost btn-sm">{{ __('shop.view_all') }}</a>
                                <button type="button" data-rail-prev class="icon-btn" aria-label="{{ __('shop.back') }}">
                                    <x-ui.icon name="chevron-left" :size="18" />
                                </button>
                                <button type="button" data-rail-next class="icon-btn" aria-label="{{ __('shop.view') }}">
                                    <x-ui.icon name="chevron-right" :size="18" />
                                </button>
                            </div>
                        </div>

                        {{-- Alt kategoriler: 600 urunluk katalogda gezinmeyi kisaltir --}}
                        @if ($category->children->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mb-7">
                                @foreach ($category->children as $child)
                                    <a href="{{ localized_route('category', ['category' => $child->slug]) }}"
                                       class="chip">{{ $child->t('name') }}</a>
                                @endforeach
                            </div>
                        @endif

                        <div class="rail gap-4 lg:gap-6 -mx-4 px-4" data-rail-track>
                            @foreach ($rail['urunler'] as $product)
                                <div class="w-[16rem] lg:w-[19rem]">
                                    <x-product-card :product="$product" style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    {{-- ------------------------------------------------------------ genis afis --}}
    @if ($wideBanner)
        <section class="relative bg-cream">
            <div class="absolute inset-0">
                <img src="{{ media_url($wideBanner->image) }}" alt="" class="w-full h-full object-cover" loading="lazy">
                <div class="absolute inset-0 {{ contrast_scrim($wideBanner->text_color) }}"></div>
            </div>
            <div class="container-x relative py-20 lg:py-28">
                <div class="max-w-md" style="color: {{ $wideBanner->text_color }}">
                    <span class="eyebrow">{{ $wideBanner->t('subtitle') }}</span>
                    <h2 class="mt-3 text-[2rem] lg:text-[2.75rem] leading-tight font-medium" style="color: inherit">{{ $wideBanner->t('title') }}</h2>
                    <a href="{{ $wideBanner->link ?: localized_route('shop') }}" class="btn btn-primary btn-lg mt-8">
                        {{ $wideBanner->t('button_text') ?: __('shop.shop') }}
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- -------------------------------------------------------------- firsatlar --}}
    @if ($deals->isNotEmpty())
        <section class="container-x section-y">
            <div class="flex flex-wrap items-end justify-between gap-4 section-head">
                <div>
                    <span class="eyebrow">{{ __('shop.badge_sale') }}</span>
                    <h2 class="section-title mt-2">{{ setting_t('home_deals_title', 'Haftanın Fırsatları') }}</h2>
                </div>
                @php($countdownProduct = $deals->first(fn ($p) => $p->hasCountdown()))
                @if ($countdownProduct)
                    <div class="flex items-center gap-4" data-countdown="{{ $countdownProduct->sale_ends_at->timestamp }}">
                        <span class="text-[0.8125rem] uppercase tracking-widest text-muted">{{ __('shop.countdown_ends') }}</span>
                        <div class="flex gap-2">
                            @foreach (['days' => __('shop.days'), 'hours' => __('shop.hours'), 'minutes' => __('shop.minutes'), 'seconds' => __('shop.seconds')] as $unit => $label)
                                <div class="w-14 py-2 bg-cream text-center rounded">
                                    <span class="block text-[1.125rem] font-medium leading-none" data-unit="{{ $unit }}">00</span>
                                    <span class="block text-[0.625rem] uppercase tracking-wider text-muted mt-1">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 lg:gap-4">
                @foreach ($deals as $product)
                    <div class="reveal" style="transition-delay: {{ $loop->index * 40 }}ms">
                        <x-product-card :product="$product" style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------- yeni gelen --}}
    @if ($newArrivals->isNotEmpty())
        <section class="bg-cream py-14 lg:py-20">
            {{-- Oklar ile ray ayni [data-rail] kabugunda olmali --}}
            <div class="container-x" data-rail>
                <div class="flex flex-wrap items-end justify-between gap-4 section-head">
                    <div>
                        <span class="eyebrow">{{ __('shop.badge_new') }}</span>
                        <h2 class="section-title mt-2">{{ setting_t('home_new_title', 'Yeni Gelenler') }}</h2>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" data-rail-prev class="icon-btn" aria-label="{{ __('shop.back') }}"><x-ui.icon name="chevron-left" :size="18" /></button>
                        <button type="button" data-rail-next class="icon-btn" aria-label="{{ __('shop.view') }}"><x-ui.icon name="chevron-right" :size="18" /></button>
                    </div>
                </div>

                <div class="rail gap-4 lg:gap-6 -mx-4 px-4" data-rail-track>
                    @foreach ($newArrivals as $product)
                        <div class="w-[16rem] lg:w-[19rem]">
                            <x-product-card :product="$product" style="--reveal-delay: {{ min($loop->index, 5) * 70 }}ms" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------------ blog --}}
    @if ($posts->isNotEmpty())
        <section class="container-x section-y">
            <div class="text-center section-head">
                <span class="eyebrow">{{ __('shop.blog') }}</span>
                <h2 class="section-title mt-2">{{ setting_t('home_blog_title', 'Yaşam Alanı Notları') }}</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
                @foreach ($posts as $post)
                    <article class="group reveal">
                        <a href="{{ localized_route('blog.show', ['post' => $post->slug]) }}" class="block aspect-[4/3] bg-cream overflow-hidden">
                            <img src="{{ media_url($post->cover) }}" alt="{{ $post->t('title') }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        </a>
                        @if ($post->category)
                            <span class="mt-4 inline-block text-[0.75rem] uppercase tracking-widest text-brand">{{ $post->category->t('name') }}</span>
                        @endif
                        <h3 class="mt-2 text-[1.125rem] font-medium leading-snug">
                            <a href="{{ localized_route('blog.show', ['post' => $post->slug]) }}" class="link-underline">{{ $post->t('title') }}</a>
                        </h3>
                        <p class="mt-2 text-[0.9375rem] text-muted line-clamp-2">{{ $post->t('excerpt') }}</p>
                        <a href="{{ localized_route('blog.show', ['post' => $post->slug]) }}"
                           class="mt-3 inline-flex items-center gap-2 text-[0.8125rem] uppercase tracking-widest font-medium link-underline">
                            {{ __('shop.read_more') }} <x-ui.icon name="arrow-right" :size="15" />
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

@endsection
