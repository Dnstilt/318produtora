<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessMobileVideoJob;
use App\Models\Section;
use App\Services\MediaConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use RuntimeException;

class ProcessMobileVideoJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_job_processes_successfully()
    {
        $section = Section::create([
            'slug' => 'test-mobile-job-' . uniqid(),
            'mobile_processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionMobileVideo')
                ->once()
                ->with('temp/mobile.mp4', 'base_name_mobile')
                ->andReturn([
                    'mobile_video_public_id' => 'videos/base_name_mobile',
                    'video_webm_mobile' => 'videos/base_name_mobile_webm.webm',
                    'video_mp4_mobile' => 'videos/base_name_mobile_mp4.mp4',
                ]);
        });

        $job = new ProcessMobileVideoJob($section->id, 'temp/mobile.mp4', 'base_name_mobile');
        
        app()->call([$job, 'handle']);

        $section->refresh();

        $this->assertEquals('done', $section->mobile_processing_status);
        $this->assertNull($section->mobile_processing_error);
        $this->assertEquals('videos/base_name_mobile', $section->mobile_video_public_id);
        $this->assertEquals('videos/base_name_mobile_webm.webm', $section->video_webm_mobile);
        $this->assertEquals('videos/base_name_mobile_mp4.mp4', $section->video_mp4_mobile);
    }

    public function test_mobile_job_fails_if_no_variants_returned()
    {
        $section = Section::create([
            'slug' => 'test-mobile-fail-' . uniqid(),
            'mobile_processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionMobileVideo')
                ->once()
                ->andReturn([
                    'mobile_video_public_id' => 'videos/base_name_mobile',
                    'video_webm_mobile' => null,
                    'video_mp4_mobile' => null, // empty triggers error
                ]);
        });

        $job = new ProcessMobileVideoJob($section->id, 'temp/mobile.mp4', 'base_name_mobile');

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Nenhuma variante de vídeo mobile foi baixada com sucesso.', $e->getMessage());
        }

        $section->refresh();
        $this->assertEquals('error', $section->mobile_processing_status);
        $this->assertEquals('Nenhuma variante de vídeo mobile foi baixada com sucesso.', $section->mobile_processing_error);
    }

    public function test_mobile_job_fails_on_service_exception()
    {
        $section = Section::create([
            'slug' => 'test-mobile-fail-exc-' . uniqid(),
            'mobile_processing_status' => 'pending'
        ]);

        $this->mock(MediaConversionService::class, function (MockInterface $mock) {
            $mock->shouldReceive('convertSectionMobileVideo')
                ->once()
                ->andThrow(new RuntimeException('Cloudinary failed mobile'));
        });

        $job = new ProcessMobileVideoJob($section->id, 'temp/mobile.mp4', 'base_name_mobile');

        try {
            app()->call([$job, 'handle']);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (RuntimeException $e) {
            $this->assertEquals('Cloudinary failed mobile', $e->getMessage());
        }

        $section->refresh();
        $this->assertEquals('error', $section->mobile_processing_status);
        $this->assertEquals('Cloudinary failed mobile', $section->mobile_processing_error);
    }

    public function test_mobile_job_failed_method_updates_status()
    {
        $section = Section::create([
            'slug' => 'test-mobile-failed-method-' . uniqid(),
            'mobile_processing_status' => 'processing'
        ]);

        $job = new ProcessMobileVideoJob($section->id, 'temp/mobile.mp4', 'base_name_mobile');
        
        $job->failed(new RuntimeException('Fatal error'));

        $section->refresh();
        $this->assertEquals('error', $section->mobile_processing_status);
        $this->assertStringContainsString('Falha após 1 tentativas: Fatal error', $section->mobile_processing_error);
    }
}
