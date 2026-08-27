@extends('layouts.app')

@section('title', __('shop.register'))

@section('content')

<x-breadcrumb :items="[__('shop.register') => null]" :title="__('shop.register')" />

<div class="container-x py-10 lg:py-16">
    <div class="max-w-md mx-auto">
        <form method="POST" action="{{ localized_route('register.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="field-label" for="name">{{ __('shop.contact_name') }} *</label>
                <input id="name" type="text" name="name" required autofocus class="field" value="{{ old('name') }}">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required class="field" value="{{ old('email') }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="phone">{{ __('shop.phone') }}</label>
                <input id="phone" type="tel" name="phone" class="field" value="{{ old('phone') }}">
            </div>
            <div>
                <label class="field-label" for="password">{{ __('shop.password') }} *</label>
                <input id="password" type="password" name="password" required class="field">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password_confirmation">{{ __('shop.confirm_password') }} *</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="field">
            </div>

            <label class="flex items-start gap-2.5 text-[0.875rem] cursor-pointer">
                <input type="checkbox" name="terms" value="1" required class="mt-0.5 w-4 h-4 accent-black">
                <span>{{ __('shop.terms_register') }}</span>
            </label>

            <label class="flex items-start gap-2.5 text-[0.875rem] cursor-pointer">
                <input type="checkbox" name="accepts_marketing" value="1" class="mt-0.5 w-4 h-4 accent-black">
                <span>{{ __('shop.accepts_marketing') }}</span>
            </label>

            <button type="submit" class="btn btn-primary w-full">{{ __('shop.register') }}</button>
        </form>

        <p class="mt-6 text-center text-[0.9375rem] text-muted">
            {{ __('shop.have_account') }}
            <a href="{{ localized_route('login') }}" class="text-ink link-underline">{{ __('shop.login') }}</a>
        </p>
    </div>
</div>
@endsection
