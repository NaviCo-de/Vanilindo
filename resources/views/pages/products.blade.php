@extends('layouts.public')

@section('title', 'Our Products')
@section('page_label', 'Our Products')

@section('content')
    @php($origin = $blocks->get('products.origin'))
    <section class="products-origin" aria-labelledby="origin-title">
        <div class="origin-art">
            <div class="map-panel">
                <img class="indonesia-map" src="{{ asset('images/canva/indonesia-map.png') }}" alt="Map showing the vanilla origin in Papua, Indonesia" width="800" height="301" loading="eager">
                <span class="papua-label">Papua Islands</span>
                <span class="papua-pointer" aria-hidden="true"></span>
            </div>
            <x-media-panel class="papua-photo" :path="$origin?->image_path" fallback="images/canva/papua-community.jpg" :alt="$origin?->image_alt ?: 'Local community in Papua, Indonesia'" />
        </div>
        <div class="origin-copy">
            <h1 id="origin-title">{{ $origin?->heading ?: 'Where our beans originate from' }}</h1>
            @foreach(preg_split('/\n\s*\n/', $origin?->body ?: 'Our vanilla beans originate from Papua. Our vanilla offers a distinctive aroma, rich flavor profile, and natural quality suitable for a wide range of culinary and commercial applications.') as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </section>

    @php($varieties = $blocks->get('products.varieties'))
    <section class="product-intro" aria-labelledby="range-title">
        <div class="range-copy">
            <h2 id="range-title">{{ $varieties?->heading ?: 'Planifolia & Tahitensis' }}</h2>
            <p>{{ $varieties?->body ?: 'We provide our clients with Planifolia and Tahitensis vanilla beans in various grades. Whether it’s bean length, moisture level, or other specifications, we’ve got you covered!' }}</p>
        </div>
        <x-media-panel class="range-photo" :path="$varieties?->image_path" fallback="images/canva/vanilla-bowl.png" :alt="$varieties?->image_alt ?: 'Vanilla beans and seeds in a wooden bowl'" />
    </section>

    @if($products->isNotEmpty())
        <section class="section catalog-section" aria-labelledby="catalog-title"><div class="shell">
            <div class="catalog-heading"><h2 class="caps-title" id="catalog-title">Explore Our Products</h2></div>
            <div class="catalog-grid">
                @foreach($products as $product)
                    <article class="catalog-card"><a href="{{ route('products.show', $product) }}"><x-media-panel :path="$product->image_path" :fallback="'images/canva/' . (str_contains(strtolower($product->variety ?: $product->name), 'tahit') ? 'tahitensis' : 'planifolia') . '.png'" :alt="$product->image_alt ?: $product->name" /><div class="catalog-card-body"><p class="eyebrow">{{ $product->variety ?: 'Vanilla Beans' }}</p><h3>{{ $product->name }}</h3>@if($product->summary)<p>{{ $product->summary }}</p>@endif<span class="text-link">View details <span aria-hidden="true">↗</span></span></div></a></article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $products->links() }}</div>
        </div></section>
    @endif
@endsection
