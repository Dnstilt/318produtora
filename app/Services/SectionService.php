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

    public function updateDescription(int $id, string $descriptionText): Section
    {
        $section = $this->requireSection($id);

        Log::info('sections.update_description', [
            'section_id' => $id,
            'slug' => $section->slug,
            'content_length' => strlen($descriptionText),
        ]);

        return $this->sections->update($section, [
            'description_text' => $descriptionText,
        ]);
    }

    public function enqueueVideoProcessing(int $id, UploadedFile $file): Section
    {
        $section = $this->requireSection($id);

        if (!$file->isValid()) {
            abort(422);
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $tmpPath = $file->storeAs('tmp', uniqid('video_', true).'.'.$extension, 'local');
        $absoluteInputPath = Storage::disk('local')->path($tmpPath);

        Log::info('sections.video.enqueue', [
            'section_id' => $id,
            'slug' => $section->slug,
            'extension' => $extension,
            'tmp_file' => basename($absoluteInputPath),
            'size' => $file->getSize(),
        ]);

        $section = $this->sections->update($section, [
            'processing_status' => 'pending',
            'processing_error' => null,
        ]);

        Bus::dispatch(new ProcessVideoJob($section->id, $absoluteInputPath));

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
            Section::SLUG_PUBLICIDADE,
            Section::SLUG_OOH,
            Section::SLUG_DOCUMENTARIOS,
            Section::SLUG_NATUREZA,
        ];
    }
}
