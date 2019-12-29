<?php

namespace App\Providers;

use App\Models\Formula;
use App\Observers\FormulaObserver;
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
}
