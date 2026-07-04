<?php

namespace App\Services;

use App\Jobs\ProcessVideoJob;
use App\Models\Section;
use App\Repositories\SectionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SectionService
{
    public function __construct(
        private readonly SectionRepositoryInterface $sections,
    ) {
    }

    public function ensureDefaultsExist(): void
    {
        foreach ($this->defaultSlugs() as $slug) {
            $this->sections->upsertBySlug($slug);
        }
    }

    public function all(): array
    {
        $this->ensureDefaultsExist();

        return $this->sections->all()->all();
    }

    public function updateContent(int $id, ?string $title, string $descriptionText): Section
    {
        $section = $this->requireSection($id);

        Log::info('sections.update_content', [
            'section_id' => $id,
            'slug' => $section->slug,
            'title_length' => strlen((string) $title),
            'content_length' => strlen($descriptionText),
        ]);

        return $this->sections->update($section, [
            'title' => $title,
            'description_text' => $descriptionText,
        ]);
    }

    public function enqueueVideoProcessing(int $id, UploadedFile $file): Section
    {
        $section = $this->requireSection($id);

        // Verificar se já há um job em processamento para esta seção
        if ($section->processing_status === 'processing' || $section->processing_status === 'pending') {
            throw new \Exception('Já existe um vídeo sendo processado para esta seção. Aguarde a conclusão antes de enviar outro.');
        }

        if (!$file->isValid()) {
            abort(422);
        }

        $publicDisk = Storage::disk('public');
        $videoDir = 'videos';
        try {
            $publicDisk->makeDirectory($videoDir);
            foreach ($publicDisk->files($videoDir) as $path) {
                $name = basename((string) $path);
                if (str_starts_with($name, $section->slug . '_')) {
                    $publicDisk->delete($path);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('sections.video.cleanup_failed', [
                'section_id' => $id,
                'exception' => $e->getMessage(),
            ]);
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $tmpPath = $file->storeAs('temp', uniqid('video_', true).'.'.$extension, 'local');
        $baseName = $section->slug . '_' . $section->id;

        Log::info('sections.video.enqueue', [
            'section_id' => $id,
            'slug' => $section->slug,
            'extension' => $extension,
            'tmp_path' => $tmpPath,
            'base_name' => $baseName,
            'size' => $file->getSize(),
        ]);

        $section = $this->sections->update($section, [
            'processing_status' => 'pending',
            'processing_error' => null,
        ]);

        Bus::dispatch(new ProcessVideoJob($section->id, $tmpPath, $baseName));

        return $section;
    }

    public function status(int $id): array
    {
        $section = $this->requireSection($id);

        return [
            'status' => $section->processing_status,
            'error' => $section->processing_error,
        ];
    }

    private function requireSection(int $id): Section
    {
        $section = $this->sections->find($id);

        if (!$section) {
            abort(404);
        }

        return $section;
    }

    private function defaultSlugs(): array
    {
        return [
            Section::SLUG_HOME,
            Section::SLUG_OOH,
            Section::SLUG_EVENTOS,
            Section::SLUG_OQUEMAISFAZEMOS,
        ];
    }
}
