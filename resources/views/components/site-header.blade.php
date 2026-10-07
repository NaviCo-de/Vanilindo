@props(['settings' => null])

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="Vanilindo home">
            @if($settings?->logo_path)
                <img class="brand-custom" src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="{{ $settings->logo_alt ?: $settings->brand_name }}">
            @else
                <img class="brand-mark" src="{{ asset('images/canva/vanilindo-mark.png') }}" alt="" width="80" height="150">
                <span>{{ $settings?->brand_name ?: 'VANILINDO' }}</span>
            @endif
        </a>
        <nav class="desktop-nav" aria-label="Main navigation">
            <a @if(request()->routeIs('home')) aria-current="page" @endif href="{{ route('home') }}">Home</a>
            <a @if(request()->routeIs('about')) aria-current="page" @endif href="{{ route('about') }}">About Us</a>
            <a @if(request()->routeIs('products.*')) aria-current="page" @endif href="{{ route('products.index') }}">Our Products</a>
            <a @if(request()->routeIs('blog.*')) aria-current="page" @endif href="{{ route('blog.index') }}">Blog</a>
        </nav>
        <a class="button button-light header-contact" href="{{ route('contact') }}">Contact Us</a>
        <button class="menu-toggle" type="button" aria-label="Open menu" aria-controls="mobile-nav" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation" hidden>
        <a href="{{ route('home') }}">Home</a>
        <a href="{{ route('about') }}">About Us</a>
        <a href="{{ route('products.index') }}">Our Products</a>
        <a href="{{ route('blog.index') }}">Blog</a>
        <a href="{{ route('contact') }}">Contact Us</a>
    </nav>
</header>
