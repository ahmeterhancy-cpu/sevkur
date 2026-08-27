@extends('layouts.app')

@section('title', __('shop.profile'))

@section('content')

<x-breadcrumb :items="[__('shop.my_account') => localized_route('account.index'), __('shop.profile') => null]" :title="__('shop.profile')" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[16rem_1fr] gap-8 lg:gap-12">
    @include('partials.account-nav')

    <div>
        <form method="POST" action="{{ localized_route('account.profile.update') }}" class="grid sm:grid-cols-2 gap-4 max-w-2xl">
            @csrf
            @method('PUT')

            <div>
                <label class="field-label" for="name">{{ __('shop.contact_name') }} *</label>
                <input id="name" type="text" name="name" required class="field" value="{{ old('name', auth()->user()->name) }}">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="email">{{ __('shop.email') }} *</label>
                <input id="email" type="email" name="email" required class="field" value="{{ old('email', auth()->user()->email) }}">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="phone">{{ __('shop.phone') }}</label>
                <input id="phone" type="tel" name="phone" class="field" value="{{ old('phone', auth()->user()->phone) }}">
            </div>

            <label class="sm:col-span-2 flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                <input type="checkbox" name="accepts_marketing" value="1" class="w-4 h-4 accent-black" @checked(auth()->user()->accepts_marketing)>
                {{ __('shop.accepts_marketing') }}
            </label>

            <div class="sm:col-span-2 pt-4 border-t border-line">
                <h2 class="text-[1.0625rem] font-medium mb-3">{{ __('shop.password') }}</h2>
            </div>

            <div class="sm:col-span-2">
                <label class="field-label" for="current_password">{{ __('shop.current_password') }}</label>
                <input id="current_password" type="password" name="current_password" class="field" autocomplete="current-password">
                @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password">{{ __('shop.new_password') }}</label>
                <input id="password" type="password" name="password" class="field" autocomplete="new-password">
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="field-label" for="password_confirmation">{{ __('shop.confirm_password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="field" autocomplete="new-password">
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary">{{ __('shop.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
