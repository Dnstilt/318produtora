<?php

namespace App\Services;

use App\Models\FooterPhoto;
use App\Repositories\FooterPhotoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FooterPhotoService
{
    private const MAX_PHOTOS = 8;

    public function __construct(
        private readonly FooterPhotoRepositoryInterface $photos,
        private readonly MediaConversionService $mediaConversion,
    ) {
    }

    public function allOrdered(): array
    {
        return $this->photos->allOrdered()->all();
    }

    public function storeUploaded(UploadedFile $file, string $title): FooterPhoto
    {
        $nextOrder = $this->photos->allOrdered()->count();
        if ($nextOrder >= self::MAX_PHOTOS) {
            throw new \RuntimeException('limite de 8 fotos atingido, subistitua uma das atuais');
        }

        $photo = $this->photos->create([
            'order' => $nextOrder,
            'title' => $title,
        ]);

        Log::info('photos.store_uploaded', [
            'photo_id' => $photo->id,
            'order' => $nextOrder,
            'title_length' => strlen($title),
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $paths = $this->mediaConversion->convertFooterPhoto($file, $photo->id);
        $photo = $this->photos->update($photo, $paths);
        
        $rsyncSource = config('services.rsync.photos.source');
        $rsyncDest = config('services.rsync.photos.dest');
        if (app()->environment('production') && $rsyncSource && $rsyncDest) {
            shell_exec(sprintf('rsync -a %s %s', escapeshellarg($rsyncSource), escapeshellarg($rsyncDest)));
        }

        return $photo;
    }

    public function delete(int $id): void
    {
        $photo = $this->requirePhoto($id);

        Log::info('photos.delete.start', [
            'photo_id' => $id,
        ]);

        Log::info('photos.delete', [
            'photo_id' => $id,
            'paths' => [
                'avif' => $photo->photo_avif ? basename($photo->photo_avif) : null,
                'webp' => $photo->photo_webp ? basename($photo->photo_webp) : null,
                'jpg' => $photo->photo_jpg ? basename($photo->photo_jpg) : null,
            ],
        ]);

        Storage::disk('public')->delete(array_filter([
            $photo->photo_avif,
            $photo->photo_webp,
            $photo->photo_jpg,
        ]));

        $this->photos->delete($photo);

        Log::info('photos.delete.done', [
            'photo_id' => $id,
        ]);
    }

    public function reorder(array $orderedIds): void
    {
        Log::info('photos.reorder', [
            'count' => count($orderedIds),
        ]);

        $this->photos->reorder($orderedIds);
    }

    private function requirePhoto(int $id): FooterPhoto
    {
        $photo = $this->photos->find($id);

        if (!$photo) {
            abort(404);
        }

        return $photo;
    }
}
