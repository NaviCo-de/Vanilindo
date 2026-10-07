<!doctype html>
<html lang="en">
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
    @hasSection('page_label')
        <div class="page-label">@yield('page_label')</div>
    @endif
    <x-site-header :settings="$settings" />
    <main id="main">@yield('content')</main>
    <x-site-footer :settings="$settings" />
</body>
</html>
