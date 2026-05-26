<?php

namespace App\Services;

use App\Models\Page;
use App\Repositories\PageRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PageService
{
    public function __construct(
        private readonly PageRepositoryInterface $pages,
        private readonly HtmlSanitizerService $sanitizer,
    ) {
    }

    public function ensureDefaultsExist(): void
    {
        foreach ($this->defaultSlugs() as $slug) {
            $this->pages->upsertBySlug($slug, ['content' => '']);
        }
    }

    public function findBySlug(string $slug): ?Page
    {
        $this->ensureDefaultsExist();

        $page = $this->pages->findBySlug($slug);
        if ($page) {
            $page->content = $this->sanitizer->sanitize($page->content);
        }

        return $page;
    }

    public function updateBySlug(string $slug, string $content): Page
    {
        $page = $this->pages->upsertBySlug($slug);

        $sanitized = $this->sanitizer->sanitize($content);

        Log::info('pages.update_by_slug', [
            'slug' => $slug,
            'content_length' => strlen($content),
            'sanitized_length' => strlen($sanitized),
        ]);

        return $this->pages->update($page, ['content' => $sanitized]);
    }

    private function defaultSlugs(): array
    {
        return [
            Page::SLUG_TERMOS,
            Page::SLUG_PRIVACIDADE,
        ];
    }
}
