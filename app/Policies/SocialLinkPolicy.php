<?php

namespace App\Policies;

use App\Models\SocialLink;
use App\Models\User;

class SocialLinkPolicy
{
    public function update(User $user, SocialLink $link): bool
    {
        return $user->isAdmin();
    }
}

