<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\ContentBlock;
use App\Models\Article;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminContentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
    }

    public function test_admin_can_create_unpublished_product(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Planifolia Vanilla Beans',
                'slug' => 'planifolia-vanilla-beans',
                'variety' => 'Planifolia',
                'summary' => 'Test summary',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'slug' => 'planifolia-vanilla-beans',
            'is_published' => false,
        ]);
    }

    public function test_admin_can_create_unpublished_article(): void
    {
        Storage::fake('public');

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Vanilla Origins',
                'external_url' => 'https://example.com/vanilla-origins',
                'cover_image_path' => UploadedFile::fake()->image('origins.jpg', 1200, 400),
                'excerpt' => 'A description of our vanilla origins.',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Vanilla Origins',
            'is_published' => false,
        ]);
    }

    public function test_admin_can_upload_publish_and_edit_a_link_blog_without_slug_or_body(): void
    {
        Storage::fake('public');

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Vanilla Harvest', 'external_url' => 'https://example.com/harvest',
                'cover_image_path' => UploadedFile::fake()->image('harvest.png', 1600, 450),
                'excerpt' => 'Meet the farmers behind our vanilla.', 'is_published' => true,
            ])
            ->call('create')->assertHasNoFormErrors();

        $article = Article::where('title', 'Vanilla Harvest')->firstOrFail();
        $this->assertNotEmpty($article->slug);
        $this->assertNull($article->body);
        Storage::disk('public')->assertExists($article->cover_image_path);
        $this->get('/')->assertOk()->assertSee('Vanilla Harvest')->assertSee('https://example.com/harvest')
            ->assertSee(Storage::disk('public')->url($article->cover_image_path));

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm(['title' => 'Updated Harvest', 'external_url' => 'https://example.com/new-harvest', 'is_published' => false])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('https://example.com/new-harvest', $article->fresh()->external_url);
        $this->get('/')->assertOk()->assertDontSee('Updated Harvest');
    }

    public function test_blog_requires_all_four_fields_and_rejects_non_web_links(): void
    {
        Livewire::test(CreateArticle::class)->fillForm([
            'title' => '', 'external_url' => '', 'excerpt' => '', 'cover_image_path' => null,
        ])->call('create')->assertHasFormErrors(['title' => 'required', 'external_url' => 'required', 'excerpt' => 'required', 'cover_image_path' => 'required']);

        Storage::fake('public');
        foreach (['javascript:alert(1)', 'ftp://example.com/file', 'not-a-url'] as $url) {
            Livewire::test(CreateArticle::class)->fillForm([
                'title' => 'Invalid Link', 'external_url' => $url, 'excerpt' => 'Description.',
                'cover_image_path' => UploadedFile::fake()->image('cover.jpg'),
            ])->call('create')->assertHasFormErrors(['external_url' => 'url']);
        }
        $this->assertDatabaseCount('articles', 0);
    }

    public function test_admin_can_upload_product_image(): void
    {
        Storage::fake('public');

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Vanilla with Photo',
                'slug' => 'vanilla-with-photo',
                'image_path' => UploadedFile::fake()->image('vanilla.jpg', 400, 500),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $path = Product::where('slug', 'vanilla-with-photo')->value('image_path');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_admin_can_edit_seeded_site_content(): void
    {
        $this->seed();

        $block = ContentBlock::where('key', 'home.hero')->firstOrFail();

        Livewire::test(EditContentBlock::class, ['record' => $block->getRouteKey()])
            ->fillForm(['heading' => 'Vanilla From Indonesia'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Vanilla From Indonesia', $block->fresh()->heading);
        $this->get('/')->assertOk()->assertSee('Vanilla From Indonesia');

        Livewire::test(EditSiteSetting::class, ['record' => 1])
            ->fillForm(['brand_name' => 'Vanilindo', 'contact_email' => 'hello@example.com'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('hello@example.com', SiteSetting::findOrFail(1)->contact_email);
    }
}
