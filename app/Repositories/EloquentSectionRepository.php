<?php

namespace App\Repositories;

use App\Models\Section;
use Illuminate\Support\Collection;

class EloquentSectionRepository implements SectionRepositoryInterface
{
    public function all(): Collection
    {
        return Section::query()->orderBy('id')->get();
    }

    public function find(int $id): ?Section
    {
        return Section::query()->find($id);
    }

    public function findBySlug(string $slug): ?Section
    {
        return Section::query()->where('slug', $slug)->first();
    }

    public function upsertBySlug(string $slug, array $attributes = []): Section
    {
        $section = $this->findBySlug($slug);

        if ($section) {
            return $this->update($section, $attributes);
        }

        return Section::query()->create(array_merge(['slug' => $slug], $attributes));
    }

    public function update(Section $section, array $attributes): Section
    {
        $section->fill($attributes);
        $section->save();

        return $section->refresh();
    }
}

