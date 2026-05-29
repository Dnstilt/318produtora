<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Format;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaConversionService
{
    public function convertSectionVideo(string $inputPath, string $slug): array
    {
        $disk = Storage::disk('public');

        $outputDir = 'videos';
        $disk->makeDirectory($outputDir);

        $desktopWebm = $outputDir . '/' . $slug . '-desktop.webm';
        $desktopMp4 = $outputDir . '/' . $slug . '-desktop.mp4';
        $mobileWebm = $outputDir . '/' . $slug . '-mobile.webm';
        $mobileMp4 = $outputDir . '/' . $slug . '-mobile.mp4';

        $ffmpegBinary = (string) config('services.ffmpeg.ffmpeg', 'ffmpeg');
        $ffprobeBinary = (string) config('services.ffmpeg.ffprobe', 'ffprobe');

        $normalizedFfmpeg = str_contains($ffmpegBinary, '\\') ? str_replace('\\', '/', $ffmpegBinary) : $ffmpegBinary;
        $normalizedFfprobe = str_contains($ffprobeBinary, '\\') ? str_replace('\\', '/', $ffprobeBinary) : $ffprobeBinary;

        Log::info('ffmpeg.binaries', [
            'ffmpeg' => $normalizedFfmpeg,
            'ffprobe' => $normalizedFfprobe,
            'ffmpeg_exists' => is_file($normalizedFfmpeg),
            'ffprobe_exists' => is_file($normalizedFfprobe),
        ]);

        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => $normalizedFfmpeg,
            'ffprobe.binaries' => $normalizedFfprobe,
            'timeout' => (int) config('services.ffmpeg.timeout', 600),
            'ffmpeg.threads' => (int) config('services.ffmpeg.threads', 0),
        ]);

        $ffprobe = $ffmpeg->getFFProbe();
        if (!$ffprobe->isValid($inputPath)) {
            throw new \RuntimeException('Arquivo de vídeo inválido.');
        }

        $maxDuration = (int) env('VIDEO_MAX_DURATION_SECONDS', 0);
        if ($maxDuration > 0) {
            $duration = (float) $ffprobe->format($inputPath)->get('duration');
            if ($duration > $maxDuration) {
                throw new \RuntimeException('Duração de vídeo acima do permitido.');
            }
        }

        $this->exportWebm($ffmpeg->open($inputPath), $disk->path($desktopWebm), 1920, 1080, 3000);
        $this->exportMp4($ffmpeg->open($inputPath), $disk->path($desktopMp4), 1920, 1080, 5000);
        $this->exportWebm($ffmpeg->open($inputPath), $disk->path($mobileWebm), 1280, 720, 1500);
        $this->exportMp4($ffmpeg->open($inputPath), $disk->path($mobileMp4), 1280, 720, 2500);

        return [
            'video_webm_desktop' => $desktopWebm,
            'video_mp4_desktop' => $desktopMp4,
            'video_webm_mobile' => $mobileWebm,
            'video_mp4_mobile' => $mobileMp4,
        ];
    }

    public function convertFooterPhoto(UploadedFile $file, int $photoId): array
    {
        Log::info('convertFooterPhoto: Iniciando processo', ['photoId' => $photoId, 'path' => $file->getPathname()]);

        $disk = Storage::disk('public');
        $outputDir = 'photos';
        $disk->makeDirectory($outputDir);

        $avifPath = $outputDir.'/'.$photoId.'.avif';
        $webpPath = $outputDir.'/'.$photoId.'.webp';
        $jpgPath = $outputDir.'/'.$photoId.'.jpg';

        $manager = new ImageManager(new Driver());
        
        try {
            Log::info('convertFooterPhoto: Lendo a imagem com read()');
            $image = $manager->decodepath($file);
            
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

    private function exportWebm(Video $video, string $outputPath, int $width, int $height, int $kiloBitrate): void
    {
        $format = (new WebM())->setVideoCodec('libvpx-vp9')->setKiloBitrate($kiloBitrate);
        $format->setAdditionalParameters([
            '-an',
            '-pix_fmt',
            'yuv420p',
            '-deadline',
            'good',
            '-cpu-used',
            '4',
            '-row-mt',
            '1',
        ]);

        $video->filters()->custom('scale=' . $width . ':' . $height . ':force_original_aspect_ratio=increase,crop=' . $width . ':' . $height);
        $video->save($format, $outputPath);
    }

    private function exportMp4(Video $video, string $outputPath, int $width, int $height, int $kiloBitrate): void
    {
        $format = (new X264())->setKiloBitrate($kiloBitrate);
        $format->setAdditionalParameters([
            '-an',
            '-pix_fmt',
            'yuv420p',
            '-movflags',
            '+faststart',
            '-preset',
            'medium',
            '-profile:v',
            'high',
        ]);

        $video->filters()->custom('scale=' . $width . ':' . $height . ':force_original_aspect_ratio=increase,crop=' . $width . ':' . $height);
        $video->save($format, $outputPath);
    }
}
