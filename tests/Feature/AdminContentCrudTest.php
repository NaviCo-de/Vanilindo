<?php

namespace Tests\Feature;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\ContentBlock;
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
        Livewire::test(CreateArticle::class)
            ->fillForm([
                'title' => 'Vanilla Origins',
                'slug' => 'vanilla-origins',
                'body' => 'A test article.',
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('articles', [
            'slug' => 'vanilla-origins',
            'is_published' => false,
        ]);
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
