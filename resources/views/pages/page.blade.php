@extends('layouts.app')

@section('title', $page->t('title'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->t('body')), 155))

@section('content')

<x-breadcrumb :items="[$page->t('title') => null]" :title="$page->t('title')" />

<div class="container-x py-10 lg:py-14">
    <div class="max-w-3xl mx-auto prose-shop">
        {!! nl2br(e($page->t('body'))) !!}
    </div>
</div>
@endsection
