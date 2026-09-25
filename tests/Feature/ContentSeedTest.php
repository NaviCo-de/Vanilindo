<?php

namespace Tests\Feature;

use App\Models\ContentBlock;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_content_is_repeatable_without_creating_an_admin(): void
    {
        $this->seed();

        $this->assertSame(11, ContentBlock::count());
        $this->assertSame(1, SiteSetting::count());
        $this->assertSame(0, User::count());

        ContentBlock::where('key', 'home.hero')->update(['heading' => 'Approved heading']);

        $this->seed();

        $this->assertSame(11, ContentBlock::count());
        $this->assertSame('Approved heading', ContentBlock::where('key', 'home.hero')->value('heading'));
    }
}
