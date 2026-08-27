<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ config('shop.locales.'.app()->getLocale().'.dir', 'ltr') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', setting('site_title', __('shop.brand'))) — {{ setting('site_name', __('shop.brand')) }}</title>
    <meta name="description" content="@yield('meta_description', setting('site_description', __('shop.footer_about')))">

    {{-- Dil alternatifleri --}}
    @foreach (\App\Support\Locales::all() as $altLocale)
        <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ locale_switch_url($altLocale) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ locale_switch_url(\App\Support\Locales::default()) }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:site_name" content="{{ setting('site_name', __('shop.brand')) }}">
    <meta property="og:title" content="@yield('title', setting('site_title', __('shop.brand')))">
    <meta property="og:description" content="@yield('meta_description', setting('site_description', __('shop.footer_about')))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <link rel="icon" href="{{ setting('favicon') ? media_url(setting('favicon')) : asset('images/favicon.svg') }}" type="image/svg+xml">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body data-page="@yield('page_key', 'default')" class="antialiased">

@include('partials.announcement-bar')
@include('partials.header')

<main id="content">
    @yield('content')
</main>

@include('partials.footer')
@include('partials.panels')

{{-- Sunucu mesajlari toast olarak gosterilir --}}
@if (session('status'))
    <div data-flash="{{ session('status') }}" data-flash-type="success" hidden></div>
@endif
@if (session('error'))
    <div data-flash="{{ session('error') }}" data-flash-type="error" hidden></div>
@endif
@if ($errors->any())
    <div data-flash="{{ $errors->first() }}" data-flash-type="error" hidden></div>
@endif

@stack('scripts')
</body>
</html>
