    @php($hero = $blocks->get('blog.hero'))
    <section class="blog-hero" aria-labelledby="blog-title">
        <div class="page-hero-media"><x-media-panel :path="$hero?->image_path" fallback="images/canva/blog-background.png" :alt="$hero?->image_alt ?: 'Coastal village surrounded by tropical forest in Indonesia'" loading="eager" /></div>
        <div class="blog-hero-copy"><h2 id="blog-title">{{ $hero?->heading ?: 'News and Events' }}</h2><p>{{ $hero?->body ?: 'Catch up with our latest news about vanilla!' }}</p></div>
    </section>
    <section class="section blog-list" aria-label="Latest blog posts">
        @if($articles->isNotEmpty())
            <div class="blog-carousel" data-blog-carousel data-slide-count="{{ $articles->count() }}" role="region" aria-roledescription="carousel" aria-label="Vanilindo blog">
                <button class="blog-arrow blog-arrow-previous" type="button" data-blog-previous aria-label="Previous blog" aria-controls="blog-slides" disabled><span aria-hidden="true"></span></button>
                <div class="blog-track" id="blog-slides" data-blog-track tabindex="0" aria-label="Blog posts. Use the left and right arrow keys to browse.">
                    @foreach($articles as $article)
                        <article class="blog-slide" data-blog-slide role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}">
                            <a class="blog-card" href="{{ $article->externalLink() ?: route('blog.show', $article) }}" @if($article->externalLink()) rel="external noopener noreferrer" @endif>
                                <x-media-panel :path="$article->cover_image_path" fallback="images/canva/blog-background.png" :alt="$article->cover_image_alt ?: $article->title" />
                                <div class="blog-card-copy">
                                    <h3>{{ $article->title }}</h3>
                                    <p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($article->body ?: ''), 180) }}</p>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
                <button class="blog-arrow blog-arrow-next" type="button" data-blog-next aria-label="Next blog" aria-controls="blog-slides"><span aria-hidden="true"></span></button>
                <p class="sr-only" data-blog-status aria-live="polite" aria-atomic="true">1 of {{ $articles->count() }}</p>
            </div>
        @else
            <div class="empty-state"><h2>Stories from Indonesian vanilla</h2><p>Our latest news and events will appear here.</p></div>
        @endif
    </section>
