<?php

namespace App\Providers;

use App\Models\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        config(['mail.mailers.smtp.password' => Config::where('key', 'Email Password')->first()->value ?? null]);
    }
}
