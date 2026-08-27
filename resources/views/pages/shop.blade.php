@extends('layouts.app')

@section('title', $category ? $category->t('name') : ($isSearch ? __('shop.search_results_for', ['term' => $term]) : __('shop.shop')))
@section('page_key', 'shop')

@section('content')

@php
    $crumbs = [];
    if ($category) {
        foreach ($category->breadcrumb() as $node) {
            $crumbs[$node->t('name')] = localized_route('category', ['category' => $node->slug]);
        }
    } elseif ($isSearch) {
        $crumbs[__('shop.search_results_for', ['term' => $term])] = null;
    } else {
        $crumbs[__('shop.shop')] = null;
    }

    $heading = $category
        ? $category->t('name')
        : ($isSearch ? __('shop.search_results_for', ['term' => $term]) : __('shop.shop'));
@endphp

<x-breadcrumb :items="$crumbs" :title="$heading" />

@if ($category && $category->t('description'))
    <div class="container-x pt-8">
        <p class="max-w-3xl text-muted">{{ $category->t('description') }}</p>
    </div>
@endif

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[17rem_1fr] gap-8 lg:gap-12">

    {{-- ------------------------------------------------------------- suzgecler --}}
    <aside>
        <button type="button" class="lg:hidden btn btn-ghost w-full mb-4" data-panel-open="filter-panel">
            <x-ui.icon name="filter" :size="18" /> {{ __('shop.filters') }}
        </button>

        <form method="GET" action="{{ $category ? localized_route('category', ['category' => $category->slug]) : ($isSearch ? localized_route('search') : localized_route('shop')) }}"
              data-filter-form id="shop-filters" class="hidden lg:block space-y-8">

            @if ($term !== '')
                <input type="hidden" name="q" value="{{ $term }}">
            @endif
            <input type="hidden" name="sirala" value="{{ $sort }}">
            <input type="hidden" name="adet" value="{{ $perPage }}">

            {{-- Kategoriler --}}
            <div>
                <h3 class="text-[0.9375rem] font-medium mb-3">{{ __('shop.categories') }}</h3>
                <ul class="space-y-2 text-[0.9375rem]">
                    @foreach ($categories as $root)
                        <li>
                            <a href="{{ localized_route('category', ['category' => $root->slug]) }}"
                               class="hover:text-brand {{ $category && $category->id === $root->id ? 'text-brand font-medium' : '' }}">
                                {{ $root->t('name') }}
                            </a>
                            @if ($root->activeChildren->isNotEmpty())
                                <ul class="mt-1.5 ml-3 space-y-1.5 text-[0.875rem] text-muted">
                                    @foreach ($root->activeChildren as $child)
                                        <li>
                                            <a href="{{ localized_route('category', ['category' => $child->slug]) }}"
                                               class="hover:text-brand {{ $category && $category->id === $child->id ? 'text-brand font-medium' : '' }}">
                                                {{ $child->t('name') }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Fiyat --}}
            <div class="pt-6 border-t border-line">
                <h3 class="text-[0.9375rem] font-medium mb-3">{{ __('shop.price') }}</h3>
                <div class="flex items-center gap-2">
                    <input type="number" name="min" value="{{ request('min') }}" min="0" step="1"
                           placeholder="{{ (int) $minPrice }}" class="field py-2 text-[0.875rem]" aria-label="min">
                    <span class="text-muted">–</span>
                    <input type="number" name="max" value="{{ request('max') }}" min="0" step="1"
                           placeholder="{{ (int) $maxPrice }}" class="field py-2 text-[0.875rem]" aria-label="max">
                </div>
                <button type="submit" class="btn btn-ghost btn-sm w-full mt-3">{{ __('shop.apply_filters') }}</button>
                <p class="text-[0.75rem] text-muted mt-2">{{ __('shop.payment_currency_note') }}</p>
            </div>

            {{-- Durum --}}
            <div class="pt-6 border-t border-line">
                <h3 class="text-[0.9375rem] font-medium mb-3">{{ __('shop.availability') }}</h3>
                <label class="flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                    <input type="checkbox" name="stokta" value="1" @checked(request()->boolean('stokta')) class="w-4 h-4 accent-black">
                    {{ __('shop.in_stock_only') }}
                </label>
                <label class="flex items-center gap-2.5 text-[0.9375rem] cursor-pointer mt-2">
                    <input type="checkbox" name="indirimli" value="1" @checked(request()->boolean('indirimli')) class="w-4 h-4 accent-black">
                    {{ __('shop.on_sale_only') }}
                </label>
            </div>

            {{-- Markalar --}}
            @if ($brands->isNotEmpty())
                <div class="pt-6 border-t border-line">
                    <h3 class="text-[0.9375rem] font-medium mb-3">{{ __('shop.brands') }}</h3>
                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                        @foreach ($brands as $brand)
                            <label class="flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                                <input type="checkbox" name="marka[]" value="{{ $brand->slug }}"
                                       @checked(in_array($brand->slug, (array) request('marka', []), true))
                                       class="w-4 h-4 accent-black">
                                {{ $brand->t('name') }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Ozellikler --}}
            @foreach ($attributes as $attribute)
                @if ($attribute->values->isNotEmpty())
                    <div class="pt-6 border-t border-line">
                        <h3 class="text-[0.9375rem] font-medium mb-3">{{ $attribute->t('name') }}</h3>
                        @php($selected = (array) (request('ozellik', [])[$attribute->slug] ?? []))

                        @if ($attribute->type === 'color')
                            <div class="flex flex-wrap gap-2">
                                @foreach ($attribute->values as $value)
                                    <label class="cursor-pointer" title="{{ $value->t('name') }}">
                                        <input type="checkbox" name="ozellik[{{ $attribute->slug }}][]" value="{{ $value->slug }}"
                                               @checked(in_array($value->slug, $selected, true)) class="sr-only peer">
                                        <span class="block w-7 h-7 rounded-full border border-line peer-checked:ring-2 peer-checked:ring-ink peer-checked:ring-offset-2"
                                              style="background: {{ $value->color_hex ?: '#ddd' }}"></span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($attribute->values as $value)
                                    <label class="flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                                        <input type="checkbox" name="ozellik[{{ $attribute->slug }}][]" value="{{ $value->slug }}"
                                               @checked(in_array($value->slug, $selected, true)) class="w-4 h-4 accent-black">
                                        {{ $value->t('name') }}
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach

            <a href="{{ $category ? localized_route('category', ['category' => $category->slug]) : localized_route('shop') }}"
               class="btn btn-ghost btn-sm w-full">{{ __('shop.clear_filters') }}</a>
        </form>
    </aside>

    {{-- ---------------------------------------------------------------- liste --}}
    <div>
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 border-b border-line">
            <p class="text-[0.875rem] text-muted">
                @if ($products->total() > 0)
                    {{ __('shop.showing_results', [
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'total' => $products->total(),
                    ]) }}
                @else
                    0 {{ __('shop.results') }}
                @endif
            </p>

            <form method="GET" data-filter-form class="flex items-center gap-3">
                @foreach (request()->except(['sirala', 'adet', 'page']) as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $sub => $item)
                            @if (is_array($item))
                                @foreach ($item as $deep)
                                    <input type="hidden" name="{{ $key }}[{{ $sub }}][]" value="{{ $deep }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                            @endif
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label class="text-[0.875rem] text-muted hidden sm:block">{{ __('shop.sort_by') }}</label>
                <select name="sirala" class="field py-2 w-auto text-[0.875rem]">
                    @foreach ([
                        'onerilen' => __('shop.sort_recommended'),
                        'fiyat-artan' => __('shop.sort_price_asc'),
                        'fiyat-azalan' => __('shop.sort_price_desc'),
                        'yeni' => __('shop.sort_newest'),
                        'populer' => __('shop.sort_popular'),
                        'puan' => __('shop.sort_rating'),
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="adet" class="field py-2 w-auto text-[0.875rem]" aria-label="{{ __('shop.per_page') }}">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if ($products->isEmpty())
            <div class="py-24 text-center">
                <x-ui.icon name="package" :size="52" class="text-line mx-auto" />
                <p class="mt-4 text-muted">{{ __('shop.no_products') }}</p>
                <a href="{{ localized_route('shop') }}" class="btn btn-primary mt-6">{{ __('shop.clear_filters') }}</a>
            </div>
        @else
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-10 lg:gap-x-6 pt-8">
                @foreach ($products as $product)
                    <div class="reveal" style="transition-delay: {{ ($loop->index % 6) * 40 }}ms">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $products->onEachSide(1)->links('partials.pagination') }}
            </div>
        @endif
    </div>
