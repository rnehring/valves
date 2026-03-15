<?php

namespace App\Providers;

use App\Services\EpicorService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register EpicorService as a singleton so the ODBC connection is reused
        $this->app->singleton(EpicorService::class, function ($app) {
            return new EpicorService();
        });
    }

    public function boot(): void
    {
        // Use Tailwind CSS pagination views everywhere
        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');

        // Share common session data with all views
        view()->composer('*', function ($view) {
            $view->with([
                'sessionUser' => session('user'),
                'sessionCompany' => session('session_company'),
                'sessionCompanyId' => session('session_companyId'),
            ]);
        });
    }
}
