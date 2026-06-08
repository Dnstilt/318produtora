<?php

namespace App\Services;

use CloudConvert\CloudConvert;
use CloudConvert\Models\Job;
use CloudConvert\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Format;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class MediaConversionService
{
    private CloudConvert $cloudConvert;

    public function __construct()
    {
        $this->cloudConvert = new CloudConvert([
            'api_key' => config('app.cloudconvert_key'),
            'sandbox' => config('app.cloudconvert_sandbox', true),
        ]);
    }

    private function requireTaskByName(Job $job, string $name): Task
    {
        $tasks = $job->getTasks();
        if (!$tasks || count($tasks) === 0) {
            throw new \RuntimeException('CloudConvert: job sem tarefas.');
        }

        foreach ($tasks as $task) {
            if ($task instanceof Task && $task->getName() === $name) {
                return $task;
            }
        }

        throw new \RuntimeException("CloudConvert: tarefa [{$name}] não encontrada.");
    }

    private function ensureJobHasNoTaskErrors(Job $job): void
    {
        $tasks = $job->getTasks() ?? [];
        foreach ($tasks as $task) {
            if (!$task instanceof Task) {
                continue;
            }

            if ($task->getStatus() === Task::STATUS_ERROR) {
                throw new \RuntimeException("CloudConvert falhou na tarefa [{$task->getName()}]: " . $task->getMessage());
            }
        }
    }

    public function convertSectionVideo(string $tempPath, string $baseName): array
    {
        $absoluteTempPath = Storage::disk('local')->path($tempPath);
        if (!file_exists($absoluteTempPath)) {
            throw new \RuntimeException("Arquivo não encontrado: {$absoluteTempPath}");
        }
        $fileSize = filesize($absoluteTempPath);
        Log::info('Arquivo para upload', [
            'path' => $absoluteTempPath,
            'exists' => file_exists($absoluteTempPath),
            'size_bytes' => $fileSize,
        ]);

        if ($fileSize === 0) {
            throw new \RuntimeException("Arquivo vazio: {$absoluteTempPath}");
        }

        $job = (new Job())
            ->addTask(
                (new Task('import/upload', 'upload-video'))
            )
            ->addTask(
                (new Task('convert', 'convert-webm-desktop'))
                    ->set('input', 'upload-video')
                    ->set('output_format', 'webm')
                    ->set('video_codec', 'libvpx-vp9')
                    ->set('width', 1920)
                    ->set('height', 1080)
                    ->set('video_bitrate', 3000)
                    ->set('no_audio', true)
                    ->set('pixel_format', 'yuv420p')
                    ->set('fit', 'max')
            )
            ->addTask(
                (new Task('convert', 'convert-mp4-desktop'))
                    ->set('input', 'upload-video')
                    ->set('output_format', 'mp4')
                    ->set('video_codec', 'libx264')
                    ->set('width', 1920)
                    ->set('height', 1080)
                    ->set('video_bitrate', 5000)
                    ->set('no_audio', true)
                    ->set('pixel_format', 'yuv420p')
                    ->set('faststart', true)
                    ->set('fit', 'max')
            )
            ->addTask(
                (new Task('convert', 'convert-webm-mobile'))
                    ->set('input', 'upload-video')
                    ->set('output_format', 'webm')
                    ->set('video_codec', 'libvpx-vp9')
                    ->set('width', 1280)
                    ->set('height', 720)
                    ->set('video_bitrate', 1500)
                    ->set('no_audio', true)
                    ->set('pixel_format', 'yuv420p')
                    ->set('fit', 'max')
            )
            ->addTask(
                (new Task('convert', 'convert-mp4-mobile'))
                    ->set('input', 'upload-video')
                    ->set('output_format', 'mp4')
                    ->set('video_codec', 'libx264')
                    ->set('width', 1280)
                    ->set('height', 720)
                    ->set('video_bitrate', 2500)
                    ->set('no_audio', true)
                    ->set('pixel_format', 'yuv420p')
                    ->set('faststart', true)
                    ->set('fit', 'max')
            )
            ->addTask(
                (new Task('export/url', 'export-webm-desktop'))
                    ->set('input', 'convert-webm-desktop')
            )
            ->addTask(
                (new Task('export/url', 'export-mp4-desktop'))
                    ->set('input', 'convert-mp4-desktop')
            )
            ->addTask(
                (new Task('export/url', 'export-webm-mobile'))
                    ->set('input', 'convert-webm-mobile')
            )
            ->addTask(
                (new Task('export/url', 'export-mp4-mobile'))
                    ->set('input', 'convert-mp4-mobile')
            );

        $job = $this->cloudConvert->jobs()->create($job);
        $uploadTask = $this->requireTaskByName($job, 'upload-video');

        $handle = fopen($absoluteTempPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Não foi possível abrir o arquivo temporário para upload.');
        }
        $this->cloudConvert->tasks()->upload($uploadTask, $handle, basename($absoluteTempPath));

        if (is_resource($handle)) {
            fclose($handle);
        }

        try {
            $this->cloudConvert->tasks()->upload($uploadTask, $handle);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $job = $this->cloudConvert->jobs()->wait($job);
        $tasks = $job->getTasks() ?? [];
        foreach ($tasks as $task) {
            if (!$task instanceof Task) continue;
            Log::info('CloudConvert task status', [
                'name'    => $task->getName(),
                'status'  => $task->getStatus(),
                'message' => $task->getMessage(),
            ]);
        }
        $this->ensureJobHasNoTaskErrors($job);

        $publicDisk = Storage::disk('public');
        $publicDisk->makeDirectory('videos');

        $exports = [
            ['task' => $this->requireTaskByName($job, 'export-webm-desktop'), 'filename' => "{$baseName}_desktop.webm", 'key' => 'video_webm_desktop'],
            ['task' => $this->requireTaskByName($job, 'export-mp4-desktop'), 'filename' => "{$baseName}_desktop.mp4", 'key' => 'video_mp4_desktop'],
            ['task' => $this->requireTaskByName($job, 'export-webm-mobile'), 'filename' => "{$baseName}_mobile.webm", 'key' => 'video_webm_mobile'],
            ['task' => $this->requireTaskByName($job, 'export-mp4-mobile'), 'filename' => "{$baseName}_mobile.mp4", 'key' => 'video_mp4_mobile'],
        ];

        $result = [];
        foreach ($exports as $export) {
            $exportTask = $export['task'];
            $filename = $export['filename'];
            $key = $export['key'];

            $files = $exportTask->getResult()->files ?? null;
            $fileUrl = $files[0]->url ?? null;
            if (!is_string($fileUrl) || $fileUrl === '') {
                throw new \RuntimeException("CloudConvert: URL de download ausente na tarefa [{$exportTask->getName()}].");
            }

            $stream = $this->cloudConvert
                ->getHttpTransport()
                ->download($fileUrl)
                ->detach();

            $relativePath = "videos/{$filename}";
            $publicDisk->put($relativePath, $stream);
            $result[$key] = $relativePath;
        }

        Storage::disk('local')->delete($tempPath);

        return [
            'video_webm_desktop' => $result['video_webm_desktop'],
            'video_mp4_desktop' => $result['video_mp4_desktop'],
            'video_webm_mobile' => $result['video_webm_mobile'],
            'video_mp4_mobile' => $result['video_mp4_mobile'],
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
