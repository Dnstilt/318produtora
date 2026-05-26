<?php

namespace App\Providers;

use App\Models\FooterPhoto;
use App\Models\Page;
use App\Models\Section;
use App\Models\SocialLink;
use App\Policies\FooterPhotoPolicy;
use App\Policies\PagePolicy;
use App\Policies\SectionPolicy;
use App\Policies\SocialLinkPolicy;
use App\Repositories\EloquentFooterPhotoRepository;
use App\Repositories\EloquentPageRepository;
use App\Repositories\EloquentSectionRepository;
use App\Repositories\EloquentSocialLinkRepository;
use App\Repositories\FooterPhotoRepositoryInterface;
use App\Repositories\PageRepositoryInterface;
use App\Repositories\SectionRepositoryInterface;
use App\Repositories\SocialLinkRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SectionRepositoryInterface::class, EloquentSectionRepository::class);
        $this->app->bind(FooterPhotoRepositoryInterface::class, EloquentFooterPhotoRepository::class);
        $this->app->bind(SocialLinkRepositoryInterface::class, EloquentSocialLinkRepository::class);
        $this->app->bind(PageRepositoryInterface::class, EloquentPageRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Section::class, SectionPolicy::class);
        Gate::policy(FooterPhoto::class, FooterPhotoPolicy::class);
        Gate::policy(SocialLink::class, SocialLinkPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);

        Gate::define('viewAdmin', fn (User $user): bool => $user->isAdmin());
    }
}
