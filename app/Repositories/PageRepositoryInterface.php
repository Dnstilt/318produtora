<?php

namespace App\Repositories;

use App\Models\Page;
use Illuminate\Support\Collection;

interface PageRepositoryInterface
{
    public function all(): Collection;

    public function findBySlug(string $slug): ?Page;

    public function upsertBySlug(string $slug, array $attributes = []): Page;

    public function update(Page $page, array $attributes): Page;
}

