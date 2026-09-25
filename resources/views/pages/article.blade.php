@extends('layouts.public')

@section('title', $article->seo_title ?: $article->title)
@section('meta_description', $article->seo_description ?: $article->excerpt ?: 'Vanilindo stories and insights about Indonesian vanilla.')

@section('content')
    <div class="page-label"><div class="shell"><a href="{{ route('blog.index') }}">Blog</a> <span aria-hidden="true">/</span> Article</div></div>
    <article class="article-page"><header class="shell narrow centered"><p class="eyebrow">{{ $article->published_at?->format('d F Y') ?: 'Vanilindo Journal' }}</p><h1 class="display-title">{{ $article->title }}</h1>@if($article->excerpt)<p class="section-lead">{{ $article->excerpt }}</p>@endif</header>@if($article->cover_image_path)<div class="shell article-cover"><x-media-panel :path="$article->cover_image_path" :alt="$article->cover_image_alt ?: $article->title" /></div>@endif<div class="shell article-body prose">{!! $articleHtml !!}</div><div class="shell narrow"><a class="text-link" href="{{ route('blog.index') }}">← Back to all stories</a></div></article>
@endsection
