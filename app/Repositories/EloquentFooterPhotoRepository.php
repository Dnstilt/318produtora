<?php

namespace App\Repositories;

use App\Models\FooterPhoto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentFooterPhotoRepository implements FooterPhotoRepositoryInterface
{
    public function allOrdered(): Collection
    {
        return FooterPhoto::query()->orderBy('order')->orderBy('id')->get();
    }

    public function find(int $id): ?FooterPhoto
    {
        return FooterPhoto::query()->find($id);
    }

    public function create(array $attributes): FooterPhoto
    {
        return FooterPhoto::query()->create($attributes);
    }

    public function update(FooterPhoto $photo, array $attributes): FooterPhoto
    {
        $photo->fill($attributes);
        $photo->save();

        return $photo->refresh();
    }

    public function delete(FooterPhoto $photo): void
    {
        $photo->delete();
    }

    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach (array_values($orderedIds) as $index => $id) {
                FooterPhoto::query()->whereKey($id)->update(['order' => $index]);
            }
        });
    }
}

