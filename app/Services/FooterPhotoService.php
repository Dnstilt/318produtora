<?php

namespace App\Services;

use App\Models\FooterPhoto;
use App\Repositories\FooterPhotoRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FooterPhotoService
{
    public function __construct(
        private readonly FooterPhotoRepositoryInterface $photos,
        private readonly MediaConversionService $mediaConversion,
    ) {
    }

    public function allOrdered(): array
    {
        return $this->photos->allOrdered()->all();
    }

    public function storeUploaded(UploadedFile $file): FooterPhoto
    {
        $nextOrder = $this->photos->allOrdered()->count();
        $photo = $this->photos->create(['order' => $nextOrder]);

        Log::info('photos.store_uploaded', [
            'photo_id' => $photo->id,
            'order' => $nextOrder,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        $paths = $this->mediaConversion->convertFooterPhoto($file, $photo->id);
        $photo = $this->photos->update($photo, $paths);
        
        shell_exec('rsync -a /home1/faust163/repositories/318produtora/storage/app/public/photos/ /home1/faust163/public_html/storage/photos/');

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

        Storage::disk('public')->delete([
            $photo->photo_avif,
            $photo->photo_webp,
            $photo->photo_jpg,
        ]);

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
