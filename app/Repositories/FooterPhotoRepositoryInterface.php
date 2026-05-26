<?php

namespace App\Repositories;

use App\Models\FooterPhoto;
use Illuminate\Support\Collection;

interface FooterPhotoRepositoryInterface
{
    public function allOrdered(): Collection;

    public function find(int $id): ?FooterPhoto;

    public function create(array $attributes): FooterPhoto;

    public function update(FooterPhoto $photo, array $attributes): FooterPhoto;

    public function delete(FooterPhoto $photo): void;

    public function reorder(array $orderedIds): void;
}

