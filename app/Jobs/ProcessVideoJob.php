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

        Section::where('id', $this->sectionId)->update([
            'processing_status' => 'processing',
            'processing_error' => null,
            'updated_at' => now(),
        ]);

        try {
            $paths = $mediaService->convertSectionVideo($this->tempPath, $this->baseName);

            $hasDesktop = !empty($paths['video_mp4_desktop']);

            if (!$hasDesktop) {
                throw new \RuntimeException('Nenhuma variante de vídeo foi baixada com sucesso.');
            }
            Section::where('id', $this->sectionId)->update([
                'video_public_id'    => $paths['video_public_id'],
                'video_webm_desktop' => $paths['video_webm_desktop'],
                'video_mp4_desktop'  => $paths['video_mp4_desktop'],
                'processing_status'  => 'done',
                'processing_error'   => null,
                'updated_at' => now(),
            ]);
            // Sincronizar vídeos apenas em produção
            $rsyncSource = config('services.rsync.videos.source');
            $rsyncDest = config('services.rsync.videos.dest');
            if (app()->environment('production') && $rsyncSource && $rsyncDest) {
                shell_exec(sprintf('rsync -a %s %s', escapeshellarg($rsyncSource), escapeshellarg($rsyncDest)));
            }
            
        } catch (\Throwable $e) {
            Log::error('video.process.error', [
                'section_id' => $this->sectionId,
                'exception' => $e,
            ]);

            Section::where('id', $this->sectionId)->update([
                'processing_status' => 'error',
                'processing_error' => $e->getMessage(),
                'updated_at' => now(),
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
