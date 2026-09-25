@props(['path' => null, 'alt' => '', 'label' => 'Image pending', 'class' => ''])

<div {{ $attributes->class(['media-panel', $class, 'has-image' => filled($path)]) }}>
    @if($path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($path) }}" alt="{{ $alt }}" loading="lazy">
    @else
        <div class="media-placeholder" role="img" aria-label="{{ $label }}">
            <span class="placeholder-stem" aria-hidden="true"></span>
            <span class="placeholder-label">[Temporary] {{ $label }}</span>
        </div>
    @endif
</div>
