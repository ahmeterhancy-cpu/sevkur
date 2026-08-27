@extends('layouts.app')

@section('title', __('shop.brands'))

@section('content')

<x-breadcrumb :items="[__('shop.brands') => null]" :title="__('shop.brands')" />

<div class="container-x py-10 lg:py-14">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
        @foreach ($brands as $brand)
            <a href="{{ localized_route('shop', ['marka' => [$brand->slug]]) }}"
               class="border border-line p-6 text-center hover:border-ink transition-colors reveal">
                @if ($brand->logo)
                    <img src="{{ media_url($brand->logo) }}" alt="{{ $brand->t('name') }}" class="h-12 w-auto mx-auto" loading="lazy">
                @else
                    <span class="block text-[1.125rem] font-medium">{{ $brand->t('name') }}</span>
                @endif
                <span class="block mt-2 text-[0.875rem] text-muted">{{ $brand->products_count }} {{ __('shop.product') }}</span>
            </a>
        @endforeach
    </div>
</div>
@endsection
