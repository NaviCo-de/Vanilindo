@extends('layouts.public')

@section('title', 'Blog')
@section('page_label', 'Blog')

@section('content')
    @php($hero = $blocks->get('blog.hero'))
    <section class="blog-hero" aria-labelledby="blog-title">
        <div class="page-hero-media"><x-media-panel :path="$hero?->image_path" fallback="images/canva/blog-background.png" :alt="$hero?->image_alt ?: 'Coastal village surrounded by tropical forest in Indonesia'" loading="eager" /></div>
        <div class="blog-hero-copy"><h1 id="blog-title">{{ $hero?->heading ?: 'News and Events' }}</h1><p>{{ $hero?->body ?: 'Catch up with our latest news about vanilla!' }}</p></div>
    </section>
    <section class="section blog-list"><div class="shell">
        @forelse($articles as $article)
            @if($loop->first)<div class="catalog-grid">@endif
            <article class="catalog-card"><a href="{{ route('blog.show', $article) }}"><x-media-panel :path="$article->cover_image_path" fallback="images/canva/home-hero.png" :alt="$article->cover_image_alt ?: $article->title" /><div class="catalog-card-body"><p class="eyebrow">{{ $article->published_at?->format('d M Y') ?: 'Vanilindo Journal' }}</p><h2>{{ $article->title }}</h2><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body), 140) }}</p><span class="text-link">Read article <span aria-hidden="true">↗</span></span></div></a></article>
            @if($loop->last)</div>@endif
        @empty
            <div class="empty-state"><h2>Stories from Indonesian vanilla</h2><p>Our latest news and events will appear here.</p></div>
        @endforelse
        <div class="pagination-wrap">{{ $articles->links() }}</div>
    </div></section>
@endsection
