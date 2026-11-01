<!doctype html>
<html lang="en" @class(['article-layout' => $articleOnly ?? false])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#577526">
    <meta name="description" content="@yield('meta_description', $settings?->meta_description ?: 'Vanilindo — Indonesian vanilla for the world.')">
    <title>@yield('title', 'Vanilindo') | Vanilindo</title>
    <link rel="preload" href="{{ asset('fonts/canva/Catchy_Mager.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="site-canvas">
        @hasSection('page_label')
            <div class="page-label">@yield('page_label')</div>
        @endif
        @unless($articleOnly ?? false)
            <x-site-header :settings="$settings" />
        @endunless
        <main id="main">@yield('content')</main>
        @unless($articleOnly ?? false)
            <x-site-footer :settings="$settings" />
        @endunless
    </div>
    <x-whatsapp-widget :settings="$settings" />
</body>
</html>
