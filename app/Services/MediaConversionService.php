<?php

namespace App\Services;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaConversionService
{
    public function convertSectionVideo(string $inputPath, string $slug): array
    {
        $disk = Storage::disk('public');

        $outputDir = 'videos';
        $disk->makeDirectory($outputDir);

        $desktopWebm = $outputDir.'/'.$slug.'-desktop.webm';
        $desktopMp4 = $outputDir.'/'.$slug.'-desktop.mp4';
        $mobileWebm = $outputDir.'/'.$slug.'-mobile.webm';
        $mobileMp4 = $outputDir.'/'.$slug.'-mobile.mp4';

        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries' => env('FFMPEG_BINARY', 'ffmpeg'),
            'ffprobe.binaries' => env('FFPROBE_BINARY', 'ffprobe'),
            'timeout' => (int) env('FFMPEG_TIMEOUT', 600),
            'ffmpeg.threads' => (int) env('FFMPEG_THREADS', 0),
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
        $disk = Storage::disk('public');
        $outputDir = 'photos';
        $disk->makeDirectory($outputDir);

        $avifPath = $outputDir.'/'.$photoId.'.avif';
        $webpPath = $outputDir.'/'.$photoId.'.webp';
        $jpgPath = $outputDir.'/'.$photoId.'.jpg';

        $driver = env('INTERVENTION_IMAGE_DRIVER', 'gd');
        $manager = ImageManager::usingDriver($driver);

        $image = $manager->decode($file->getPathname())->orient()->cover(1200, 800);

        $disk->put($avifPath, (string) $image->encodeUsingFileExtension('avif', 75));
        $disk->put($webpPath, (string) $image->encodeUsingFileExtension('webp', 82));
        $disk->put($jpgPath, (string) $image->encodeUsingFileExtension('jpg', 85));

        $optimizer = OptimizerChainFactory::create();
        $optimizer->optimize($disk->path($avifPath));
        $optimizer->optimize($disk->path($webpPath));
        $optimizer->optimize($disk->path($jpgPath));

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
            '-pix_fmt', 'yuv420p',
            '-deadline', 'good',
            '-cpu-used', '4',
            '-row-mt', '1',
        ]);

        $video->filters()->custom('scale='.$width.':'.$height.':force_original_aspect_ratio=increase,crop='.$width.':'.$height);
        $video->save($format, $outputPath);
    }

    private function exportMp4(Video $video, string $outputPath, int $width, int $height, int $kiloBitrate): void
    {
        $format = (new X264())->setKiloBitrate($kiloBitrate);
        $format->setAdditionalParameters([
            '-an',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            '-preset', 'medium',
            '-profile:v', 'high',
        ]);

        $video->filters()->custom('scale='.$width.':'.$height.':force_original_aspect_ratio=increase,crop='.$width.':'.$height);
        $video->save($format, $outputPath);
    }
}
