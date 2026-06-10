<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Format;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaConversionService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary((string) config('cloudinary.cloud_url'));
    }

    public function convertSectionVideo(string $tempPath, string $baseName): array
    {
        $absoluteTempPath = Storage::disk('local')->path($tempPath);
        if (!file_exists($absoluteTempPath)) {
            throw new \RuntimeException("Arquivo não encontrado: {$absoluteTempPath}");
        }
        Log::info('Cloudinary upload iniciado', ['path' => $absoluteTempPath]);

        $result = $this->cloudinary->uploadApi()->upload($absoluteTempPath, [
            'resource_type' => 'video',
            'public_id'     => $baseName,
            'folder'        => 'videos',
            'overwrite'     => true,
            'chunk_size'    => 6000000,
        ]);
        Storage::disk('local')->delete($tempPath);
        $publicId = $result['public_id'];
        $cloudName = config('cloudinary.cloud_url') ? parse_url(config('cloudinary.cloud_url'), PHP_URL_HOST) : env('CLOUDINARY_CLOUD_NAME');
        Log::info('Cloudinary upload finalizado', ['public_id' => $publicId]);

        $disk = Storage::disk('public');
        $disk->makeDirectory('videos');
        $variants = [
            'video_webm_desktop' => "https://res.cloudinary.com/{$cloudName}/video/upload/w_1920,h_1080,c_fill,vc_vp9,q_auto/{$publicId}.webm",
            'video_mp4_desktop'  => "https://res.cloudinary.com/{$cloudName}/video/upload/w_1920,h_1080,c_fill,vc_h264,q_auto/{$publicId}.mp4",
            'video_webm_mobile'  => "https://res.cloudinary.com/{$cloudName}/video/upload/w_768,h_1280,c_fill,vc_vp9,q_auto/{$publicId}.webm",
            'video_mp4_mobile'   => "https://res.cloudinary.com/{$cloudName}/video/upload/w_768,h_1280,c_fill,vc_h264,q_auto/{$publicId}.mp4",
        ];
        $localPaths = [];
        foreach ($variants as $key => $url) {
            $filename = "videos/{$baseName}_{$key}." . (str_contains($key, 'webm') ? 'webm' : 'mp4');
            $contents = file_get_contents($url);
            if ($contents !== false) {
                $disk->put($filename, $contents);
                $localPaths[$key] = $filename;
                Log::info("Vídeo baixado: {$filename}");
            } else {
                Log::warning("Falha ao baixar: {$url}");
                $localPaths[$key] = null;
            }
        }

        return [
            'video_public_id'    => $publicId,
            'video_webm_desktop' => $localPaths['video_webm_desktop'],
            'video_mp4_desktop'  => $localPaths['video_mp4_desktop'],
            'video_webm_mobile'  => $localPaths['video_webm_mobile'],
            'video_mp4_mobile'   => $localPaths['video_mp4_mobile'],
        ];
    }

    public function convertFooterPhoto(UploadedFile $file, int $photoId): array
    {
        Log::info('convertFooterPhoto: Iniciando processo', ['photoId' => $photoId, 'path' => $file->getPathname()]);

        $disk = Storage::disk('public');
        $outputDir = 'photos';
        $disk->makeDirectory($outputDir);

        $avifPath = $outputDir . '/' . $photoId . '.avif';
        $webpPath = $outputDir . '/' . $photoId . '.webp';
        $jpgPath = $outputDir . '/' . $photoId . '.jpg';

        $manager = new ImageManager(new Driver());

        try {
            Log::info('convertFooterPhoto: Lendo a imagem com read()');
            $image = $manager->decodePath($file->getPathname());

            Log::info('convertFooterPhoto: Aplicando cover(1200, 800)');
            $image->cover(1200, 800);
        } catch (\Throwable $e) {
            Log::error('convertFooterPhoto: Erro na leitura ou cover da imagem', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }

        try {
            Log::info('convertFooterPhoto: Tentando converter para AVIF');
            $disk->put($avifPath, (string) $image->encodeUsingFormat(Format::AVIF, 75));
        } catch (\Throwable $e) {
            Log::warning('GD Extension não suporta AVIF neste servidor, pulando formato AVIF.', ['error' => $e->getMessage()]);
            $avifPath = null;
        }

        try {
            Log::info('convertFooterPhoto: Tentando converter para WEBP');
            $disk->put($webpPath, (string) $image->encodeUsingFormat(Format::WEBP, 82));
        } catch (\Throwable $e) {
            Log::warning('GD Extension não suporta WEBP neste servidor, pulando formato WEBP.', ['error' => $e->getMessage()]);
            $webpPath = null;
        }

        try {
            Log::info('convertFooterPhoto: Tentando converter para JPEG');
            $disk->put($jpgPath, (string) $image->encodeUsingFormat(Format::JPEG, 85));
        } catch (\Throwable $e) {
            Log::error('convertFooterPhoto: Erro ao converter para JPEG', ['error' => $e->getMessage()]);
            throw $e;
        }

        try {
            Log::info('convertFooterPhoto: Iniciando Spatie Optimizer');
            $optimizer = OptimizerChainFactory::create();
            if ($avifPath) $optimizer->optimize($disk->path($avifPath));
            if ($webpPath) $optimizer->optimize($disk->path($webpPath));
            $optimizer->optimize($disk->path($jpgPath));
        } catch (\Throwable $e) {
            Log::warning('Spatie Optimizer falhou ao otimizar a imagem, mas as imagens foram salvas.', ['error' => $e->getMessage()]);
        }

        Log::info('convertFooterPhoto: Finalizado com sucesso');

        return [
            'photo_avif' => $avifPath,
            'photo_webp' => $webpPath,
            'photo_jpg' => $jpgPath,
        ];
    }
}
