<?php

namespace App\Domains\Category\Providers;

use App\Core\Category\Repositories\CategoryGroupRepository;
use App\Core\Category\Repositories\CategoryRepository;
use App\Domains\Category\Services\CategoryGroupService;
use App\Domains\Category\Services\CategoryService;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        // Bind repositories
        $this->app->singleton(CategoryGroupRepository::class);
        $this->app->singleton(CategoryRepository::class);
        
        // Bind services
        $this->app->singleton(CategoryGroupService::class);
        $this->app->singleton(CategoryService::class);
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        //
    }
}