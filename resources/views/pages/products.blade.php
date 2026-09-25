@extends('layouts.public')

@section('title', 'Our Products')

@section('content')
    <div class="page-label"><div class="shell">Our Products</div></div>
    @php($origin = $blocks->get('products.origin'))
    <section class="section products-origin" aria-labelledby="origin-title">
        <div class="shell split-grid">
            <div class="map-panel" aria-label="Vanilla origin: Papua, Indonesia"><span class="map-line" aria-hidden="true"></span><span class="map-dot" aria-hidden="true"></span><strong>Papua, Indonesia</strong><small>Where our beans begin</small></div>
            <div><p class="eyebrow">The Source</p><h1 class="display-title" id="origin-title">{{ $origin?->heading ?: 'Where our beans originate from' }}</h1><p>{{ $origin?->body ?: 'Our vanilla beans originate from Papua. They offer a distinctive aroma and rich flavor profile, suitable for a wide range of culinary and commercial applications.' }}</p></div>
        </div>
    </section>

    @php($varieties = $blocks->get('products.varieties'))
    <section class="section product-intro"><div class="shell split-grid"><div><p class="eyebrow">Our Range</p><h2 class="display-title">{{ $varieties?->heading ?: 'Planifolia & Tahitensis' }}</h2><p>{{ $varieties?->body ?: 'We provide Planifolia and Tahitensis vanilla beans in various grades. Ask us about bean length, moisture level, and other specifications.' }}</p></div><div class="abstract-pods" aria-hidden="true"><i></i><i></i><i></i><i></i></div></div></section>

    <section class="section catalog-section" aria-labelledby="catalog-title"><div class="shell"><div class="catalog-heading"><div><p class="eyebrow">Vanilindo Selection</p><h2 class="caps-title" id="catalog-title">Explore Our Products</h2></div><span>{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</span></div>
        @forelse($products as $product)
            @if($loop->first)<div class="catalog-grid">@endif
            <article class="catalog-card"><a href="{{ route('products.show', $product) }}"><x-media-panel :path="$product->image_path" :alt="$product->image_alt ?: $product->name" :label="$product->name . ' photo pending'" /><div class="catalog-card-body"><p class="eyebrow">{{ $product->variety ?: 'Vanilla Beans' }}</p><h3>{{ $product->name }}</h3><p>{{ $product->summary ?: '[Temporary] Product description pending.' }}</p><span class="text-link">View details <span aria-hidden="true">↗</span></span></div></a></article>
            @if($loop->last)</div>@endif
        @empty
            <div class="empty-state"><p>[Temporary] Product catalog is being prepared.</p><a class="text-link" href="{{ route('contact') }}">Ask about availability <span aria-hidden="true">↗</span></a></div>
        @endforelse
        <div class="pagination-wrap">{{ $products->links() }}</div>
    </div></section>
@endsection
