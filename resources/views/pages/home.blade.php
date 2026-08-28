@extends('layouts.app')

{{-- Ana sayfa kendi basligini vermez: ayarlardaki "site_title" kullanilir. --}}
@section('page_key', 'home')

@section('content')

    {{-- ------------------------------------------------------------------ hero --}}
    @if ($sliders->isNotEmpty())
        <section class="relative overflow-hidden bg-cream" data-slider="6500">
            <div class="flex transition-transform duration-700 ease-out" data-slider-track>
                @foreach ($sliders as $slide)
                    <div class="w-full shrink-0 relative" data-slide>
                        <div class="absolute inset-0">
                            <img src="{{ media_url($slide->image) }}" alt="" class="w-full h-full object-cover" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
                            {{-- Fotograf uzerindeki yazi okunabilir kalsin --}}
                            <div class="absolute inset-0 {{ contrast_scrim($slide->text_color, $slide->text_align) }}"></div>
                        </div>
                        <div class="container-x relative min-h-[26rem] lg:min-h-[38rem] flex items-center py-16">
                            <div class="max-w-lg {{ $slide->text_align === 'center' ? 'mx-auto text-center' : ($slide->text_align === 'right' ? 'ml-auto text-right' : '') }}"
                                 style="color: {{ $slide->text_color }}">
                                @if ($slide->t('badge'))
                                    <span class="eyebrow">{{ $slide->t('badge') }}</span>
                                @endif
                                @if ($slide->t('title'))
                                    <h2 class="mt-3 text-[2rem] lg:text-[3.25rem] leading-[1.1] font-medium" style="color: inherit">
                                        {{ $slide->t('title') }}
                                    </h2>
                                @endif
                                @if ($slide->t('subtitle'))
                                    <p class="mt-4 text-[1.0625rem] opacity-80">{{ $slide->t('subtitle') }}</p>
                                @endif
                                @if ($slide->link)
                                    <a href="{{ $slide->link }}" class="btn btn-primary btn-lg mt-8">
                                        {{ $slide->t('button_text') ?: __('shop.shop') }}
                                        <x-ui.icon name="arrow-right" :size="16" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($sliders->count() > 1)
                <button type="button" data-slider-prev aria-label="{{ __('shop.back') }}"
                        class="hidden lg:flex absolute left-6 top-1/2 -translate-y-1/2 icon-btn w-11 h-11">
                    <x-ui.icon name="chevron-left" :size="20" />
                </button>
                <button type="button" data-slider-next aria-label="{{ __('shop.view') }}"
                        class="hidden lg:flex absolute right-6 top-1/2 -translate-y-1/2 icon-btn w-11 h-11">
                    <x-ui.icon name="chevron-right" :size="20" />
                </button>

                <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2">
                    @foreach ($sliders as $slide)
                        <button type="button" data-slider-dot
                                class="w-2.5 h-2.5 rounded-full bg-ink/25 transition-all [&.is-active]:w-7 [&.is-active]:bg-ink"
                                aria-label="{{ $loop->iteration }}"></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- ------------------------------------------------------------ kategoriler --}}
    @if ($featuredCategories->isNotEmpty())
        <section class="container-x py-14 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
                <div>
                    <span class="eyebrow">{{ __('shop.categories') }}</span>
                    <h2 class="section-title mt-2">{{ setting_t('home_categories_title', 'Odalarına göre keşfet') }}</h2>
                </div>
                <a href="{{ localized_route('shop') }}" class="btn btn-ghost btn-sm">{{ __('shop.view_all') }}</a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 lg:gap-6">
                @foreach ($featuredCategories as $category)
                    <a href="{{ localized_route('category', ['category' => $category->slug]) }}"
                       class="group text-center reveal">
                        <div class="aspect-square bg-cream overflow-hidden rounded-full">
                            <img src="{{ media_url($category->image) }}" alt="{{ $category->t('name') }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        </div>
                        <h3 class="mt-3 text-[0.9375rem] font-normal group-hover:text-brand">{{ $category->t('name') }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------ orta afis --}}
    @if ($midBanners->isNotEmpty())
        <section class="container-x pb-14 lg:pb-20">
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
        <section class="container-x pb-14 lg:pb-20">
            <div class="text-center mb-10">
                <h2 class="section-title">{{ setting_t('home_bestsellers_title', 'Çok Satanlar') }}</h2>
                <p class="section-sub mt-2">{{ setting_t('home_bestsellers_sub', 'Müşterilerimizin en çok tercih ettiği parçalar.') }}</p>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 lg:gap-x-6">
                @foreach ($bestSellers as $product)
                    <div class="reveal" style="transition-delay: {{ $loop->index * 40 }}ms">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

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
        <section class="container-x py-14 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
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

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 lg:gap-x-6">
                @foreach ($deals as $product)
                    <div class="reveal" style="transition-delay: {{ $loop->index * 40 }}ms">
                        <x-product-card :product="$product" />
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
                <div class="flex flex-wrap items-end justify-between gap-4 mb-8">
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
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------------------------ blog --}}
    @if ($posts->isNotEmpty())
        <section class="container-x py-14 lg:py-20">
            <div class="text-center mb-10">
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
