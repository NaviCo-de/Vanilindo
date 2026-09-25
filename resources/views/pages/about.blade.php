@extends('layouts.public')

@section('title', 'About Us')

@section('content')
    <div class="page-label"><div class="shell">About Us</div></div>
    @php($profile = $blocks->get('about.profile'))
    <section class="page-hero about-hero" aria-labelledby="about-title">
        <div class="page-hero-media"><x-media-panel :path="$profile?->image_path" :alt="$profile?->image_alt ?: ''" label="Vanilla curing photo pending" /></div>
        <div class="page-hero-shade"></div>
        <div class="shell page-hero-content">
            <p class="eyebrow light">The Story Behind Vanilindo</p>
            <h1 id="about-title">{{ $profile?->heading ?: 'About Us' }}</h1>
            @foreach(preg_split('/\n\s*\n/', $profile?->body ?: 'Vanilindo is a brand under ANCA Organics, focusing on supplying the global market with premium quality vanilla. Our vanilla beans are sourced from Papua, in eastern Indonesia.') as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>

    @php($goal = $blocks->get('about.goal'))
    <section class="section goal-section"><div class="shell narrow centered"><p class="eyebrow">Our Purpose</p><h2 class="caps-title">{{ $goal?->heading ?: 'Our Goal' }}</h2><p class="section-lead">{{ $goal?->body ?: 'Our mission is to connect global businesses with reliable access to high-quality Indonesian vanilla while creating opportunities for local farmers.' }}</p></div></section>

    @php($origin = $blocks->get('about.origin'))
    <section class="section origin-section"><div class="shell split-grid"><div><p class="eyebrow">From Indonesia</p><h2 class="display-title">{{ $origin?->heading ?: 'A connection to our roots.' }}</h2><p>{{ $origin?->body ?: '[Temporary] More about our farming partners and origins will be added here.' }}</p><a class="text-link" href="{{ route('products.index') }}">Explore our vanilla <span aria-hidden="true">↗</span></a></div><x-media-panel :path="$origin?->image_path" :alt="$origin?->image_alt ?: ''" label="Papua origin photo pending" /></div></section>
@endsection
