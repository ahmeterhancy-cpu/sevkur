@extends('layouts.app')

@section('title', $post->t('title'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->t('excerpt')), 155))
@section('og_type', 'article')
@section('og_image', media_url($post->cover))

@section('content')

<x-breadcrumb :items="[__('shop.blog') => localized_route('blog'), $post->t('title') => null]" />

<article class="container-x py-10 lg:py-14">
    <div class="max-w-3xl mx-auto">
        <div class="flex flex-wrap items-center gap-3 text-[0.75rem] uppercase tracking-widest text-muted">
            @if ($post->category)
                <span class="text-brand">{{ $post->category->t('name') }}</span>
            @endif
            @if ($post->published_at)
                <span>{{ $post->published_at->translatedFormat('d M Y') }}</span>
            @endif
            @if ($post->author)
                <span>{{ __('shop.by_author') }}: {{ $post->author->name }}</span>
            @endif
        </div>

        <h1 class="mt-3 text-[2rem] lg:text-[2.5rem] font-medium leading-tight">{{ $post->t('title') }}</h1>

        @if ($post->cover)
            <img src="{{ media_url($post->cover) }}" alt="{{ $post->t('title') }}" class="w-full mt-8" fetchpriority="high">
        @endif

        <div class="prose-shop mt-8">
            {!! nl2br(e($post->t('body'))) !!}
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-16 pt-12 border-t border-line">
            <h2 class="section-title text-center mb-10">{{ __('shop.related_posts') }}</h2>
            <div class="grid md:grid-cols-3 gap-8">
                @foreach ($related as $item)
                    <article class="group">
                        <a href="{{ localized_route('blog.show', ['post' => $item->slug]) }}" class="block aspect-[4/3] bg-cream overflow-hidden">
                            <img src="{{ media_url($item->cover) }}" alt="{{ $item->t('title') }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                        </a>
                        <h3 class="mt-3 text-[1.0625rem] font-medium leading-snug">
                            <a href="{{ localized_route('blog.show', ['post' => $item->slug]) }}" class="link-underline">{{ $item->t('title') }}</a>
                        </h3>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</article>
@endsection
