<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use App\Integrations\IntegrationManager;
use Illuminate\Support\Facades\Route;

class IntegrationProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // NOTE: OverrideConfig middleware is defined in app/Http/Middleware and registered in app/Http/Kernel.php

        // Common routes
        Route::middleware(['web'])->group(function () {

            Route::get(
                'oauth2/{integration}/setup_auth/{company_id}',
                'App\Http\Controllers\IntegrationController@setup_auth')
                ->name('oauth2.setup_auth');

            Route::get(
                'oauth2/{integration}/token_revoke/{company_id}', 
                'App\Http\Controllers\IntegrationController@token_revoke')
                ->name('oauth2.token_revoke');

            Route::get(
                'oauth2/{integration}/token_success',
                'App\Http\Controllers\IntegrationController@token_success')
                ->name('oauth2.token_success');

            Route::get(
                'oauth2/{integration}/token_failure',
                'App\Http\Controllers\IntegrationController@token_failure')
                ->name('oauth2.token_failure');
        });

        // Per Integration Routes
        IntegrationManager::routes();

        // Per Integration Events
        IntegrationManager::events();
    }
}