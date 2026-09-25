@extends('layouts.public')

@section('title', 'Blog')

@section('content')
    <div class="page-label"><div class="shell">Blog</div></div>
    <section class="section blog-intro"><div class="shell narrow centered"><p class="eyebrow">News and Events</p><h1 class="display-title">{{ $blocks->get('blog.hero')?->heading ?: 'Stories & Insights' }}</h1><p class="section-lead">{{ $blocks->get('blog.hero')?->body ?: 'Catch up with our latest news about vanilla.' }}</p></div></section>
    <section class="section blog-list"><div class="shell">
        @forelse($articles as $article)
            @if($loop->first)<div class="catalog-grid">@endif
            <article class="catalog-card"><a href="{{ route('blog.show', $article) }}"><x-media-panel :path="$article->cover_image_path" :alt="$article->cover_image_alt ?: $article->title" :label="$article->title . ' cover photo pending'" /><div class="catalog-card-body"><p class="eyebrow">{{ $article->published_at?->format('d M Y') ?: 'Vanilindo Journal' }}</p><h2>{{ $article->title }}</h2><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 140) }}</p><span class="text-link">Read article <span aria-hidden="true">↗</span></span></div></a></article>
            @if($loop->last)</div>@endif
        @empty
            <div class="empty-state"><p>[Temporary] Our first stories are on their way.</p></div>
        @endforelse
        <div class="pagination-wrap">{{ $articles->links() }}</div>
    </div></section>
@endsection
