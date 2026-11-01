@extends('layouts.public', ['articleOnly' => true])

@section('title', $article->seo_title ?: $article->title)
@section('meta_description', $article->seo_description ?: $article->summary() ?: 'Vanilindo stories and insights about Indonesian vanilla.')

@section('content')
    <nav class="journal-backbar" aria-label="Article navigation">
        <div class="journal-shell">
            <a class="journal-back" href="{{ route('home') }}#blog">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m10 5-7 7 7 7M3 12h18" /></svg>
                <span>Back to all stories</span>
            </a>
        </div>
    </nav>
    <article class="journal-page">
        <header class="journal-hero">
            <div class="journal-shell">
                <div class="journal-title"><h1>{{ $article->title }}</h1></div>
                <div class="journal-heading-grid">
                    <div class="journal-intro">
                        @if($article->excerpt)<p class="journal-summary">{{ $article->excerpt }}</p>@endif
                        <div class="journal-meta">
                            <span>Vanilindo</span><span aria-hidden="true">·</span>
                            <time datetime="{{ ($article->published_at ?: $article->created_at)->toDateString() }}">{{ ($article->published_at ?: $article->created_at)->timezone('Asia/Jakarta')->format('d F Y') }}</time>
                        </div>
                    </div>
                    <div class="journal-cover">
                        <x-media-panel :path="$article->cover_image_path" fallback="images/canva/papua-community.jpg" :alt="$article->cover_image_alt ?: $article->title" loading="eager" />
                    </div>
                </div>
            </div>
        </header>

        @if(filled($article->body))
            <div class="journal-shell journal-reading-layout">
                <div class="journal-body prose">{!! $articleHtml !!}</div>
            </div>
        @endif
    </article>
@endsection