</div>

{{-- ---------------------------------------------------------- mobil suzgecler --}}
<div class="panel panel--left lg:hidden" id="filter-panel" aria-hidden="true" role="dialog" aria-label="{{ __('shop.filters') }}">
    <div class="panel__backdrop"></div>
    <div class="panel__body">
        <div class="flex items-center justify-between px-5 h-16 border-b border-line">
            <span class="font-medium">{{ __('shop.filters') }}</span>
            <button type="button" data-panel-close class="p-2" aria-label="{{ __('shop.close') }}"><x-ui.icon name="close" :size="22" /></button>
        </div>
        <div class="flex-1 overflow-y-auto p-5" id="filter-panel-body"></div>
    </div>
</div>

@push('scripts')
<script>
    // Mobil panelde ayni suzgec formunu yeniden kullan.
    (function () {
        const source = document.getElementById('shop-filters');
        const target = document.getElementById('filter-panel-body');
        if (!source || !target) return;

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-panel-open="filter-panel"]')) return;
            if (target.childElementCount) return;

            const clone = source.cloneNode(true);
            clone.removeAttribute('id');
            clone.classList.remove('hidden');
            clone.classList.add('block', 'space-y-8');
            target.appendChild(clone);
        });
    })();
</script>
@endpush

@endsection
