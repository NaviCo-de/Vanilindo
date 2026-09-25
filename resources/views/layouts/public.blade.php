<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#526827">
    <meta name="description" content="@yield('meta_description', $settings?->meta_description ?: 'Vanilindo — Indonesian vanilla for the world.')">
    <title>@yield('title', 'Vanilindo') | Vanilindo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="shell header-inner">
            <a class="brand" href="{{ route('home') }}" aria-label="Vanilindo home">
                @if($settings?->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($settings->logo_path) }}" alt="{{ $settings->logo_alt ?: $settings->brand_name }}">
                @else
                    <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
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

    <main id="main">@yield('content')</main>

    <footer class="site-footer contour-dark">
        <div class="shell footer-grid">
            <div class="footer-brand">
                <div class="footer-symbol" aria-hidden="true">✦</div>
                <p>ANCA<br><span>Organics</span></p>
                <small>Vanilindo is a brand under ANCA Organics.</small>
            </div>
            <div>
                <h2>Contact</h2>
                @if($settings?->contact_email)
                    <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                @else
                    <p class="temporary">[Temporary] Contact email to be confirmed.</p>
                @endif
                @if($settings?->whatsapp_number)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp_number) }}" rel="noopener">{{ $settings->whatsapp_number }}</a>
                @endif
                @if($settings?->secondary_whatsapp_number)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->secondary_whatsapp_number) }}" rel="noopener">{{ $settings->secondary_whatsapp_number }}</a>
                @endif
            </div>
            <div>
                <h2>Address</h2>
                <p>{{ $settings?->address ?: '[Temporary] Business address to be confirmed.' }}</p>
            </div>
        </div>
        <div class="shell footer-bottom">
            <nav aria-label="Footer navigation">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('about') }}">About Us</a>
                <a href="{{ route('products.index') }}">Products</a>
                <a href="{{ route('blog.index') }}">Blog</a>
            </nav>
            <span>&copy; {{ date('Y') }} Vanilindo</span>
        </div>
    </footer>
</body>
</html>
