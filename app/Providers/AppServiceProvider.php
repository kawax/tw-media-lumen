<?php

namespace App\Providers;

use Google\Service\PhotosLibrary\BatchCreateMediaItemsRequest;
use Google\Service\PhotosLibrary\NewMediaItem;
use Google\Service\PhotosLibrary\SimpleMediaItem;
use Illuminate\Support\ServiceProvider;
use mpyw\Cowitter\Client;
use Revolution\Google\Photos\Facades\Photos;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Photos::macro('createWithDescription',
            function ($uploadToken, $description = '') {
                $simple = new SimpleMediaItem([
                    'uploadToken' => $uploadToken,
                ]);

                $newMediaItem = new NewMediaItem([
                    'simpleMediaItem' => $simple,
                    'description'     => $description,
                ]);

                $newMediaItems = [$newMediaItem];

                $request = new BatchCreateMediaItemsRequest([
                    'newMediaItems' => $newMediaItems,
                ]);

                return $this->serviceMediaItems()->batchCreate($request)->toSimpleObject();
            });
    }

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
