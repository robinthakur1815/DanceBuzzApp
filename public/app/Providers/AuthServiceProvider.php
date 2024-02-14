<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Collection::class => \App\Policies\CollectionPolicy::class,
        \App\WebPage::class => \App\Policies\WebPagePolicy::class,
        \App\WebSection::class => \App\Policies\WebSectionPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Passport::routes();
        // Passport::tokensExpireIn(now()->addDays(1));
        // Passport::refreshTokensExpireIn(now()->addDays(1));
    }
}
