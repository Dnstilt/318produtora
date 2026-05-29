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

        return view('landing.index', [
            'sections' => [
                Section::SLUG_PUBLICIDADE => $sections->get(Section::SLUG_PUBLICIDADE),
                Section::SLUG_OOH => $sections->get(Section::SLUG_OOH),
                Section::SLUG_DOCUMENTARIOS => $sections->get(Section::SLUG_DOCUMENTARIOS),
                Section::SLUG_NATUREZA => $sections->get(Section::SLUG_NATUREZA),
            ],
            'photos' => $this->photos->allOrdered(),
            'socialLinks' => $this->socialLinks->all(),
            'rodapeTitulo' => $this->pages->findBySlug(Page::SLUG_RODAPE_TITULO)?->content ?? 'Conheça nosso trabalho',
            'rodapeSubtitulo' => $this->pages->findBySlug(Page::SLUG_RODAPE_SUBTITULO)?->content ?? 'Entre em contato para mais informações.',
        ]);
    }

    public function termos(): View
    {
        return view('pages.termos', [
            'page' => $this->pages->findBySlug(Page::SLUG_TERMOS),
        ]);
    }

    public function privacidade(): View
    {
        return view('pages.privacidade', [
            'page' => $this->pages->findBySlug(Page::SLUG_PRIVACIDADE),
        ]);
    }
}
