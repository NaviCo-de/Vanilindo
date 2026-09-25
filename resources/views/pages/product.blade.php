@extends('layouts.public')

@section('title', $product->seo_title ?: $product->name)
@section('meta_description', $product->seo_description ?: $product->summary ?: 'Indonesian vanilla from Vanilindo.')

@section('content')
    <div class="page-label"><div class="shell"><a href="{{ route('products.index') }}">Products</a> <span aria-hidden="true">/</span> {{ $product->name }}</div></div>
    <section class="section detail-section"><div class="shell detail-grid"><x-media-panel :path="$product->image_path" :alt="$product->image_alt ?: $product->name" :label="$product->name . ' photo pending'" /><div><p class="eyebrow">{{ $product->variety ?: 'Indonesian Vanilla' }}</p><h1 class="display-title">{{ $product->name }}</h1>@if($product->summary)<p class="detail-lead">{{ $product->summary }}</p>@endif@if($product->description)<div class="prose">{!! \Illuminate\Support\Str::markdown($product->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>@else<p class="temporary">[Temporary] Specifications are being prepared. Please contact us for details.</p>@endif<a class="button button-olive" href="{{ route('contact') }}">Ask About This Product <span aria-hidden="true">↗</span></a></div></div></section>
@endsection
