<?php

namespace Database\Seeders;

use App\Models\ContentBlock;
use App\Models\SiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        SiteSetting::firstOrCreate(['id' => 1], [
            'brand_name' => 'VANILINDO',
            'tagline' => 'Indonesian Soil / World Class Vanilla',
            'contact_email' => 'ancaorganicsmarketing@gmail.com',
            'whatsapp_number' => '628118492209',
            'secondary_whatsapp_number' => '628119980980',
            'address' => 'Kh Moh Mansyur No. 164 A, RT.7/RW.9, Tanah Sereal, Kec. Tambora, Kota Jakarta Barat, DKI Jakarta 11210',
        ]);

        $blocks = [
            ['key' => 'home.hero', 'page' => 'home', 'label' => 'Home · Hero', 'eyebrow' => 'From the Islands of Indonesia', 'heading' => 'Indonesian Soil', 'body' => 'World Class Vanilla', 'sort_order' => 10],
            ['key' => 'home.intro', 'page' => 'home', 'label' => 'Home · Quality', 'eyebrow' => 'What We Stand For', 'heading' => 'What quality means to us?', 'body' => 'Our farmers are family, not suppliers. We grow alongside communities that grow our vanilla. We promise consistent grade, from sample to container.', 'sort_order' => 20],
            ['key' => 'home.varieties', 'page' => 'home', 'label' => 'Home · Vanilla Varieties', 'heading' => 'Our Vanilla Beans', 'body' => 'Our vanilla beans are handled with attention to quality at every stage to preserve their natural characteristics, including aroma, moisture, and flavor profile. Each pod contains naturally aromatic vanilla seeds that can be used across a wide range of culinary and commercial applications.', 'sort_order' => 30],
            ['key' => 'home.values', 'page' => 'home', 'label' => 'Home · Values', 'heading' => 'What We Deliver', 'sort_order' => 40],
            ['key' => 'home.value.transparency', 'page' => 'home', 'label' => 'Home · Transparency', 'heading' => 'Transparency', 'body' => 'We trace our products from farm to your hands.', 'sort_order' => 41],
            ['key' => 'home.value.consistency', 'page' => 'home', 'label' => 'Home · Consistency', 'heading' => 'Consistency', 'body' => 'We guarantee consistent results.', 'sort_order' => 42],
            ['key' => 'home.value.quality', 'page' => 'home', 'label' => 'Home · Quality Value', 'heading' => 'Quality', 'body' => 'Naturally cured and hand sorted.', 'sort_order' => 43],
            ['key' => 'home.cta', 'page' => 'home', 'label' => 'Home · Contact Invitation', 'heading' => '[Temporary] Let’s Talk Vanilla', 'sort_order' => 50],
            ['key' => 'about.hero', 'page' => 'about', 'label' => 'About · Hero', 'heading' => '[Temporary] Our Story', 'sort_order' => 10],
            ['key' => 'about.profile', 'page' => 'about', 'label' => 'About · Company Profile', 'heading' => 'About Us', 'body' => "Vanilindo is a brand under ANCA Organics, focusing mainly on supplying the global market with premium quality vanilla. Our vanilla beans are sourced from the island of Papua located in the eastern region of Indonesia.\n\nThrough Vanilindo, we aim to introduce the distinctive character of Indonesian vanilla to a wider global market while providing reliable and responsive service to our clients.", 'sort_order' => 20],
            ['key' => 'about.origin', 'page' => 'about', 'label' => 'About · Origin', 'heading' => '[Temporary] From Indonesia', 'sort_order' => 30],
            ['key' => 'about.goal', 'page' => 'about', 'label' => 'About · Our Goal', 'heading' => 'Our Goal', 'body' => 'Our main mission is to provide global demand with local supply by serving global businesses with reliable access to high-quality Indonesian vanilla. At the same time, we aim to give opportunities to farmers by connecting them to the international market.', 'sort_order' => 40],
            ['key' => 'products.hero', 'page' => 'products', 'label' => 'Products · Hero', 'heading' => '[Temporary] Our Products', 'sort_order' => 10],
            ['key' => 'products.origin', 'page' => 'products', 'label' => 'Products · Origin', 'heading' => 'Where our beans originate from', 'body' => 'Our vanilla beans originate from Papua. Our vanilla offers a distinctive aroma, rich flavor profile, and natural quality suitable for a wide range of culinary and commercial applications. From cultivation to selection, we are committed to preserving the natural characteristics of each vanilla bean.', 'sort_order' => 20],
            ['key' => 'products.varieties', 'page' => 'products', 'label' => 'Products · Varieties', 'heading' => 'Planifolia & Tahitensis', 'body' => 'We provide our clients with Planifolia and Tahitensis vanilla beans in various grades. Whether it’s bean length, moisture level, or other specifications, we’ve got you covered!', 'sort_order' => 30],
            ['key' => 'blog.hero', 'page' => 'blog', 'label' => 'Blog · Hero', 'heading' => 'News and Events', 'body' => 'Catch up with our latest news about vanilla!', 'sort_order' => 10],
            ['key' => 'contact.hero', 'page' => 'contact', 'label' => 'Contact · Hero', 'heading' => '[Temporary] Get in Touch', 'sort_order' => 10],
        ];

        foreach ($blocks as $block) {
            $key = $block['key'];
            unset($block['key']);

            $existing = ContentBlock::firstOrCreate(['key' => $key], $block);

            // Upgrade only untouched scaffold placeholders; never overwrite admin edits.
            $oldPlaceholders = [
                'home.hero' => 'Indonesian Soil / World Class Vanilla',
                'home.intro' => '[Temporary] Introduction',
                'home.varieties' => '[Temporary] Our Vanilla',
                'home.values' => '[Temporary] What We Value',
                'home.cta' => '[Temporary] Let’s Connect',
                'about.profile' => '[Temporary] Vanilindo & ANCA Organics',
                'blog.hero' => '[Temporary] Stories & Insights',
            ];

            if (isset($oldPlaceholders[$key]) && $existing->heading === $oldPlaceholders[$key] && blank($existing->body)) {
                $existing->update(collect($block)->only(['heading', 'eyebrow', 'body'])->all());
            }
        }
    }
}
