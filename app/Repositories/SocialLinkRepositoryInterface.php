<?php

namespace App\Repositories;

use App\Models\SocialLink;
use Illuminate\Support\Collection;

interface SocialLinkRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?SocialLink;

    public function findByPlatform(string $platform): ?SocialLink;

    public function upsertByPlatform(string $platform, array $attributes = []): SocialLink;

    public function update(SocialLink $link, array $attributes): SocialLink;
}

