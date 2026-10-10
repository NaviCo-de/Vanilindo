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

    public function test_home_contains_all_sections_and_one_contact_footer(): void
    {
        $response = $this->get('/')->assertOk()
            ->assertSee('Indonesian Soil')->assertSee('What We Deliver')
            ->assertSee('ANCA Organics')->assertSee('Papua, Indonesia')->assertSee('News and Events')
            ->assertSee('ancaorganicsmarketing@gmail.com')->assertSee('+62 811-9980-980')
            ->assertSeeInOrder(['id="home"', 'id="about"', 'id="products"', 'id="blog"', 'id="contact"'], false);

        foreach (['home', 'about', 'products', 'blog', 'contact'] as $section) {
            $response->assertSee('href="#' . $section . '"', false);
        }

        $this->assertSame(1, substr_count($response->getContent(), '<header class="site-header">'));
        $this->assertSame(1, substr_count($response->getContent(), '<footer '));
        $this->assertSame(1, substr_count($response->getContent(), '<h1 '));
    }

    public function test_old_page_urls_redirect_to_their_scroll_sections(): void
    {
        foreach (['about', 'products', 'blog', 'contact'] as $section) {
            $this->get('/' . $section)->assertRedirect(route('home') . '#' . $section);
        }

        $this->get('/products?page=2')->assertRedirect(route('home', ['products_page' => 2]) . '#products');
        $this->get('/blog?page=2')->assertRedirect(route('home') . '#blog');
    }

    public function test_only_published_products_are_visible(): void
    {
        $visible = Product::create(['name' => 'Visible Vanilla', 'slug' => 'visible-vanilla', 'is_published' => true]);
        Product::create(['name' => 'Private Vanilla', 'slug' => 'private-vanilla', 'is_published' => false]);

        $this->get('/')->assertOk()->assertSee($visible->name)->assertDontSee('Private Vanilla');
        $this->get('/products/visible-vanilla')->assertOk();
        $this->get('/products/private-vanilla')->assertNotFound();
    }

    public function test_only_published_and_due_articles_are_visible(): void
    {
        Article::create(['title' => 'Published Story', 'slug' => 'published-story', 'body' => 'Published body', 'is_published' => true, 'published_at' => now()->subDay()]);
        Article::create(['title' => 'Draft Story', 'slug' => 'draft-story', 'body' => 'Draft body', 'is_published' => false]);
        Article::create(['title' => 'Future Story', 'slug' => 'future-story', 'body' => 'Future body', 'is_published' => true, 'published_at' => now()->addDay()]);

        $this->get('/')->assertOk()->assertSee('Published Story')->assertDontSee('Draft Story')->assertDontSee('Future Story');
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

    public function test_all_blog_slides_remain_available_while_products_paginate(): void
    {
        for ($number = 1; $number <= 12; $number++) {
            $suffix = sprintf('%02d', $number);
            Product::create([
                'name' => 'Catalog Vanilla ' . $suffix, 'slug' => 'catalog-vanilla-' . $suffix,
                'sort_order' => $number, 'is_published' => true,
            ]);
            Article::create([
                'title' => 'Journal Story ' . $suffix, 'slug' => 'journal-story-' . $suffix,
                'body' => 'A vanilla story.', 'is_published' => true, 'published_at' => now()->subDays($number),
            ]);
        }

        $this->get('/?products_page=2')->assertOk()
            ->assertSee('Catalog Vanilla 12')->assertDontSee('Catalog Vanilla 09')
            ->assertSee('Journal Story 01')->assertSee('Journal Story 12')
            ->assertSee('data-slide-count="12"', false)
            ->assertDontSee('blog_page=');

        $this->get('/?blog_page=2')->assertOk()
            ->assertSee('Catalog Vanilla 09')->assertDontSee('Catalog Vanilla 10')
            ->assertSee('Journal Story 12')->assertSee('Journal Story 01')
            ->assertSee(route('home', ['blog_page' => 2, 'products_page' => 2]) . '#products');
    }

    public function test_blog_cards_link_directly_to_external_urls_and_old_article_urls_redirect(): void
    {
        $article = Article::create([
            'title' => 'Vanilla Export News', 'excerpt' => 'The latest from our vanilla farmers.',
            'external_url' => 'https://example.com/vanilla?source=blog&campaign=harvest',
            'is_published' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Vanilla Export News')
            ->assertSee('The latest from our vanilla farmers.')
            ->assertSee('href="https://example.com/vanilla?source=blog&amp;campaign=harvest"', false)
            ->assertDontSee('href="' . route('blog.show', $article) . '"', false);
        $this->get(route('blog.show', $article))->assertRedirect($article->external_url);

        $article->update(['is_published' => false]);
        $this->get(route('blog.show', $article))->assertNotFound();
        $article->update(['is_published' => true, 'published_at' => now()->addDay()]);
        $this->get(route('blog.show', $article))->assertNotFound();
    }

    public function test_unsafe_imported_blog_urls_are_not_rendered_or_followed(): void
    {
        $article = Article::create([
            'title' => 'Imported Story', 'body' => 'A legacy article.',
            'external_url' => 'javascript:alert(1)', 'is_published' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('href="javascript:', false);
        $this->get(route('blog.show', $article))->assertOk()->assertSee('A legacy article.');
    }
}
