<?php

namespace Tests\Feature;

use App\Jobs\ProcessMobileVideoJob;
use App\Jobs\ProcessVideoJob;
use App\Models\Section;
use App\Models\User;
use App\Services\MediaConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class VideoUploadFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['email' => 'admin_' . uniqid() . '@318produtora.com.br', 'is_admin' => true]);
        
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_full_video_upload_flow_desktop_then_mobile()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $section = Section::create(['slug' => 'test-flow-' . uniqid(), 'processing_status' => 'done']);
        
        $desktopFile = UploadedFile::fake()->create('desktop.mp4', 1024, 'video/mp4');

        // 1. Upload Desktop
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/video", [
                'video' => $desktopFile,
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();
        
        $section->refresh();
        $this->assertEquals('pending', $section->processing_status);

        \Illuminate\Support\Facades\Bus::assertDispatched(ProcessVideoJob::class, function ($job) use ($section) {
            return $job->sectionId === $section->id;
        });

        // Mock MediaConversionService
        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionVideo')
                ->once()
                ->andReturn([
                    'video_public_id' => 'fake_public_id_desktop',
                    'video_webm_desktop' => 'videos/desktop_fake.webm',
                    'video_mp4_desktop' => 'videos/desktop_fake.mp4',
                ]);
            $mock->shouldReceive('convertSectionMobileVideo')
                ->once()
                ->andReturn([
                    'mobile_video_public_id' => 'fake_public_id_mobile',
                    'video_webm_mobile' => 'videos/mobile_fake.webm',
                    'video_mp4_mobile' => 'videos/mobile_fake.mp4',
                ]);
        });

        // Executar o job de desktop manualmente
        $jobDesktop = new ProcessVideoJob($section->id, 'fake/temp/path.mp4', $section->slug);
        $jobDesktop->handle(app(MediaConversionService::class));

        $section->refresh();
        $this->assertEquals('done', $section->processing_status);
        $this->assertEquals('videos/desktop_fake.mp4', $section->video_mp4_desktop);
        // Assert mobile still null
        $this->assertNull($section->mobile_processing_status);
        $this->assertNull($section->video_mp4_mobile);

        // 2. Upload Mobile
        $mobileFile = UploadedFile::fake()->create('mobile.mp4', 1024, 'video/mp4');

        $responseMobile = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/mobile-video", [
                'video' => $mobileFile,
            ]);

        $responseMobile->assertSessionHas('success');
        $responseMobile->assertRedirect();

        $section->refresh();
        $this->assertEquals('pending', $section->mobile_processing_status);
        $this->assertEquals('done', $section->processing_status); // Desktop intact

        \Illuminate\Support\Facades\Bus::assertDispatched(ProcessMobileVideoJob::class, function ($job) use ($section) {
            return $job->sectionId === $section->id;
        });

        // Executar o job de mobile manualmente
        $jobMobile = new ProcessMobileVideoJob($section->id, 'fake/temp/path2.mp4', $section->slug);
        $jobMobile->handle(app(MediaConversionService::class));

        $section->refresh();
        $this->assertEquals('done', $section->mobile_processing_status);
        $this->assertEquals('videos/mobile_fake.mp4', $section->video_mp4_mobile);
        
        // Assert desktop still intact
        $this->assertEquals('done', $section->processing_status);
        $this->assertEquals('videos/desktop_fake.mp4', $section->video_mp4_desktop);
    }

    public function test_full_video_upload_flow_mobile_then_desktop()
    {
        \Illuminate\Support\Facades\Bus::fake();

        $section = Section::create(['slug' => 'test-flow-rev-' . uniqid(), 'processing_status' => 'done']);
        
        // Mock MediaConversionService
        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionMobileVideo')
                ->once()
                ->andReturn([
                    'mobile_video_public_id' => 'fake_public_id_mobile',
                    'video_webm_mobile' => 'videos/mobile_fake.webm',
                    'video_mp4_mobile' => 'videos/mobile_fake.mp4',
                ]);
            $mock->shouldReceive('convertSectionVideo')
                ->once()
                ->andReturn([
                    'video_public_id' => 'fake_public_id_desktop',
                    'video_webm_desktop' => 'videos/desktop_fake.webm',
                    'video_mp4_desktop' => 'videos/desktop_fake.mp4',
                ]);
        });

        // 1. Upload Mobile
        $mobileFile = UploadedFile::fake()->create('mobile.mp4', 1024, 'video/mp4');
        $responseMobile = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/mobile-video", [
                'video' => $mobileFile,
            ]);

        $responseMobile->assertSessionHas('success');
        $responseMobile->assertRedirect();
        
        $section->refresh();
        $this->assertEquals('pending', $section->mobile_processing_status);

        $jobMobile = new ProcessMobileVideoJob($section->id, 'fake/temp/path2.mp4', $section->slug);
        $jobMobile->handle(app(MediaConversionService::class));

        $section->refresh();
        $this->assertEquals('done', $section->mobile_processing_status);
        $this->assertEquals('videos/mobile_fake.mp4', $section->video_mp4_mobile);
        $this->assertEquals('done', $section->processing_status);

        // 2. Upload Desktop
        $desktopFile = UploadedFile::fake()->create('desktop.mp4', 1024, 'video/mp4');
        $response = $this->actingAs($this->adminUser)
            ->post("/admin/sections/{$section->id}/video", [
                'video' => $desktopFile,
            ]);

        $response->assertSessionHas('success');
        $response->assertRedirect();

        $section->refresh();
        $this->assertEquals('pending', $section->processing_status);
        $this->assertEquals('done', $section->mobile_processing_status); // Mobile intact

        $jobDesktop = new ProcessVideoJob($section->id, 'fake/temp/path.mp4', $section->slug);
        $jobDesktop->handle(app(MediaConversionService::class));

        $section->refresh();
        $this->assertEquals('done', $section->processing_status);
        $this->assertEquals('videos/desktop_fake.mp4', $section->video_mp4_desktop);
        $this->assertEquals('done', $section->mobile_processing_status);
        $this->assertEquals('videos/mobile_fake.mp4', $section->video_mp4_mobile);
    }
}
