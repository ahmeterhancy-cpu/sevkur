@extends('layouts.app')

@section('title', __('shop.new_password'))

@section('content')

<x-breadcrumb :items="[__('shop.new_password') => null]" :title="__('shop.new_password')" />

<div class="container-x py-10 lg:py-16">
    <div class="max-w-md mx-auto">
        <form method="POST" action="{{ localized_route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required class="field" value="{{ old('email', $email) }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password">{{ __('shop.new_password') }} *</label>
                <input id="password" type="password" name="password" required class="field">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password_confirmation">{{ __('shop.confirm_password') }} *</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required class="field">
            </div>
            <button type="submit" class="btn btn-primary w-full">{{ __('shop.save') }}</button>
        </form>
    </div>
</div>
@endsection
