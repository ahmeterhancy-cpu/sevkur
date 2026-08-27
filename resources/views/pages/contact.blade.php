@extends('layouts.app')

@section('title', __('shop.contact_title'))

@section('content')

<x-breadcrumb :items="[__('shop.contact') => null]" :title="__('shop.contact_title')" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[1fr_20rem] gap-10 lg:gap-16">
    <form method="POST" action="{{ localized_route('contact.send') }}" class="space-y-4 max-w-2xl">
        @csrf
        {{-- Bal kupu: gercek kullanicilar doldurmaz --}}
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label" for="name">{{ __('shop.contact_name') }} *</label>
                <input id="name" type="text" name="name" required maxlength="80" class="field" value="{{ old('name', auth()->user()->name ?? '') }}">
            </div>
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required maxlength="120" class="field" value="{{ old('email', auth()->user()->email ?? '') }}">
            </div>
            <div>
                <label class="field-label" for="phone">{{ __('shop.phone') }}</label>
                <input id="phone" type="tel" name="phone" maxlength="30" class="field" value="{{ old('phone') }}">
            </div>
            <div>
                <label class="field-label" for="subject">{{ __('shop.contact_subject') }}</label>
                <input id="subject" type="text" name="subject" maxlength="120" class="field" value="{{ old('subject') }}">
            </div>
        </div>

        <div>
            <label class="field-label" for="message">{{ __('shop.contact_message') }} *</label>
            <textarea id="message" name="message" rows="6" required minlength="10" maxlength="2000" class="field">{{ old('message') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('shop.contact_send') }}</button>
    </form>

    <aside class="space-y-6">
        @if (setting('address'))
            <div class="flex gap-3">
                <x-ui.icon name="pin" :size="20" class="text-brand shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-medium">{{ __('shop.address_label') }}</h3>
                    <p class="text-muted text-[0.9375rem]">{{ setting('address') }}</p>
                </div>
            </div>
        @endif
        @if (setting('phone'))
            <div class="flex gap-3">
                <x-ui.icon name="phone" :size="20" class="text-brand shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-medium">{{ __('shop.phone') }}</h3>
                    <a href="tel:{{ preg_replace('/\s+/', '', setting('phone')) }}" class="text-muted text-[0.9375rem] hover:text-ink">{{ setting('phone') }}</a>
                </div>
            </div>
        @endif
        @if (setting('email'))
            <div class="flex gap-3">
                <x-ui.icon name="mail" :size="20" class="text-brand shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-medium">{{ __('shop.email') }}</h3>
                    <a href="mailto:{{ setting('email') }}" class="text-muted text-[0.9375rem] hover:text-ink">{{ setting('email') }}</a>
                </div>
            </div>
        @endif
        @if (setting_t('working_hours'))
            <div class="flex gap-3">
                <x-ui.icon name="clock" :size="20" class="text-brand shrink-0 mt-0.5" />
                <div>
                    <h3 class="font-medium">{{ __('shop.working_hours') }}</h3>
                    <p class="text-muted text-[0.9375rem]">{{ setting_t('working_hours') }}</p>
                </div>
            </div>
        @endif
    </aside>
</div>

@if (setting('map_embed'))
    <div class="w-full h-[26rem]">
        {!! setting('map_embed') !!}
    </div>
@endif

@endsection
