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
                'body' => 'The story of our Indonesian vanilla origins.',
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

    public function test_admin_can_upload_publish_and_edit_an_internal_article(): void
    {
        Storage::fake('public');

        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Vanilla Harvest', 'body' => "## Our harvest\n\nMeet our **vanilla farmers**.",
                'cover_image_path' => UploadedFile::fake()->image('harvest.png', 1600, 450),
                'excerpt' => 'Meet the farmers behind our vanilla.', 'is_published' => true,
            ])
            ->call('create')->assertHasNoFormErrors();

        $article = Article::where('title', 'Vanilla Harvest')->firstOrFail();
        $this->assertNotEmpty($article->slug);
        $this->assertStringContainsString('vanilla farmers', $article->body);
        $this->assertNull($article->external_url);
        Storage::disk('public')->assertExists($article->cover_image_path);
        $this->get('/')->assertOk()->assertSee('Vanilla Harvest')->assertSee(route('blog.show', $article))
            ->assertSee(Storage::disk('public')->url($article->cover_image_path));
        $this->get(route('blog.show', $article))->assertOk()->assertSee('<strong>vanilla farmers</strong>', false);

        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm(['title' => 'Updated Harvest', 'body' => 'Updated complete article.', 'is_published' => false])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame('Updated complete article.', $article->fresh()->body);
        $this->get('/')->assertOk()->assertDontSee('Updated Harvest');
        $this->get(route('blog.show', $article))->assertNotFound();
    }

    public function test_blog_requires_a_title_cover_summary_and_article_body(): void
    {
        Livewire::test(CreateArticle::class)->fillForm([
            'title' => '', 'body' => '', 'excerpt' => '', 'cover_image_path' => null,
        ])->call('create')->assertHasFormErrors(['title' => 'required', 'body' => 'required', 'excerpt' => 'required', 'cover_image_path' => 'required']);

        $this->assertDatabaseCount('articles', 0);
    }

    public function test_new_blog_is_a_draft_until_admin_enables_publication(): void
    {
        Storage::fake('public');
        Livewire::test(CreateArticle::class)->fillForm([
            'title' => 'A work in progress', 'excerpt' => 'A preview for the homepage.',
            'body' => 'The complete story is being prepared.',
            'cover_image_path' => UploadedFile::fake()->image('cover.jpg'),
        ])->call('create')->assertHasNoFormErrors();

        $article = Article::firstOrFail();
        $this->assertFalse($article->is_published);
        $this->get(route('blog.show', $article))->assertNotFound();
    }

    public function test_legacy_link_blog_can_be_completed_without_changing_its_url(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/existing.jpg', 'existing cover');
        $article = Article::create([
            'title' => 'Existing story', 'slug' => 'existing-story', 'excerpt' => 'Existing summary.',
            'external_url' => 'https://example.com/legacy', 'cover_image_path' => 'articles/existing.jpg',
        ]);
        Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
            ->fillForm(['body' => 'The newly written full article.', 'is_published' => true])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('existing-story', $article->fresh()->slug);
        $this->get('/blog/existing-story')->assertOk()->assertSee('The newly written full article.');
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
