<?php

namespace Tests\Feature\Controllers;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $normalUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['email' => 'admin_' . uniqid() . '@318produtora.com.br', 'is_admin' => true]);
        $this->normalUser = User::factory()->create(['email' => 'user_' . uniqid() . '@example.com']);
        
        Storage::fake('local');
        Storage::fake('public');
        Queue::fake();
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_is_forbidden_or_redirected()
    {
        $response = $this->actingAs($this->normalUser)->get('/admin');
        // It might be 403 or redirect depending on how the middleware is configured.
        $this->assertTrue($response->isForbidden() || $response->isRedirect());
    }

    public function test_upload_section_video_success()
    {
        $section = Section::create(['slug' => 'test-upload-desktop-' . uniqid(), 'processing_status' => 'done', 'mobile_processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/video", [
                'video' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Upload recebido. Processamento em background iniciado.');
    }

    public function test_upload_section_video_duplicate_processing_error()
    {
        $section = Section::create(['slug' => 'test-upload-dup-' . uniqid(), 'processing_status' => 'processing', 'mobile_processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/video", [
                'video' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Não foi possível receber o vídeo. Já existe um vídeo sendo processado para esta seção. Aguarde a conclusão antes de enviar outro.');
    }

    public function test_upload_section_mobile_video_success()
    {
        $section = Section::create(['slug' => 'test-upload-mobile-' . uniqid(), 'processing_status' => 'done', 'mobile_processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $response = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/mobile-video", [
                'video' => $file
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Upload de vídeo mobile recebido. Processamento em background iniciado.');
    }

    public function test_section_status_returns_json()
    {
        $section = Section::create([
            'slug' => 'test-status-' . uniqid(),
            'processing_status' => 'done',
            'mobile_processing_status' => 'processing'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get("/admin/sections/{$section->id}/status");

        $response->assertOk();
        $response->assertJson([
            'status' => 'done',
            'error' => null,
            'mobile_status' => 'processing',
            'mobile_error' => null
        ]);
    }
}
