@extends('layouts.public')

@section('title', 'Indonesian Vanilla')

@section('content')
    @php($hero = $blocks->get('home.hero'))
    <section class="hero hero-home" aria-labelledby="hero-title">
        <div class="hero-background">
            <x-media-panel :path="$hero?->image_path" :alt="$hero?->image_alt ?: ''" label="Vanilla flower and growing vines photo pending" />
        </div>
        <div class="hero-shade"></div>
        <div class="shell hero-content">
            <p class="hero-kicker">{{ $hero?->eyebrow ?: 'From the Islands of Indonesia' }}</p>
            <h1 id="hero-title">{{ \Illuminate\Support\Str::before($hero?->heading ?: 'Indonesian Soil', ' / ') }}</h1>
            @if($hero?->body || str_contains($hero?->heading ?: '', ' / '))
                <p class="hero-subtitle">{{ $hero?->body ?: \Illuminate\Support\Str::after($hero->heading, ' / ') }}</p>
            @endif
            <a class="button button-light" href="{{ route('contact') }}">Contact Us <span aria-hidden="true">↗</span></a>
        </div>
        <p class="hero-corner">Indonesia Vanilla</p>
    </section>

    @php($values = $blocks->get('home.values'))
    <section class="section section-cream contour-light values-section" aria-labelledby="values-title">
        <div class="shell">
            <p class="eyebrow">{{ $values?->eyebrow ?: 'Vanilindo' }}</p>
            <h2 class="display-title centered" id="values-title">{{ $values?->heading ?: 'What We Deliver' }}</h2>
            <div class="value-grid">
                <article class="value-card"><div class="value-icon" aria-hidden="true">◎</div><h3>{{ $blocks->get('home.value.transparency')?->heading ?: 'Transparency' }}</h3><p>{{ $blocks->get('home.value.transparency')?->body ?: 'We trace our products from farm to your hands.' }}</p></article>
                <article class="value-card"><div class="value-icon" aria-hidden="true">↻</div><h3>{{ $blocks->get('home.value.consistency')?->heading ?: 'Consistency' }}</h3><p>{{ $blocks->get('home.value.consistency')?->body ?: 'We guarantee consistent results.' }}</p></article>
                <article class="value-card"><div class="value-icon" aria-hidden="true">✧</div><h3>{{ $blocks->get('home.value.quality')?->heading ?: 'Quality' }}</h3><p>{{ $blocks->get('home.value.quality')?->body ?: 'Naturally cured and hand sorted.' }}</p></article>
            </div>
            @if($values?->body)<p class="section-note">{{ $values->body }}</p>@endif
        </div>
    </section>

    @php($varieties = $blocks->get('home.varieties'))
    <section class="section vanilla-section" aria-labelledby="vanilla-title">
        <div class="shell">
            <p class="eyebrow">{{ $varieties?->eyebrow ?: 'The Collection' }}</p>
            <h2 class="caps-title centered" id="vanilla-title">{{ $varieties?->heading ?: 'Our Vanilla Beans' }}</h2>
            <p class="section-lead centered">{{ $varieties?->body ?: 'Our vanilla beans are handled with attention to quality at every stage to preserve their natural characteristics, including aroma, moisture, and flavor profile.' }}</p>
            <div class="variety-grid">
                @forelse($featuredProducts as $product)
                    <a class="variety-card" href="{{ route('products.show', $product) }}">
                        <x-media-panel :path="$product->image_path" :alt="$product->image_alt ?: $product->name" :label="$product->name . ' photo pending'" />
                        <h3>{{ $product->name }}</h3>
                        <span>Explore product <span aria-hidden="true">↗</span></span>
                    </a>
                @empty
                    <div class="variety-card"><x-media-panel label="Planifolia product photo pending" /><h3>Planifolia</h3><span class="temporary">[Temporary] Product details coming soon.</span></div>
                    <div class="variety-card"><x-media-panel label="Tahitensis product photo pending" /><h3>Tahitensis</h3><span class="temporary">[Temporary] Product details coming soon.</span></div>
                @endforelse
            </div>
            <div class="centered"><a class="text-link" href="{{ route('products.index') }}">Discover our products <span aria-hidden="true">↗</span></a></div>
        </div>
    </section>

    @php($quality = $blocks->get('home.intro'))
    <section class="section editorial-section" aria-labelledby="editorial-title">
        <div class="shell editorial-grid">
            <div>
                <p class="eyebrow">{{ $quality?->eyebrow ?: 'What We Stand For' }}</p>
                <h2 class="display-title" id="editorial-title">{{ $quality?->heading ?: 'Rooted in quality, grown together.' }}</h2>
                <p>{{ $quality?->body ?: 'Our farmers are family, not suppliers. We grow alongside communities that grow our vanilla. We promise consistent grade, from sample to container.' }}</p>
                <a class="text-link text-link-light" href="{{ route('about') }}">Get to know us <span aria-hidden="true">↗</span></a>
            </div>
            <div class="editorial-symbol" aria-hidden="true">✳</div>
        </div>
    </section>

    @php($cta = $blocks->get('home.cta'))
    <section class="section cta-section">
        <div class="shell cta-grid">
            <div><p class="eyebrow">{{ $cta?->eyebrow ?: 'Connect With Us' }}</p><h2 class="display-title">{{ $cta?->heading ?: 'Let’s talk vanilla.' }}</h2><p>{{ $cta?->body ?: '[Temporary] Contact our team to discuss your vanilla requirements.' }}</p></div>
            <a class="button button-olive" href="{{ route('contact') }}">Contact Us <span aria-hidden="true">↗</span></a>
        </div>
    </section>
@endsection
