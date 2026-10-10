@props(['settings' => null])
@php($homeUrl = request()->routeIs('home') ? '' : route('home'))

<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="{{ $homeUrl }}#home" aria-label="Vanilindo home">
            @if($settings?->logo_path)
                <img class="brand-custom" src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="{{ $settings->logo_alt ?: $settings->brand_name }}">
            @else
                <img class="brand-mark" src="{{ asset('images/canva/vanilindo-mark.png') }}" alt="" width="80" height="150">
                <span>{{ $settings?->brand_name ?: 'VANILINDO' }}</span>
            @endif
        </a>
        <nav class="desktop-nav" aria-label="Main navigation">
            <a data-nav-section="home" href="{{ $homeUrl }}#home">Home</a>
            <a data-nav-section="about" href="{{ $homeUrl }}#about">About Us</a>
            <a data-nav-section="products" @if(request()->routeIs('products.show')) aria-current="page" @endif href="{{ $homeUrl }}#products">Our Products</a>
            <a data-nav-section="blog" @if(request()->routeIs('blog.show')) aria-current="page" @endif href="{{ $homeUrl }}#blog">Blog</a>
        </nav>
        <a class="button button-light header-contact" data-nav-section="contact" href="{{ $homeUrl }}#contact">Contact Us</a>
        <button class="menu-toggle" type="button" aria-label="Open menu" aria-controls="mobile-nav" aria-expanded="false"><span></span><span></span><span></span></button>
    </div>
    <nav class="mobile-nav" id="mobile-nav" aria-label="Mobile navigation" hidden>
        <a data-nav-section="home" href="{{ $homeUrl }}#home">Home</a>
        <a data-nav-section="about" href="{{ $homeUrl }}#about">About Us</a>
        <a data-nav-section="products" href="{{ $homeUrl }}#products">Our Products</a>
        <a data-nav-section="blog" href="{{ $homeUrl }}#blog">Blog</a>
        <a data-nav-section="contact" href="{{ $homeUrl }}#contact">Contact Us</a>
    </nav>
</header>
