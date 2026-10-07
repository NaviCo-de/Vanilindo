@props(['path' => null, 'fallback' => null, 'alt' => '', 'label' => 'Image pending', 'class' => '', 'loading' => 'lazy'])

<div {{ $attributes->class(['media-panel', $class, 'has-image' => filled($path) || filled($fallback), 'is-default' => blank($path) && filled($fallback)]) }}>
    @if($path || $fallback)
        <img src="{{ $path ? \Illuminate\Support\Facades\Storage::url($path) : asset($fallback) }}" alt="{{ $alt }}" loading="{{ $loading }}" @if($loading === 'eager') fetchpriority="high" @endif>
    @else
        <div class="media-placeholder" role="img" aria-label="{{ $label }}">
            <span class="placeholder-stem" aria-hidden="true"></span>
            <span class="placeholder-label">{{ $label }}</span>
        </div>
    @endif
</div>
