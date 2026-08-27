@extends('layouts.app')

@section('title', __('shop.login'))

@section('content')

<x-breadcrumb :items="[__('shop.login') => null]" :title="__('shop.login')" />

<div class="container-x py-10 lg:py-16">
    <div class="max-w-md mx-auto">
        <form method="POST" action="{{ localized_route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required autofocus class="field" value="{{ old('email') }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password">{{ __('shop.password') }} *</label>
                <input id="password" type="password" name="password" required class="field">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-[0.9375rem] cursor-pointer">
                    <input type="checkbox" name="remember" value="1" class="w-4 h-4 accent-black">
                    {{ __('shop.remember_me') }}
                </label>
                <a href="{{ localized_route('password.request') }}" class="text-[0.9375rem] link-underline">{{ __('shop.forgot_password') }}</a>
            </div>

            <button type="submit" class="btn btn-primary w-full">{{ __('shop.login') }}</button>
        </form>

        <p class="mt-6 text-center text-[0.9375rem] text-muted">
            {{ __('shop.no_account') }}
            <a href="{{ localized_route('register') }}" class="text-ink link-underline">{{ __('shop.register') }}</a>
        </p>
    </div>
</div>
@endsection
