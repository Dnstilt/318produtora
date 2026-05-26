<?php

namespace App\Repositories;

use App\Models\SocialLink;
use Illuminate\Support\Collection;

class EloquentSocialLinkRepository implements SocialLinkRepositoryInterface
{
    public function all(): Collection
    {
        return SocialLink::query()->orderBy('id')->get();
    }

    public function find(int $id): ?SocialLink
    {
        return SocialLink::query()->find($id);
    }

    public function findByPlatform(string $platform): ?SocialLink
    {
        return SocialLink::query()->where('platform', $platform)->first();
    }

    public function upsertByPlatform(string $platform, array $attributes = []): SocialLink
    {
        $link = $this->findByPlatform($platform);

        if ($link) {
            return $this->update($link, $attributes);
        }

        return SocialLink::query()->create(array_merge(['platform' => $platform], $attributes));
    }

    public function update(SocialLink $link, array $attributes): SocialLink
    {
        $link->fill($attributes);
        $link->save();

        return $link->refresh();
    }
}

