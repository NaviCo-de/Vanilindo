<?php

namespace Tests\Feature;

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
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    public function test_mass_assignment_cannot_promote_a_user_to_admin(): void
    {
        $user = User::create([
            'name' => 'Regular user', 'email' => 'regular@example.test',
            'password' => 'a-test-password-only', 'is_admin' => true,
        ]);

        $this->assertFalse($user->fresh()->is_admin);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->actingAs($user)->get('/admin/products/create')->assertForbidden();
    }

    public function test_plain_content_is_escaped_and_product_markdown_is_sanitized(): void
    {
        $this->seed();
        ContentBlock::where('key', 'home.hero')->update(['heading' => '<img src=x onerror=alert(1)>']);
        Product::create([
            'name' => '<script>alert(1)</script>', 'slug' => 'security-product',
            'description' => "<iframe src=\"https://evil.example\"></iframe>\n\n[unsafe](javascript:alert(1)) **Safe**",
            'is_published' => true,
        ]);

        $this->get('/')->assertOk()->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
        $this->get('/products/security-product')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<iframe', false)->assertDontSee('href="javascript:', false)
            ->assertSee('<strong>Safe</strong>', false);
    }

    public function test_unsafe_imported_social_link_is_not_rendered(): void
    {
        $this->seed();
        SiteSetting::first()->update(['instagram_url' => 'javascript:alert(document.cookie)']);

        $this->get('/')->assertOk()->assertDontSee('href="javascript:', false);
    }

    public function test_social_link_form_rejects_non_http_schemes(): void
    {
        $this->seed();
        $this->signInAsAdmin();

        foreach (['javascript:alert(1)', 'ftp://example.com/file', 'data:text/html,test'] as $url) {
            Livewire::test(EditSiteSetting::class, ['record' => 1])
                ->fillForm(['instagram_url' => $url])->call('save')
                ->assertHasFormErrors(['instagram_url']);
        }
    }

    public function test_image_upload_rejects_executable_extensions_svg_and_oversized_files(): void
    {
        $this->signInAsAdmin();
        Storage::fake('public');
        $disguised = UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "not-an-image";');
        $disguised->mimeType((new \finfo(FILEINFO_MIME_TYPE))->file($disguised->getPathname()));

        $files = [
            UploadedFile::fake()->image('payload.php'),
            $disguised,
            UploadedFile::fake()->createWithContent('payload.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('large.jpg')->size(4097),
        ];
        foreach ($files as $file) {
            $component = Livewire::test(CreateProduct::class)
                ->fillForm(['name' => 'Rejected upload', 'slug' => 'rejected-upload', 'image_path' => $file])
                ->call('create');
            $this->assertSame(0, Product::count(), 'Accepted '.$file->getClientOriginalName().' as '.Product::value('image_path'));
            $component->assertHasFormErrors(['image_path']);
        }
        $this->assertDatabaseCount('products', 0);
    }

    public function test_client_cannot_assign_an_existing_file_path_to_a_new_record(): void
    {
        $this->signInAsAdmin();
        Storage::fake('public');
        Storage::disk('public')->put('branding/existing.jpg', 'existing file from another record');

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Tampered upload', 'slug' => 'tampered-upload', 'image_path' => ['tampered-key' => 'branding/existing.jpg']])
            ->call('create')->assertHasFormErrors(['image_path']);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_upload_url_uses_public_disk_even_when_default_disk_is_private(): void
    {
        $this->seed();
        config(['filesystems.default' => 'local']);
        SiteSetting::first()->update(['logo_path' => 'branding/logo.jpg']);

        $this->get('/')->assertOk()->assertSee(Storage::disk('public')->url('branding/logo.jpg'));
    }

    public function test_security_headers_are_present_on_public_and_login_responses(): void
    {
        $this->seed();

        foreach (['/', '/admin/login'] as $url) {
            $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
                ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function test_untrusted_host_is_rejected_in_production(): void
    {
        $this->app->instance('env', 'production');
        config(['app.url' => 'https://indonesiavanillas.com']);

        $this->withHeader('Host', 'evil.example')->get('/about')->assertStatus(400);
    }

    public function test_csp_nonces_change_per_response_and_public_scripts_cannot_use_eval(): void
    {
        $this->seed();
        $first = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
        $second = $this->get('/')->assertOk()->headers->get('Content-Security-Policy');
        $this->assertNotSame($first, $second);
        $this->assertStringNotContainsString('unsafe-eval', $first);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $first);

        $login = $this->get('/admin/login')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        preg_match("/nonce-([^']+)/", $login->headers->get('Content-Security-Policy'), $matches);
        $this->assertNotEmpty($matches[1]);
        $login->assertSee('nonce="'.$matches[1].'"', false);
        $this->assertStringContainsString('unsafe-eval', $login->headers->get('Content-Security-Policy'));
    }

    public function test_logout_without_csrf_token_is_rejected_outside_testing_bypass(): void
    {
        $this->signInAsAdmin();
        config(['app.url' => 'http://localhost']);
        $this->app->instance('env', 'production');

        $this->post('http://localhost/admin/logout')->assertStatus(419);
    }

    public function test_sql_injection_shaped_slug_cannot_read_unpublished_records(): void
    {
        Product::create(['name' => 'Secret draft', 'slug' => 'secret-draft', 'is_published' => false]);

        $this->get('/products/'.rawurlencode("' OR 1=1 --"))->assertNotFound();
        $this->assertDatabaseCount('products', 1);
    }

    private function signInAsAdmin(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
    }
}
