<?php

namespace App\Services;

use App\Models\SocialLink;
use App\Repositories\SocialLinkRepositoryInterface;
use Illuminate\Support\Facades\Log;

class SocialLinkService
{
    public function __construct(
        private readonly SocialLinkRepositoryInterface $links,
    ) {
    }

    public function ensureDefaultsExist(): void
    {
        foreach ($this->defaultPlatforms() as $platform) {
            $this->links->upsertByPlatform($platform, [
                'icon_class' => 'icon',
            ]);
        }
    }

    public function all(): array
    {
        $this->ensureDefaultsExist();

        return $this->links->all()->all();
    }

    public function updateUrl(int $id, string $url): SocialLink
    {
        $link = $this->requireLink($id);

        Log::info('social_links.update_url', [
            'social_link_id' => $id,
            'platform' => $link->platform,
            'url_host' => parse_url($url, PHP_URL_HOST),
            'url_path' => parse_url($url, PHP_URL_PATH),
        ]);

        return $this->links->update($link, ['url' => $url]);
    }

    private function requireLink(int $id): SocialLink
    {
        $link = $this->links->find($id);

        if (!$link) {
            abort(404);
        }

        return $link;
    }

    private function defaultPlatforms(): array
    {
        return [
            SocialLink::PLATFORM_INSTAGRAM,
            SocialLink::PLATFORM_VIMEO,
            SocialLink::PLATFORM_YOUTUBE,
        ];
    }
}
