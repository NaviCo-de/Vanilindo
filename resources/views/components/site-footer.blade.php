@props(['settings' => null])

<footer class="site-footer">
    <div class="footer-content">
        <a class="footer-brand" href="{{ route('about') }}" aria-label="About ANCA Organics and Vanilindo">
            <img class="anca-logo" src="{{ asset('images/canva/anca-organics-hd.png') }}" alt="ANCA Organics" width="263" height="204" loading="lazy">
        </a>
        <div class="footer-grid">
            <div class="footer-contact">
                <h2>Contact</h2>
                @if($settings?->whatsapp_number)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp_number) }}" rel="noopener">{{ $settings->whatsapp_number }}</a>
                @endif
                @if($settings?->secondary_whatsapp_number)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->secondary_whatsapp_number) }}" rel="noopener">{{ $settings->secondary_whatsapp_number }}</a>
                @endif
                @if($settings?->contact_email)
                    <a href="mailto:{{ $settings->contact_email }}">{{ $settings->contact_email }}</a>
                @endif
            </div>
            <div class="footer-address">
                <h2>Address</h2>
                <p>{{ $settings?->address }}</p>
            </div>
        </div>
        <div class="footer-bottom">
            <nav aria-label="Footer navigation">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('about') }}">About Us</a>
                <a href="{{ route('products.index') }}">Products</a>
                <a href="{{ route('blog.index') }}">Blog</a>
            </nav>
            <div class="footer-socials">
                @if($settings?->instagram_url)
                    <a href="{{ $settings->instagram_url }}" aria-label="Instagram" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/canva/instagram.png') }}" alt="" width="48" height="48" loading="lazy"></a>
                @else
                    <img src="{{ asset('images/canva/instagram.png') }}" alt="" width="48" height="48" loading="lazy">
                @endif
                @if($settings?->whatsapp_number)
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', $settings->whatsapp_number) }}" aria-label="Contact Vanilindo on WhatsApp" rel="noopener"><img src="{{ asset('images/canva/whatsapp.png') }}" alt="" width="48" height="48" loading="lazy"></a>
                @endif
            </div>
        </div>
    </div>
</footer>
