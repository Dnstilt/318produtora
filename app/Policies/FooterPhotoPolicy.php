<?php

namespace App\Policies;

use App\Models\FooterPhoto;
use App\Models\User;

class FooterPhotoPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, FooterPhoto $photo): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, FooterPhoto $photo): bool
    {
        return $user->isAdmin();
    }
}

