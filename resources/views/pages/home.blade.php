@extends('layouts.public')

@section('title', 'Indonesian Vanilla')

@section('content')
    <div id="home" data-scroll-section>
    @php($hero = $blocks->get('home.hero'))
    <section class="hero hero-home" aria-labelledby="hero-title">
        <div class="hero-background">
            <x-media-panel :path="$hero?->image_path" fallback="images/canva/home-hero.png" :alt="$hero?->image_alt ?: 'Vanilla flower growing among the vines in Indonesia'" loading="eager" />
        </div>
        <div class="hero-content">
            <h1 id="hero-title">{{ \Illuminate\Support\Str::before($hero?->heading ?: 'Indonesian Soil', ' / ') }}</h1>
            <p class="hero-subtitle">{{ $hero?->body ?: (str_contains($hero?->heading ?: '', ' / ') ? \Illuminate\Support\Str::after($hero->heading, ' / ') : 'World Class Vanilla') }}</p>
            <a class="button button-light" href="#contact">Contact Us</a>
        </div>
        <p class="hero-corner">Indonesia Vanilla</p>
    </section>

    @php($values = $blocks->get('home.values'))
    <section class="values-section" aria-labelledby="values-title">
        <h2 id="values-title">{{ $values?->heading ?: 'What We Deliver' }}</h2>
        <div class="value-grid">
            @foreach(['transparency' => 'Transparency', 'consistency' => 'Consistency', 'quality' => 'Quality'] as $key => $title)
                @php($value = $blocks->get('home.value.' . $key))
                <article class="value-item">
                    <x-media-panel class="value-icon" :path="$value?->image_path" :fallback="'images/canva/' . $key . '-hd.png'" :alt="$value?->image_alt ?: ''" />
                    <div class="value-card">
                        <h3>{{ $value?->heading ?: $title }}</h3>
                        <p>{{ $value?->body ?: match($key) { 'transparency' => 'We trace our products from farm to your hands.', 'consistency' => 'We guarantee consistent results.', 'quality' => 'Naturally cured and hand sorted.' } }}</p>
                    </div>
                </article>
            @endforeach
        </div>
        @if($values?->body)<p class="section-note">{{ $values->body }}</p>@endif
    </section>

    @php($varieties = $blocks->get('home.varieties'))
    <section class="vanilla-section" aria-labelledby="vanilla-title">
        <h2 class="caps-title centered" id="vanilla-title">{{ $varieties?->heading ?: 'Our Vanilla Beans' }}</h2>
        <p class="section-lead centered">{{ $varieties?->body ?: 'Our vanilla beans are handled with attention to quality at every stage to preserve their natural characteristics, including aroma, moisture, and flavor profile. Each pod contains naturally aromatic vanilla seeds that can be used across a wide range of culinary and commercial applications.' }}</p>
        <div class="variety-grid">
            @forelse($featuredProducts as $product)
                <a class="variety-card {{ str_contains(strtolower($product->variety ?: $product->name), 'tahit') ? 'variety-tahitensis' : 'variety-planifolia' }}" href="{{ route('products.show', $product) }}">
                    <x-media-panel :path="$product->image_path" :fallback="'images/canva/' . (str_contains(strtolower($product->variety ?: $product->name), 'tahit') ? 'tahitensis' : 'planifolia') . '.png'" :alt="$product->image_alt ?: $product->name" />
                    <h3>{{ $product->name }}</h3>
                </a>
            @empty
                <a class="variety-card variety-planifolia" href="#products"><x-media-panel fallback="images/canva/planifolia.png" alt="Bundle of Planifolia vanilla beans" /><h3>Planifolia</h3></a>
                <a class="variety-card variety-tahitensis" href="#products"><x-media-panel fallback="images/canva/tahitensis.png" alt="Tahitensis vanilla beans" /><h3>Tahitensis</h3></a>
            @endforelse
        </div>
    </section>

    @php($quality = $blocks->get('home.intro'))
    <section class="quality-section" aria-labelledby="quality-title">
        <div class="quality-background"><x-media-panel :path="$quality?->image_path" fallback="images/canva/quality-background.png" :alt="$quality?->image_alt ?: ''" /></div>
        <h2 id="quality-title">{{ $quality?->eyebrow ?: 'What we stand for' }}</h2>
        <div class="quality-content">
            <div class="quality-copy">
                <h3>{{ $quality?->heading ?: 'What quality means to us?' }}</h3>
                <p>{{ $quality?->body ?: 'Our farmers are family, not suppliers. We grow alongside communities that grow our vanilla. We promise consistent grade, from sample to container.' }}</p>
            </div>
            <img class="quality-symbol" src="{{ asset('images/canva/quality-icon.png') }}" alt="" width="324" height="324" loading="lazy">
        </div>
    </section>
    </div>

    <section id="about" data-scroll-section aria-labelledby="about-title">
        @include('sections.about')
    </section>

    <section id="products" data-scroll-section aria-labelledby="origin-title">
        @include('sections.products')
    </section>

    <section id="blog" data-scroll-section aria-labelledby="blog-title">
        @include('sections.blog')
    </section>
@endsection
