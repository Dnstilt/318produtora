<?php

namespace App\Repositories;

use App\Models\Page;
use Illuminate\Support\Collection;

class EloquentPageRepository implements PageRepositoryInterface
{
    public function all(): Collection
    {
        return Page::query()->orderBy('id')->get();
    }

    public function findBySlug(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->first();
    }

    public function upsertBySlug(string $slug, array $attributes = []): Page
    {
        $page = $this->findBySlug($slug);

        if ($page) {
            return $this->update($page, $attributes);
        }

        return Page::query()->create(array_merge(['slug' => $slug], $attributes));
    }

    public function update(Page $page, array $attributes): Page
    {
        $page->fill($attributes);
        $page->save();

        return $page->refresh();
    }
}

