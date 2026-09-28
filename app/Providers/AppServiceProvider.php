<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Organization::class, OrganizationPolicy::class);

        if ($this->app->isLocal()) {
            DevCommands::node('dev:ssr', 'ssr-build');
            DevCommands::artisan('inertia:start-ssr', 'ssr');
        }

        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
