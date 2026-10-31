<?php

namespace App\Providers;

use App\Models\Contact;
use App\Policies\ContactPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
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
        // Super-admin bypasses ALL permission checks.
        // Never assign individual permissions to super-admin — this handles everything.
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super-admin')) {
                return true;
            }
        });

        Gate::policy(Contact::class, ContactPolicy::class);

        Paginator::useBootstrapFive();
        Paginator::defaultView('pagination.admin-compact');
        Paginator::defaultSimpleView('pagination.admin-compact');
    }
}
