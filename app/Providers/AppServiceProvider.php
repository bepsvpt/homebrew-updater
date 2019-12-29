<?php

namespace App\Providers;

use App\Models\Formula;
use App\Observers\FormulaObserver;
use Github\Client;
use Github\ResultPager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Formula::observe(FormulaObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('github', function (Application $app) {
            $github = new Client;

            if ($app['config']['services.github.token']) {
                $github->authenticate($app['config']['services.github.token'], 'http_token');
            }

            return $github;
        });

        $this->app->singleton('github.pager', function (Application $app) {
            return new ResultPager($app['github']);
        });
    }
}
