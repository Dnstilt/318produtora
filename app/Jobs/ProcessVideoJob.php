<?php

namespace App\Jobs;

use App\Models\Section;
use App\Services\MediaConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public $maxExceptions = 1;

    public int $timeout = 600;

    public int $backoff = 30;

    public int $sectionId;
    public string $tempPath;
    public string $baseName;

    public function __construct(
        int $sectionId,
        string $tempPath,
        string $baseName,
    ) {
        $this->sectionId = $sectionId;
        $this->tempPath = $tempPath;
        $this->baseName = $baseName;
    }

    public function handle(MediaConversionService $mediaService): void
    {
        $section = Section::find($this->sectionId);
        if (!$section) {
            Log::warning('video.process.missing_section', [
                'section_id' => $this->sectionId,
                'temp_path' => $this->tempPath,
            ]);
            return;
        }

        $section->update([
            'processing_status' => 'processing',
            'processing_error' => null,
        ]);

        try {
            $paths = $mediaService->convertSectionVideo($this->tempPath, $this->baseName);

            $section->update([
                'video_public_id'    => $paths['video_public_id'],
                'video_webm_desktop' => $paths['video_webm_desktop'],
                'video_mp4_desktop'  => $paths['video_mp4_desktop'],
                'video_webm_mobile'  => $paths['video_webm_mobile'],
                'video_mp4_mobile'   => $paths['video_mp4_mobile'],
                'processing_status'  => 'done',
                'processing_error'   => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('video.process.error', [
                'section_id' => $this->sectionId,
                'exception' => $e,
            ]);

            $section->update([
                'processing_status' => 'error',
                'processing_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Section::where('id', $this->sectionId)->update([
            'processing_status' => 'error',
            'processing_error' => 'Falha após ' . $this->tries . ' tentativas: ' . $e->getMessage(),
        ]);
    }
}
