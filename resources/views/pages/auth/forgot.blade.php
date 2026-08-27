@extends('layouts.app')

@section('title', __('shop.forgot_password'))

@section('content')

<x-breadcrumb :items="[__('shop.forgot_password') => null]" :title="__('shop.forgot_password')" />

<div class="container-x py-10 lg:py-16">
    <div class="max-w-md mx-auto">
        <form method="POST" action="{{ localized_route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required autofocus class="field" value="{{ old('email') }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-full">{{ __('shop.send_reset_link') }}</button>
        </form>

        <p class="mt-6 text-center text-[0.9375rem]">
            <a href="{{ localized_route('login') }}" class="link-underline">{{ __('shop.login') }}</a>
        </p>
    </div>
</div>
@endsection
