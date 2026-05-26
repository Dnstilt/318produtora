<?php

namespace App\Jobs;

use App\Repositories\SectionRepositoryInterface;
use App\Services\MediaConversionService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1200;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $sectionId,
        private readonly string $inputPath,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(SectionRepositoryInterface $sections, MediaConversionService $mediaConversion): void
    {
        $section = $sections->find($this->sectionId);

        if (!$section) {
            Log::warning('video.process.missing_section', [
                'section_id' => $this->sectionId,
                'input_file' => basename($this->inputPath),
            ]);
            $this->cleanupInput();
            return;
        }

        Log::info('video.process.start', [
            'section_id' => $this->sectionId,
            'slug' => $section->slug,
            'input_file' => basename($this->inputPath),
        ]);

        $sections->update($section, [
            'processing_status' => 'processing',
            'processing_error' => null,
        ]);

        try {
            $paths = $mediaConversion->convertSectionVideo($this->inputPath, $section->slug);

            $sections->update($section, array_merge($paths, [
                'processing_status' => 'done',
                'processing_error' => null,
            ]));

            Log::info('video.process.success', [
                'section_id' => $this->sectionId,
                'slug' => $section->slug,
                'outputs' => array_map(
                    fn ($p) => is_string($p) ? basename($p) : null,
                    $paths
                ),
            ]);
        } catch (Exception $e) {
            Log::error('Falha ao processar vídeo', [
                'section_id' => $this->sectionId,
                'input_path' => $this->inputPath,
                'exception' => $e->getMessage(),
            ]);

            $sections->update($section, [
                'processing_status' => 'error',
                'processing_error' => 'Falha no processamento.',
            ]);

            throw $e;
        } finally {
            $this->cleanupInput();
        }
    }

    private function cleanupInput(): void
    {
        if (is_file($this->inputPath)) {
            @unlink($this->inputPath);
        }
    }
}
