@extends('layouts.app')

@section('title', $page->t('title'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags(legal_fill($page->t('body'))), 155))

@section('content')

<x-breadcrumb :items="[$page->t('title') => null]" :title="$page->t('title')" />

<div class="container-x py-10 lg:py-14">
    <div class="max-w-3xl mx-auto prose-shop">
        {{-- Yasal metinlerdeki [[unvan]], [[mersis]] gibi isaretler magaza
             ayarlarindan doldurulur; diger sayfalarda isaret olmadigi icin
             metin oldugu gibi gecer. --}}
        {!! nl2br(e(legal_fill($page->t('body')))) !!}
    </div>
</div>
@endsection
