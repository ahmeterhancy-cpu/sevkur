@extends('layouts.app')

@section('title', __('shop.page_not_found'))

@section('content')
<div class="container-x py-24 text-center">
    <span class="text-[5rem] font-medium leading-none text-brand">404</span>
    <h1 class="text-[1.75rem] font-medium mt-3">{{ __('shop.page_not_found') }}</h1>
    <p class="text-muted mt-2">{{ __('shop.page_not_found_text') }}</p>
    <a href="{{ localized_route('home') }}" class="btn btn-primary mt-7">{{ __('shop.go_home') }}</a>
</div>
@endsection
