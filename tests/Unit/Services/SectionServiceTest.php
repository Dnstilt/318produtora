<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcessVideoJob;
use App\Jobs\ProcessMobileVideoJob;
use App\Models\Section;
use App\Repositories\SectionRepositoryInterface;
use App\Services\SectionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Since we are doing a Unit/Integration test, we will resolve the service from the container
        // to get the real repository injected.
        $this->service = app(SectionService::class);
        Storage::fake('public');
        Storage::fake('local');
        Queue::fake();
    }

    public function test_update_content()
    {
        $section = Section::create([
            'slug' => 'test-home-' . uniqid(),
            'title' => 'Old Title',
            'description_text' => 'Old text',
        ]);

        $updated = $this->service->updateContent($section->id, 'New Title', 'New text');

        $this->assertEquals('New Title', $updated->title);
        $this->assertEquals('New text', $updated->description_text);
        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'title' => 'New Title',
            'description_text' => 'New text',
        ]);
    }

    public function test_enqueue_video_processing_throws_exception_if_already_processing()
    {
        $section = Section::create([
            'slug' => 'test-1-' . uniqid(),
            'processing_status' => 'processing',
        ]);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Já existe um vídeo sendo processado para esta seção');

        $this->service->enqueueVideoProcessing($section->id, $file);
    }

    public function test_enqueue_video_processing_deletes_only_desktop_files()
    {
        $slug = 'test_slug_' . uniqid();
        $section = Section::create(['slug' => $slug, 'processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        Storage::disk('public')->put("videos/desktop_{$slug}_1.webm", "content");
        Storage::disk('public')->put("videos/{$slug}_legacy.mp4", "content");
        Storage::disk('public')->put("videos/mobile_{$slug}_1.webm", "content");

        $this->service->enqueueVideoProcessing($section->id, $file);

        Storage::disk('public')->assertMissing("videos/desktop_{$slug}_1.webm");
        Storage::disk('public')->assertMissing("videos/{$slug}_legacy.mp4");
        Storage::disk('public')->assertExists("videos/mobile_{$slug}_1.webm"); // Must exist!
    }

    public function test_enqueue_video_processing_dispatches_job()
    {
        $section = Section::create(['slug' => 'test_slug_' . uniqid(), 'processing_status' => 'error']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $this->service->enqueueVideoProcessing($section->id, $file);

        Queue::assertPushed(ProcessVideoJob::class, function ($job) use ($section) {
            return $job->sectionId === $section->id;
        });

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'processing_status' => 'pending',
            'processing_error' => null,
        ]);
    }

    public function test_enqueue_mobile_video_processing_deletes_only_mobile_files()
    {
        $slug = 'test_slug_' . uniqid();
        $section = Section::create(['slug' => $slug, 'mobile_processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        Storage::disk('public')->put("videos/desktop_{$slug}_1.webm", "content");
        Storage::disk('public')->put("videos/mobile_{$slug}_1.webm", "content");

        $this->service->enqueueMobileVideoProcessing($section->id, $file);

        Storage::disk('public')->assertMissing("videos/mobile_{$slug}_1.webm");
        Storage::disk('public')->assertExists("videos/desktop_{$slug}_1.webm"); // Must exist!
    }

    public function test_enqueue_mobile_video_processing_dispatches_job()
    {
        $section = Section::create(['slug' => 'test_slug_' . uniqid(), 'mobile_processing_status' => 'done']);
        $file = UploadedFile::fake()->create('video.mp4', 1000, 'video/mp4');

        $this->service->enqueueMobileVideoProcessing($section->id, $file);

        Queue::assertPushed(ProcessMobileVideoJob::class, function ($job) use ($section) {
            return $job->sectionId === $section->id;
        });

        $this->assertDatabaseHas('sections', [
            'id' => $section->id,
            'mobile_processing_status' => 'pending',
            'mobile_processing_error' => null,
        ]);
    }

    public function test_status_returns_correct_format()
    {
        $section = Section::create([
            'slug' => 'test-status',
            'processing_status' => 'done',
            'processing_error' => null,
            'mobile_processing_status' => null, // Never received mobile video
            'mobile_processing_error' => null,
        ]);

        $status = $this->service->status($section->id);

        $this->assertEquals([
            'status' => 'done',
            'error' => null,
            'mobile_status' => null,
            'mobile_error' => null,
        ], $status);
    }
}
