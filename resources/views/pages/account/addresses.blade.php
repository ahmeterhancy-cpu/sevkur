@extends('layouts.app')

@section('title', __('shop.my_addresses'))

@section('content')

<x-breadcrumb :items="[__('shop.my_account') => localized_route('account.index'), __('shop.my_addresses') => null]" :title="__('shop.my_addresses')" />

<div class="container-x py-10 lg:py-14 grid lg:grid-cols-[16rem_1fr] gap-8 lg:gap-12">
    @include('partials.account-nav')

    <div>
        @if ($addresses->isEmpty())
            <p class="py-10 text-center text-muted border border-line mb-8">{{ __('shop.no_addresses') }}</p>
        @else
            <div class="grid sm:grid-cols-2 gap-4 mb-10">
                @foreach ($addresses as $address)
                    <div class="border border-line p-5 {{ $address->is_default ? 'border-ink' : '' }}">
                        <div class="flex items-center justify-between gap-2">
                            <strong>{{ $address->title ?: $address->city }}</strong>
                            @if ($address->is_default)
                                <span class="badge badge-trend">{{ __('shop.default_address') }}</span>
                            @endif
                        </div>
                        <p class="mt-2 text-[0.9375rem] text-muted">
                            {{ $address->fullName() }}<br>
                            {{ $address->phone }}<br>
                            {{ $address->oneLine() }}
                        </p>
                        <form method="POST" action="{{ localized_route('account.addresses.destroy', ['address' => $address->id]) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-[0.875rem] text-muted hover:text-sale">{{ __('shop.delete') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif

        <h2 class="text-[1.125rem] font-medium mb-4">{{ __('shop.add_address') }}</h2>

        <form method="POST" action="{{ localized_route('account.addresses.store') }}" class="grid sm:grid-cols-2 gap-4 max-w-2xl">
            @csrf
            <div class="sm:col-span-2">
                <label class="field-label" for="title">{{ __('shop.address_title') }}</label>
                <input id="title" type="text" name="title" maxlength="40" class="field" value="{{ old('title') }}">
            </div>
            <div>
                <label class="field-label" for="first_name">{{ __('shop.first_name') }} *</label>
                <input id="first_name" type="text" name="first_name" required class="field" value="{{ old('first_name', auth()->user()->firstName()) }}">
            </div>
            <div>
                <label class="field-label" for="last_name">{{ __('shop.last_name') }} *</label>
                <input id="last_name" type="text" name="last_name" required class="field" value="{{ old('last_name', auth()->user()->lastName()) }}">
            </div>
            <div>
                <label class="field-label" for="phone">{{ __('shop.phone') }} *</label>
                <input id="phone" type="tel" name="phone" required class="field" value="{{ old('phone', auth()->user()->phone) }}">
            </div>
            <div>
                <label class="field-label" for="city">{{ __('shop.city') }} *</label>
                <input id="city" type="text" name="city" required class="field" value="{{ old('city') }}">
            </div>
            <div>
                <label class="field-label" for="district">{{ __('shop.district') }}</label>
                <input id="district" type="text" name="district" class="field" value="{{ old('district') }}">
            </div>
            <div>
                <label class="field-label" for="postcode">{{ __('shop.postcode') }}</label>
                <input id="postcode" type="text" name="postcode" class="field" value="{{ old('postcode') }}">
            </div>
            <div class="sm:col-span-2">
                <label class="field-label" for="address">{{ __('shop.address') }} *</label>
                <textarea id="address" name="address" rows="3" required class="field">{{ old('address') }}</textarea>
            </div>
            <label class="sm:col-span-2 flex items-center gap-2.5 text-[0.9375rem] cursor-pointer">
                <input type="checkbox" name="is_default" value="1" class="w-4 h-4 accent-black">
                {{ __('shop.set_default') }}
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="btn btn-primary">{{ __('shop.save') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
