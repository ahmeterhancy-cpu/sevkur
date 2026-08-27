@extends('layouts.app')

@section('title', __('shop.blog'))

@section('content')

<x-breadcrumb :items="[__('shop.blog') => null]" :title="__('shop.blog')" />

<div class="container-x py-10 lg:py-14">
    @if ($categories->isNotEmpty())
        <div class="flex flex-wrap gap-2 mb-10">
            <a href="{{ localized_route('blog') }}" class="btn btn-sm {{ $activeCategory ? 'btn-ghost' : 'btn-primary' }}">
                {{ __('shop.view_all') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ localized_route('blog', ['kategori' => $category->slug]) }}"
                   class="btn btn-sm {{ $activeCategory === $category->slug ? 'btn-primary' : 'btn-ghost' }}">
                    {{ $category->t('name') }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($posts->isEmpty())
        <p class="py-20 text-center text-muted">{{ __('shop.no_posts') }}</p>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach ($posts as $post)
                <article class="group reveal">
                    <a href="{{ localized_route('blog.show', ['post' => $post->slug]) }}" class="block aspect-[4/3] bg-cream overflow-hidden">
                        <img src="{{ media_url($post->cover) }}" alt="{{ $post->t('title') }}"
                             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                    </a>
                    <div class="mt-4 flex items-center gap-3 text-[0.75rem] uppercase tracking-widest text-muted">
                        @if ($post->category)
                            <span class="text-brand">{{ $post->category->t('name') }}</span>
                        @endif
                        @if ($post->published_at)
                            <span>{{ $post->published_at->translatedFormat('d M Y') }}</span>
                        @endif
                    </div>
                    <h2 class="mt-2 text-[1.125rem] font-medium leading-snug">
                        <a href="{{ localized_route('blog.show', ['post' => $post->slug]) }}" class="link-underline">{{ $post->t('title') }}</a>
                    </h2>
                    <p class="mt-2 text-[0.9375rem] text-muted line-clamp-3">{{ $post->t('excerpt') }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-12">{{ $posts->links('partials.pagination') }}</div>
    @endif
</div>
@endsection
