<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessVideoJob;
use App\Models\Section;
use App\Services\MediaConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use RuntimeException;

class ProcessVideoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_processes_successfully()
    {
        $section = Section::create([
            'slug' => 'test-job-' . uniqid(),
            'processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionVideo')
                ->once()
                ->with('temp/path.mp4', 'base_name')
                ->andReturn([
                    'video_public_id' => 'videos/base_name',
                    'video_webm_desktop' => 'videos/base_name_webm.webm',
                    'video_mp4_desktop' => 'videos/base_name_mp4.mp4',
                ]);
        });

        $job = new ProcessVideoJob($section->id, 'temp/path.mp4', 'base_name');
        
        // Resolve job dependencies from container and run
        app()->call([$job, 'handle']);

        $section->refresh();

        $this->assertEquals('done', $section->processing_status);
        $this->assertNull($section->processing_error);
        $this->assertEquals('videos/base_name', $section->video_public_id);
        $this->assertEquals('videos/base_name_webm.webm', $section->video_webm_desktop);
        $this->assertEquals('videos/base_name_mp4.mp4', $section->video_mp4_desktop);
    }

    public function test_job_fails_if_no_variants_returned()
    {
        $section = Section::create([
            'slug' => 'test-job-fail-' . uniqid(),
            'processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionVideo')
                ->once()
                ->andReturn([
                    'video_public_id' => 'videos/base_name',
                    'video_webm_desktop' => null,
                    'video_mp4_desktop' => null, // empty triggers error
                ]);
        });

        $job = new ProcessVideoJob($section->id, 'temp/path.mp4', 'base_name');

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Nenhuma variante de vídeo foi baixada com sucesso.', $e->getMessage());
        }

        // State is checked after exception is caught in job
        $section->refresh();
        $this->assertEquals('error', $section->processing_status);
        $this->assertEquals('Nenhuma variante de vídeo foi baixada com sucesso.', $section->processing_error);
    }

    public function test_job_fails_on_service_exception()
    {
        $section = Section::create([
            'slug' => 'test-job-fail-exc-' . uniqid(),
            'processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionVideo')
                ->once()
                ->andThrow(new RuntimeException('Cloudinary failed'));
        });

        $job = new ProcessVideoJob($section->id, 'temp/path.mp4', 'base_name');

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Cloudinary failed', $e->getMessage());
        }

        $section->refresh();
        $this->assertEquals('error', $section->processing_status);
        $this->assertEquals('Cloudinary failed', $section->processing_error);
    }

    public function test_job_failed_method_updates_status()
    {
        $section = Section::create([
            'slug' => 'test-job-failed-method-' . uniqid(),
            'processing_status' => 'processing'
        ]);

        $job = new ProcessVideoJob($section->id, 'temp/path.mp4', 'base_name');
        
        $job->failed(new RuntimeException('Fatal error'));

        $section->refresh();
        $this->assertEquals('error', $section->processing_status);
        $this->assertStringContainsString('Falha após 1 tentativas: Fatal error', $section->processing_error);
    }
}
