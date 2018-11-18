<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use mpyw\Cowitter\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function ($app) {
            return new Client([
                config('twitter.CONSUMER_KEY'),
                config('twitter.CONSUMER_SECRET'),
                config('twitter.ACCESS_TOKEN'),
                config('twitter.ACCESS_TOKEN_SECRET'),
            ]);
        });
    }
}
