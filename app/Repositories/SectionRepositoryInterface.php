<?php

namespace App\Repositories;

use App\Models\Section;
use Illuminate\Support\Collection;

interface SectionRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Section;

    public function findBySlug(string $slug): ?Section;

    public function upsertBySlug(string $slug, array $attributes = []): Section;

    public function update(Section $section, array $attributes): Section;
}

