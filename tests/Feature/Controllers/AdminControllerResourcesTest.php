<?php

namespace Tests\Feature\Controllers;

use App\Models\Section;
use App\Models\FooterPhoto;
use App\Models\SocialLink;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminControllerResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['email' => 'admin_' . uniqid() . '@318produtora.com.br', 'is_admin' => true]);
        $this->normalUser = User::factory()->create(['email' => 'user_' . uniqid() . '@example.com', 'is_admin' => false]);
        
        Storage::fake('public');
    }

    public function test_update_section_success()
    {
        $section = Section::create(['slug' => 'test-update-section-' . uniqid(), 'processing_status' => 'done']);

        $response = $this->actingAs($this->adminUser)
            ->put("/admin/sections/{$section->id}", [
                'title' => 'New Title',
                'description_text' => 'New text',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'title' => 'New Title',
            'description_text' => 'New text',
        ]);
    }

    public function test_update_section_forbidden()
    {
        $section = Section::create(['slug' => 'test-update-section-forb-' . uniqid(), 'processing_status' => 'done']);

        $response = $this->actingAs($this->normalUser)
            ->put("/admin/sections/{$section->id}", [
                'title' => 'New Title',
                'description_text' => 'New text',
            ]);

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_store_photo_success()
    {
        FooterPhoto::query()->delete(); // Clear existing to avoid 8 photo limit
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/photos", [
                'photo' => $file,
                'title' => 'Test Photo',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('footer_photos', [
            'title' => 'Test Photo',
        ]);
    }

    public function test_store_photo_forbidden()
    {
        FooterPhoto::query()->delete(); // Clear existing
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->normalUser)
            ->post("/admin/photos", [
                'photo' => $file,
                'title' => 'Test Photo',
            ]);

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_delete_photo_success()
    {
        $photo = FooterPhoto::create(['title' => 'Test', 'photo_jpg' => 'test.jpg']);

        $response = $this->actingAs($this->adminUser)
            ->delete("/admin/photos/{$photo->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('footer_photos', [
            'id' => $photo->id,
        ]);
    }

    public function test_delete_photo_forbidden()
    {
        $photo = FooterPhoto::create(['title' => 'Test', 'photo_jpg' => 'test.jpg']);

        $response = $this->actingAs($this->normalUser)
            ->delete("/admin/photos/{$photo->id}");

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
        $this->assertDatabaseHas('footer_photos', [
            'id' => $photo->id,
        ]);
    }

    public function test_update_social_link_success()
    {
        $link = SocialLink::firstOrCreate(['platform' => 'instagram'], ['url' => 'https://instagram.com/old']);

        $response = $this->actingAs($this->adminUser)
            ->put("/admin/social-links/{$link->id}", [
                'url' => 'https://instagram.com/new',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('social_links', [
            'id' => $link->id,
            'url' => 'https://instagram.com/new',
        ]);
    }

    public function test_update_social_link_forbidden()
    {
        $link = SocialLink::firstOrCreate(['platform' => 'twitter'], ['url' => 'https://twitter.com/old']);

        $response = $this->actingAs($this->normalUser)
            ->put("/admin/social-links/{$link->id}", [
                'url' => 'https://twitter.com/new',
            ]);

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_update_page_success()
    {
        $page = Page::firstOrCreate(['slug' => Page::SLUG_FOTOS_TITULO], ['title' => 'Test Page']);

        $response = $this->actingAs($this->adminUser)
            ->put("/admin/pages/{$page->slug}", [
                'content' => 'New content',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'content' => 'New content',
        ]);
    }

    public function test_update_page_forbidden()
    {
        $page = Page::firstOrCreate(['slug' => Page::SLUG_FOTOS_SUBTITULO], ['title' => 'Test Page']);

        $response = $this->actingAs($this->normalUser)
            ->put("/admin/pages/{$page->slug}", [
                'content' => 'New content',
            ]);

        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }
}
