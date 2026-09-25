<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_core_pages_load_with_canva_content(): void
    {
        $this->get('/')->assertOk()->assertSee('Indonesian Soil')->assertSee('What We Deliver');
        $this->get('/about')->assertOk()->assertSee('ANCA Organics');
        $this->get('/products')->assertOk()->assertSee('Papua, Indonesia');
        $this->get('/blog')->assertOk()->assertSee('News and Events');
        $this->get('/contact')->assertOk()->assertSee('ancaorganicsmarketing@gmail.com')->assertSee('628119980980');
    }

    public function test_only_published_products_are_visible(): void
    {
        $visible = Product::create(['name' => 'Visible Vanilla', 'slug' => 'visible-vanilla', 'is_published' => true]);
        Product::create(['name' => 'Private Vanilla', 'slug' => 'private-vanilla', 'is_published' => false]);

        $this->get('/products')->assertOk()->assertSee($visible->name)->assertDontSee('Private Vanilla');
        $this->get('/products/visible-vanilla')->assertOk();
        $this->get('/products/private-vanilla')->assertNotFound();
    }

    public function test_only_published_and_due_articles_are_visible(): void
    {
        Article::create(['title' => 'Published Story', 'slug' => 'published-story', 'body' => 'Published body', 'is_published' => true, 'published_at' => now()->subDay()]);
        Article::create(['title' => 'Draft Story', 'slug' => 'draft-story', 'body' => 'Draft body', 'is_published' => false]);
        Article::create(['title' => 'Future Story', 'slug' => 'future-story', 'body' => 'Future body', 'is_published' => true, 'published_at' => now()->addDay()]);

        $this->get('/blog')->assertOk()->assertSee('Published Story')->assertDontSee('Draft Story')->assertDontSee('Future Story');
        $this->get('/blog/published-story')->assertOk();
        $this->get('/blog/draft-story')->assertNotFound();
        $this->get('/blog/future-story')->assertNotFound();
    }

    public function test_article_markdown_does_not_render_raw_html_or_unsafe_links(): void
    {
        Article::create([
            'title' => 'Safe Story',
            'slug' => 'safe-story',
            'body' => "<script>alert(\"x\")</script>\n\n[bad](javascript:alert(1)) **Good**",
            'is_published' => true,
        ]);

        $this->get('/blog/safe-story')
            ->assertOk()
            ->assertDontSee('<script>', false)
            ->assertDontSee('href="javascript:', false)
            ->assertSee('<strong>Good</strong>', false);

    }
}
