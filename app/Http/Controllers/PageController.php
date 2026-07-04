<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Section;
use App\Services\FooterPhotoService;
use App\Services\PageService;
use App\Services\SectionService;
use App\Services\SocialLinkService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly SectionService $sections,
        private readonly FooterPhotoService $photos,
        private readonly SocialLinkService $socialLinks,
        private readonly PageService $pages,
    ) {
    }

    public function landing(): View
    {
        $sections = collect($this->sections->all())->keyBy('slug');

        $cloudUrl = (string) config('cloudinary.cloud_url', '');
        $cloudName = '';
        if ($cloudUrl !== '') {
            $atPos = strrpos($cloudUrl, '@');
            if ($atPos !== false) {
                $cloudName = substr($cloudUrl, $atPos + 1);
            }
        }

        return view('landing.index', [
            'sections' => [
                Section::SLUG_HOME => $sections->get(Section::SLUG_HOME),
                Section::SLUG_OOH => $sections->get(Section::SLUG_OOH),
                Section::SLUG_EVENTOS => $sections->get(Section::SLUG_EVENTOS),
                Section::SLUG_OQUEMAISFAZEMOS => $sections->get(Section::SLUG_OQUEMAISFAZEMOS),
            ],
            'photos' => collect($this->photos->allOrdered()),
            'socialLinks' => $this->socialLinks->all(),
            'fotosTitulo' => $this->pages->findBySlug(Page::SLUG_FOTOS_TITULO)?->content ?? 'Conheça nosso trabalho',
            'fotosSubtitulo' => $this->pages->findBySlug(Page::SLUG_FOTOS_SUBTITULO)?->content ?? 'Entre em contato para mais informações.',
            'cloudName' => $cloudName,
        ]); 
    }
}
