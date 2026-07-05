<?php

namespace Tests\Unit\Services;

use App\Services\MediaConversionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;
use Cloudinary\Cloudinary;
use Cloudinary\Api\Upload\UploadApi;

class MediaConversionServiceTest extends TestCase
{
    private MediaConversionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        config(['cloudinary.cloud_url' => 'cloudinary://key:secret@mycloud']);
    }

    private function mockCloudinary()
    {
        // Mock Cloudinary SDK using Reflection to replace the private property
        $apiResponseMock = \Mockery::mock(\Cloudinary\Api\ApiResponse::class);
        $apiResponseMock->shouldReceive('offsetGet')->with('public_id')->andReturn('videos/my_video');

        $uploadApiMock = \Mockery::mock(UploadApi::class);
        $uploadApiMock->shouldReceive('upload')->andReturn($apiResponseMock);

        $cloudinaryMock = \Mockery::mock(Cloudinary::class);
        $cloudinaryMock->shouldReceive('uploadApi')->andReturn($uploadApiMock);

        $this->service = new MediaConversionService();
        
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('cloudinary');
        $property->setAccessible(true);
        $property->setValue($this->service, $cloudinaryMock);
    }

    public function test_convert_section_video_urls()
    {
        $this->mockCloudinary();
        Storage::disk('local')->put('temp.mp4', 'dummy');

        Http::fake([
            '*' => function() { return Http::response('fake_video_content', 200); },
        ]);

        $result = $this->service->convertSectionVideo('temp.mp4', 'base_name');

        $this->assertEquals('videos/my_video', $result['video_public_id']);
        $this->assertStringContainsString('videos/base_name_video_webm_desktop.webm', $result['video_webm_desktop']);
        $this->assertStringContainsString('videos/base_name_video_mp4_desktop.mp4', $result['video_mp4_desktop']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            return str_contains($url, 'w_1920,h_1080,c_fill') && !str_contains($url, 'mobile');
        });
    }

    public function test_convert_section_mobile_video_urls()
    {
        $this->mockCloudinary();
        Storage::disk('local')->put('temp_mobile.mp4', 'dummy');

        Http::fake([
            '*' => function() { return Http::response('fake_video_content', 200); },
        ]);

        $result = $this->service->convertSectionMobileVideo('temp_mobile.mp4', 'base_name');

        $this->assertEquals('videos/my_video', $result['mobile_video_public_id']);
        $this->assertStringContainsString('videos/base_name_video_webm_mobile.webm', $result['video_webm_mobile']);
        $this->assertStringContainsString('videos/base_name_video_mp4_mobile.mp4', $result['video_mp4_mobile']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            return str_contains($url, 'w_1080,c_limit') && !str_contains($url, 'desktop');
        });
    }

    public function test_download_fails_immediately_on_423_status()
    {
        $this->mockCloudinary();
        Storage::disk('local')->put('temp_mobile.mp4', 'dummy');

        Http::fake([
            '*' => Http::response('processing', 423),
        ]);

        $result = $this->service->convertSectionMobileVideo('temp_mobile.mp4', 'base_name');

        // Because it fails immediately and we catch it to return null
        $this->assertNull($result['video_webm_mobile']);
        $this->assertNull($result['video_mp4_mobile']);
    }

    public function test_convert_footer_photo_handles_gd_avif_failure()
    {
        // This test requires a valid image for GD to decode.
        $image = UploadedFile::fake()->image('photo.jpg', 10, 10);

        $this->service = new MediaConversionService();

        // Since GD might or might not support AVIF/WEBP depending on the system, 
        // we just assert that JPG is generated successfully (which is always supported).
        $result = $this->service->convertFooterPhoto($image, 1);

        $this->assertNotNull($result['photo_jpg']);
        Storage::disk('public')->assertExists($result['photo_jpg']);
    }
}
