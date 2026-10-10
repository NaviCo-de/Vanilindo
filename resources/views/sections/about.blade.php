    @php($profile = $blocks->get('about.profile'))
    <section class="about-hero" aria-labelledby="about-title">
        <div class="page-hero-media"><x-media-panel :path="$profile?->image_path" fallback="images/canva/about-background.png" :alt="$profile?->image_alt ?: 'Indonesian vanilla beans drying on wooden racks'" loading="eager" /></div>
        <div class="about-copy">
            <h2 id="about-title">{{ $profile?->heading ?: 'About Us' }}</h2>
            @foreach(preg_split('/\n\s*\n/', $profile?->body ?: 'Vanilindo is a brand under ANCA Organics, focusing mainly on supplying the global market with premium quality vanilla. Our vanilla beans are sourced from the island of Papua located in the eastern region of Indonesia.') as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </div>
        <img class="anca-logo about-logo" src="{{ asset('images/canva/anca-organics-hd.png') }}" alt="ANCA Organics" width="263" height="204" loading="lazy">
    </section>

    @php($goal = $blocks->get('about.goal'))
    <section class="goal-section" aria-labelledby="goal-title">
        <h2 class="caps-title centered" id="goal-title">{{ $goal?->heading ?: 'Our Goal' }}</h2>
        <p class="section-lead centered">{{ $goal?->body ?: 'Our main mission is to provide global demands with local supply by serving global businesses with reliable access to high-quality Indonesian Vanilla. At the same time, we aim to give opportunities to farmers by connecting them to the international market. Through long term relationship, we hope to grow together with our international buyers and local farmers while bringing greater connection to Indonesian Vanilla globally.' }}</p>
    </section>
