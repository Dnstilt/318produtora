<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhotoRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Requests\UpdateSectionRequest;
use App\Http\Requests\UpdateSocialLinkRequest;
use App\Http\Requests\UploadSectionVideoRequest;
use App\Models\FooterPhoto;
use App\Models\Page;
use App\Models\Section;
use App\Models\SocialLink;
use App\Services\FooterPhotoService;
use App\Services\PageService;
use App\Services\SectionService;
use App\Services\SocialLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        private readonly SectionService $sections,
        private readonly FooterPhotoService $photos,
        private readonly SocialLinkService $socialLinks,
        private readonly PageService $pages,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAdmin');

        return view('admin.dashboard', [
            'sections' => $this->sections->all(),
            'photos' => $this->photos->allOrdered(),
            'socialLinks' => $this->socialLinks->all(),
            'pageRodapeTitulo' => $this->pages->findBySlug(Page::SLUG_RODAPE_TITULO),
            'pageRodapeSubtitulo' => $this->pages->findBySlug(Page::SLUG_RODAPE_SUBTITULO),
        ]);
    }

    public function updateSection(UpdateSectionRequest $request, int $id): RedirectResponse
    {
        Log::info('admin.section.update.request', [
            'user_id' => $request->user()?->id,
            'section_id' => $id,
            'ip' => $request->ip(),
            'content_length' => is_string($request->input('description_text')) ? strlen((string) $request->input('description_text')) : null,
        ]);

        try {
            $section = Section::query()->findOrFail($id);
            $this->authorize('update', $section);

            $validated = $request->validated();
            $this->sections->updateContent($id, $validated['title'] ?? null, (string) ($validated['description_text'] ?? ''));

            Log::info('admin.section.update.success', [
                'user_id' => $request->user()?->id,
                'section_id' => $id,
            ]);

            return redirect('/admin')->with('success', 'Seção atualizada.');
        } catch (\Throwable $e) {
            Log::error('admin.section.update.error', [
                'user_id' => $request->user()?->id,
                'section_id' => $id,
                'exception' => $e,
            ]);
            return back()->with('error', 'Não foi possível salvar o texto desta seção. Tente novamente.');
        }
    }

    public function uploadSectionVideo(UploadSectionVideoRequest $request, int $id): RedirectResponse
    {
        $file = $request->file('video');

        Log::info('admin.section.video.request', [
            'user_id' => $request->user()?->id,
            'section_id' => $id,
            'ip' => $request->ip(),
            'original_name' => $file?->getClientOriginalName(),
            'mime' => $file?->getClientMimeType(),
            'size' => $file?->getSize(),
        ]);

        try {
            $section = Section::query()->findOrFail($id);
            $this->authorize('update', $section);

            $this->sections->enqueueVideoProcessing($id, $file);

            Log::info('admin.section.video.success', [
                'user_id' => $request->user()?->id,
                'section_id' => $id,
            ]);

            return back()->with('success', 'Upload recebido. Processamento em background iniciado.');
        } catch (\Throwable $e) {
            Log::error('admin.section.video.error', [
                'user_id' => $request->user()?->id,
                'section_id' => $id,
                'exception' => $e,
            ]);
            
            // Dar feedback mais específico baseado no tipo de erro
            $errorMessage = 'Não foi possível receber o vídeo. ';
            
            if (str_contains($e->getMessage(), 'Já existe um vídeo sendo processado')) {
                $errorMessage .= 'Já existe um vídeo sendo processado para esta seção. Aguarde a conclusão antes de enviar outro.';
            } elseif (str_contains($e->getMessage(), 'required')) {
                $errorMessage .= 'O campo de vídeo é obrigatório.';
            } elseif (str_contains($e->getMessage(), 'mimes')) {
                $errorMessage .= 'Formato de arquivo não suportado. Use: mp4, webm, mov, mkv, avi.';
            } elseif (str_contains($e->getMessage(), 'max')) {
                $errorMessage .= 'Arquivo muito grande. Limite: 500MB.';
            } else {
                $errorMessage .= 'Verifique o arquivo e tente novamente.';
            }
            
            return back()->with('error', $errorMessage);
        }
    }

    public function sectionStatus(int $id): JsonResponse
    {
        return response()->json($this->sections->status($id));
    }

    public function storePhoto(StorePhotoRequest $request): RedirectResponse|JsonResponse
    {
        Log::info('admin.photos.upload.start', ['is_json' => $request->expectsJson() || $request->isJson()]);
        
        if ($request->expectsJson() || $request->isJson()) {
            Log::info('admin.photos.reorder.request', [
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'orders_count' => is_array($request->input('orders')) ? count($request->input('orders')) : null,
            ]);

            try { 
                $this->authorize('create', FooterPhoto::class);
                $this->photos->reorder($request->validated()['orders']);

                Log::info('admin.photos.reorder.success', [
                    'user_id' => $request->user()?->id,
                ]);

                return response()->json(['ok' => true]);
            } catch (\Throwable $e) {
                Log::error('admin.photos.reorder.error', [
                    'user_id' => $request->user()?->id,
                    'exception' => $e,
                ]);
                return response()->json(['ok' => false, 'message' => 'Não foi possível reordenar as fotos.'], 500);
            }
        }

        $file = $request->file('photo');
        $title = (string) $request->input('title', '');

        if (!$file || !$file->isValid()) {
            Log::error('admin.photos.upload.invalid', [
                'user_id' => $request->user()?->id,
                'error_code' => $file ? $file->getError() : 'no_file',
                'error_message' => $file ? $file->getErrorMessage() : 'Nenhum arquivo recebido',
            ]);
            return back()->with('error', 'Arquivo de foto inválido ou não recebido.');
        }

        try {
            $this->authorize('create', FooterPhoto::class);
            Log::info('admin.photos.upload.authorized');
            
            $this->photos->storeUploaded($file, $title);

            Log::info('admin.photos.upload.success', [
                'user_id' => $request->user()?->id,
            ]);

            return back()->with('success', 'Foto adicionada.');
        } catch (\Throwable $e) {
            Log::error('admin.photos.upload.error', [
                'user_id' => $request->user()?->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $message = $e->getMessage();
            if (str_contains($message, 'limite de 8 fotos atingido')) {
                return back()->with('error', 'limite de 8 fotos atingido, subistitua uma das atuais');
            }
            return back()->with('error', 'Não foi possível adicionar a foto. Erro: ' . $message);
        }
    }

    public function deletePhoto(int $id): RedirectResponse|JsonResponse
    {
        Log::info('admin.photos.delete.request', [
            'user_id' => request()->user()?->id,
            'photo_id' => $id,
            'ip' => request()->ip(),
            'expects_json' => request()->expectsJson(),
        ]);

        try {
            $photo = FooterPhoto::query()->findOrFail($id);
            $this->authorize('delete', $photo);

            Log::info('admin.photos.delete.start', [
                'user_id' => request()->user()?->id,
                'photo_id' => $id,
            ]);

            $this->photos->delete($id);

            Log::info('admin.photos.delete.success', [
                'user_id' => request()->user()?->id,
                'photo_id' => $id,
            ]);

            if (request()->expectsJson()) {
                return response()->json(['ok' => true, 'id' => $id]);
            }

            return back()->with('success', 'Foto removida.');
        } catch (\Throwable $e) {
            Log::error('admin.photos.delete.error', [
                'user_id' => request()->user()?->id,
                'photo_id' => $id,
                'exception' => $e,
            ]);

            if (request()->expectsJson()) {
                if ($e instanceof AuthorizationException) {
                    return response()->json(['ok' => false, 'message' => 'Sem permissão para excluir esta foto.'], 403);
                }
                if ($e instanceof ModelNotFoundException) {
                    return response()->json(['ok' => false, 'message' => 'Foto não encontrada.'], 404);
                }

                return response()->json(['ok' => false, 'message' => 'Não foi possível remover a foto.'], 500);
            }

            return back()->with('error', 'Não foi possível remover a foto. Tente novamente.');
        }
    }

    public function updateSocialLink(UpdateSocialLinkRequest $request, int $id): RedirectResponse
    {
        $url = (string) $request->input('url');

        Log::info('admin.social_links.update.request', [
            'user_id' => $request->user()?->id,
            'social_link_id' => $id,
            'ip' => $request->ip(),
            'url_host' => parse_url($url, PHP_URL_HOST),
            'url_path' => parse_url($url, PHP_URL_PATH),
        ]);

        try {
            $link = SocialLink::query()->findOrFail($id);
            $this->authorize('update', $link);

            $this->socialLinks->updateUrl($id, $url);

            Log::info('admin.social_links.update.success', [
                'user_id' => $request->user()?->id,
                'social_link_id' => $id,
            ]);

            return back()->with('success', 'Link social atualizado.');
        } catch (\Throwable $e) {
            Log::error('admin.social_links.update.error', [
                'user_id' => $request->user()?->id,
                'social_link_id' => $id,
                'exception' => $e,
            ]);
            return back()->with('error', 'Não foi possível salvar este link. Verifique a URL e tente novamente.');
        }
    }

    public function updatePage(UpdatePageRequest $request, string $slug): RedirectResponse
    {
        if (!in_array($slug, [Page::SLUG_RODAPE_TITULO, Page::SLUG_RODAPE_SUBTITULO], true)) {
            abort(404);
        }

        $content = (string) $request->input('content', '');

        Log::info('admin.pages.update.request', [
            'user_id' => $request->user()?->id,
            'slug' => $slug,
            'ip' => $request->ip(),
            'content_length' => strlen($content),
        ]);

        try {
            $this->authorize('viewAdmin');

            $this->pages->updateBySlug($slug, $content);

            Log::info('admin.pages.update.success', [
                'user_id' => $request->user()?->id,
                'slug' => $slug,
            ]);

            return back()->with('success', 'Página atualizada.');
        } catch (\Throwable $e) {
            Log::error('admin.pages.update.error', [
                'user_id' => $request->user()?->id,
                'slug' => $slug,
                'exception' => $e,
            ]);
            return back()->with('error', 'Não foi possível salvar a página. Tente novamente.');
        }
    }
}
