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
        ]);

        $blocks = [
            ['key' => 'home.hero', 'page' => 'home', 'label' => 'Home · Hero', 'heading' => 'Indonesian Soil / World Class Vanilla', 'sort_order' => 10],
            ['key' => 'home.intro', 'page' => 'home', 'label' => 'Home · Introduction', 'heading' => '[Temporary] Introduction', 'sort_order' => 20],
            ['key' => 'home.varieties', 'page' => 'home', 'label' => 'Home · Vanilla Varieties', 'heading' => '[Temporary] Our Vanilla', 'sort_order' => 30],
            ['key' => 'home.values', 'page' => 'home', 'label' => 'Home · Values', 'heading' => '[Temporary] What We Value', 'sort_order' => 40],
            ['key' => 'home.cta', 'page' => 'home', 'label' => 'Home · Contact Invitation', 'heading' => '[Temporary] Let’s Connect', 'sort_order' => 50],
            ['key' => 'about.hero', 'page' => 'about', 'label' => 'About · Hero', 'heading' => '[Temporary] Our Story', 'sort_order' => 10],
            ['key' => 'about.profile', 'page' => 'about', 'label' => 'About · Company Profile', 'heading' => '[Temporary] Vanilindo & ANCA Organics', 'sort_order' => 20],
            ['key' => 'about.origin', 'page' => 'about', 'label' => 'About · Origin', 'heading' => '[Temporary] From Indonesia', 'sort_order' => 30],
            ['key' => 'products.hero', 'page' => 'products', 'label' => 'Products · Hero', 'heading' => '[Temporary] Our Products', 'sort_order' => 10],
            ['key' => 'blog.hero', 'page' => 'blog', 'label' => 'Blog · Hero', 'heading' => '[Temporary] Stories & Insights', 'sort_order' => 10],
            ['key' => 'contact.hero', 'page' => 'contact', 'label' => 'Contact · Hero', 'heading' => '[Temporary] Get in Touch', 'sort_order' => 10],
        ];

        foreach ($blocks as $block) {
            $key = $block['key'];
            unset($block['key']);

            ContentBlock::firstOrCreate(['key' => $key], $block);
        }
    }
}
