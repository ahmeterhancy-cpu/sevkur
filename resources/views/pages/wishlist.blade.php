@extends('layouts.app')

@section('title', __('shop.wishlist'))

@section('content')

<x-breadcrumb :items="[__('shop.wishlist') => null]" :title="__('shop.wishlist')" />

<div class="container-x py-10 lg:py-14">
    @if ($products->isEmpty())
        <div class="py-20 text-center">
            <x-ui.icon name="heart" :size="56" class="text-line mx-auto" />
            <p class="mt-4 text-muted">{{ __('shop.wishlist_empty') }}</p>
            <a href="{{ localized_route('shop') }}" class="btn btn-primary mt-6">{{ __('shop.continue_shopping') }}</a>
        </div>
    @else
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-10 lg:gap-x-6">
            @foreach ($products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    @endif
</div>
@endsection
