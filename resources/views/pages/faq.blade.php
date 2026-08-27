@extends('layouts.app')

@section('title', __('shop.faq'))

@push('head')
@php
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [],
    ];
    foreach ($faqs as $group) {
        foreach ($group as $faq) {
            $faqSchema['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq->t('question'),
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->t('answer')],
            ];
        }
    }
@endphp
<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<x-breadcrumb :items="[__('shop.faq') => null]" :title="__('shop.faq')" />

<div class="container-x py-10 lg:py-14">
    <div class="max-w-3xl mx-auto space-y-10">
        @forelse ($faqs as $group => $items)
            <section>
                <div data-accordion class="border-t border-line">
                    @foreach ($items as $faq)
                        <div data-accordion-item class="border-b border-line">
                            <button type="button" data-accordion-trigger aria-expanded="false"
                                    class="w-full flex items-center justify-between gap-4 py-5 text-left">
                                <span class="font-medium text-[1.0625rem]">{{ $faq->t('question') }}</span>
                                <span class="accordion-icon shrink-0"><x-ui.icon name="plus" :size="18" /></span>
                            </button>
                            <div class="accordion-body">
                                <div>
                                    <p class="pb-5 text-ink-soft">{{ $faq->t('answer') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-center text-muted py-16">—</p>
        @endforelse
    </div>
</div>
@endsection
