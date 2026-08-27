@php
    $links = [
        ['route' => 'account.index', 'label' => __('shop.dashboard'), 'icon' => 'grid'],
        ['route' => 'account.orders', 'label' => __('shop.my_orders'), 'icon' => 'package'],
        ['route' => 'account.addresses', 'label' => __('shop.my_addresses'), 'icon' => 'pin'],
        ['route' => 'account.profile', 'label' => __('shop.profile'), 'icon' => 'user'],
    ];
    $currentName = request()->route()?->getName();
@endphp

<nav class="space-y-1">
    @foreach ($links as $link)
        @php($isActive = $currentName === app()->getLocale().'.'.$link['route'])
        <a href="{{ localized_route($link['route']) }}"
           class="flex items-center gap-3 px-4 py-3 border border-line {{ $isActive ? 'bg-ink text-white border-ink' : 'hover:border-ink' }}"
           @if($isActive) style="color:#ffffff" @endif>
            <x-ui.icon :name="$link['icon']" :size="18" />
            <span class="text-[0.9375rem]">{{ $link['label'] }}</span>
        </a>
    @endforeach

    <form method="POST" action="{{ localized_route('logout') }}">
        @csrf
        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 border border-line hover:border-sale hover:text-sale">
            <x-ui.icon name="arrow-right" :size="18" />
            <span class="text-[0.9375rem]">{{ __('shop.logout') }}</span>
        </button>
    </form>
</nav>
