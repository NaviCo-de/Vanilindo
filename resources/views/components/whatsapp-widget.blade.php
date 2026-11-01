@props(['settings' => null])
@php
    $contacts = collect([
        ['name' => 'Contact Person 1', 'number' => $settings?->whatsapp_number],
        ['name' => 'Contact Person 2', 'number' => $settings?->secondary_whatsapp_number],
    ])->map(fn ($contact) => [...$contact, 'digits' => preg_replace('/\D+/', '', (string) $contact['number'])])
        ->filter(fn ($contact) => preg_match('/^[1-9]\d{6,14}$/', $contact['digits']));
@endphp

@if($contacts->isNotEmpty())
    <div class="whatsapp-widget" data-whatsapp-widget hidden>
        <section class="whatsapp-panel" id="whatsapp-panel" data-whatsapp-panel role="dialog" aria-labelledby="whatsapp-title" aria-describedby="whatsapp-description" hidden>
            <div class="whatsapp-panel-heading">
                <div class="whatsapp-label"><x-whatsapp-icon /><span>WhatsApp</span></div>
                <button class="whatsapp-close" type="button" data-whatsapp-close aria-label="Close WhatsApp contacts"><span aria-hidden="true">×</span></button>
                <h2 id="whatsapp-title">Let's talk vanilla.</h2>
                <p id="whatsapp-description">Choose a contact to start a conversation.</p>
            </div>
            <div class="whatsapp-contacts">
                @foreach($contacts as $contact)
                    <a class="whatsapp-contact" href="https://wa.me/{{ $contact['digits'] }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with {{ $contact['name'] }} on WhatsApp (opens in a new tab)">
                        <span class="whatsapp-contact-number" aria-hidden="true">0{{ $loop->iteration }}</span>
                        <span class="whatsapp-contact-copy"><strong>{{ $contact['name'] }}</strong><span>{{ $contact['number'] }}</span></span>
                        <x-whatsapp-icon class="whatsapp-contact-icon" />
                    </a>
                @endforeach
            </div>
            <p class="whatsapp-note">We look forward to hearing from you.</p>
        </section>
        <button class="whatsapp-toggle" type="button" data-whatsapp-toggle aria-expanded="false" aria-controls="whatsapp-panel" aria-label="Open WhatsApp contacts">
            <x-whatsapp-icon class="whatsapp-toggle-icon" /><span class="whatsapp-toggle-label">Chat with us</span><span class="whatsapp-toggle-close" aria-hidden="true">×</span>
        </button>
    </div>
@endif
