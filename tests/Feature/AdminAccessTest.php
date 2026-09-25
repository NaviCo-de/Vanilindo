<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_regular_user_cannot_open_admin_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_content_resources(): void
    {
        $this->seed();

        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/products')->assertOk();
        $this->actingAs($user)->get('/admin/articles')->assertOk();
        $this->actingAs($user)->get('/admin/content-blocks')->assertOk();
        $this->actingAs($user)->get('/admin/site-settings')->assertOk();
        $this->actingAs($user)->get('/admin/products/create')->assertOk();
        $this->actingAs($user)->get('/admin/articles/create')->assertOk();
        $this->actingAs($user)->get('/admin/content-blocks/1/edit')->assertOk();
        $this->actingAs($user)->get('/admin/site-settings/1/edit')->assertOk();
        $this->actingAs($user)->get('/admin/content-blocks/create')->assertNotFound();
    }
}
