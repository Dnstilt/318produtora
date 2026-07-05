<?php

namespace Tests\Feature\Requests;

use App\Http\Requests\UploadSectionVideoRequest;
use App\Http\Requests\UploadSectionMobileVideoRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Requests\StorePhotoRequest;
use App\Http\Requests\UpdateSocialLinkRequest;
use App\Http\Requests\UpdatePageRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;

class FormRequestsTest extends TestCase
{
    public function test_upload_section_video_request_validation()
    {
        $request = new UploadSectionVideoRequest();
        $rules = $request->rules();

        // Pass: Valid video
        $validFile = UploadedFile::fake()->create('video.mp4', 50000, 'video/mp4');
        $validator = Validator::make(['video' => $validFile], $rules);
        $this->assertTrue($validator->passes());

        // Fail: Too large (> 100MB = 102400KB)
        $largeFile = UploadedFile::fake()->create('large.mp4', 105000, 'video/mp4');
        $validator = Validator::make(['video' => $largeFile], $rules);
        $this->assertFalse($validator->passes());

        // Fail: Invalid mime type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $validator = Validator::make(['video' => $invalidFile], $rules);
        $this->assertFalse($validator->passes());
    }

    public function test_upload_section_mobile_video_request_validation()
    {
        $request = new UploadSectionMobileVideoRequest();
        $rules = $request->rules();

        // Pass: Valid video
        $validFile = UploadedFile::fake()->create('video.mp4', 50000, 'video/mp4');
        $validator = Validator::make(['video' => $validFile], $rules);
        $this->assertTrue($validator->passes());

        // Fail: Too large (> 100MB = 102400KB)
        $largeFile = UploadedFile::fake()->create('large.mp4', 105000, 'video/mp4');
        $validator = Validator::make(['video' => $largeFile], $rules);
        $this->assertFalse($validator->passes());

        // Fail: Invalid mime type
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $validator = Validator::make(['video' => $invalidFile], $rules);
        $this->assertFalse($validator->passes());
    }

    public function test_update_section_request_validation()
    {
        $request = new UpdateSectionRequest();
        $rules = $request->rules();

        // Pass
        $validator = Validator::make(['title' => 'Test', 'description_text' => 'Content'], $rules);
        $this->assertTrue($validator->passes());

        // Pass: nullable fields
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }

    public function test_store_photo_request_validation()
    {
        $request = new StorePhotoRequest();
        $rules = $request->rules(); // Assumes non-json request by default in this context

        // Pass
        $validImage = UploadedFile::fake()->image('photo.jpg');
        $validator = Validator::make(['photo' => $validImage, 'title' => 'Test title'], $rules);
        $this->assertTrue($validator->passes());

        // Fail: not an image
        $invalidFile = UploadedFile::fake()->create('doc.pdf');
        $validator = Validator::make(['photo' => $invalidFile, 'title' => 'Test title'], $rules);
        $this->assertFalse($validator->passes());
        
        // Fail: missing title
        $validator = Validator::make(['photo' => $validImage], $rules);
        $this->assertFalse($validator->passes());
    }

    public function test_update_social_link_request_validation()
    {
        $request = new UpdateSocialLinkRequest();
        $rules = $request->rules();

        // Pass
        $validator = Validator::make(['url' => 'https://example.com'], $rules);
        $this->assertTrue($validator->passes());

        // Fail: invalid url
        $validator = Validator::make(['url' => 'not-a-url'], $rules);
        $this->assertFalse($validator->passes());
    }

    public function test_update_page_request_validation()
    {
        $request = new UpdatePageRequest();
        $rules = $request->rules();

        // Pass
        $validator = Validator::make(['content' => 'Content'], $rules);
        $this->assertTrue($validator->passes());

        // Pass: nullable
        $validator = Validator::make([], $rules);
        $this->assertTrue($validator->passes());
    }
}
