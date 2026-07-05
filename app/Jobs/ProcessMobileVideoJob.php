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

class ProcessMobileVideoJob implements ShouldQueue
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
            Log::warning('mobile_video.process.missing_section', [
                'section_id' => $this->sectionId,
                'temp_path' => $this->tempPath,
            ]);
            return;
        }

        Section::where('id', $this->sectionId)->update([
            'mobile_processing_status' => 'processing',
            'mobile_processing_error' => null,
            'updated_at' => now(),
        ]);

        try {
            $paths = $mediaService->convertSectionMobileVideo($this->tempPath, $this->baseName);

            $hasMobile  = !empty($paths['video_mp4_mobile']);

            if (!$hasMobile) {
                throw new \RuntimeException('Nenhuma variante de vídeo mobile foi baixada com sucesso.');
            }
            Section::where('id', $this->sectionId)->update([
                'mobile_video_public_id' => $paths['mobile_video_public_id'],
                'video_webm_mobile'      => $paths['video_webm_mobile'],
                'video_mp4_mobile'       => $paths['video_mp4_mobile'],
                'mobile_processing_status'  => 'done',
                'mobile_processing_error'   => null,
                'updated_at' => now(),
            ]);
            // Sincronizar vídeos apenas em produção
            $rsyncSource = config('services.rsync.videos.source');
            $rsyncDest = config('services.rsync.videos.dest');
            if (app()->environment('production') && $rsyncSource && $rsyncDest) {
                shell_exec(sprintf('rsync -a %s %s', escapeshellarg($rsyncSource), escapeshellarg($rsyncDest)));
            }
            
        } catch (\Throwable $e) {
            Log::error('mobile_video.process.error', [
                'section_id' => $this->sectionId,
                'exception' => $e,
            ]);

            Section::where('id', $this->sectionId)->update([
                'mobile_processing_status' => 'error',
                'mobile_processing_error' => $e->getMessage(),
                'updated_at' => now(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Section::where('id', $this->sectionId)->update([
            'mobile_processing_status' => 'error',
            'mobile_processing_error' => 'Falha após ' . $this->tries . ' tentativas: ' . $e->getMessage(),
        ]);
    }
}
