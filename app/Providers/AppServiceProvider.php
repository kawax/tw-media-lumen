<?php

namespace App\Providers;

use mpyw\Cowitter\Client;
use Illuminate\Support\ServiceProvider;
use Revolution\Google\Photos\Facades\Photos;
use Google_Service_PhotosLibrary_NewMediaItem as NewMediaItem;
use Google_Service_PhotosLibrary_SimpleMediaItem as SimpleMediaItem;
use Google_Service_PhotosLibrary_BatchCreateMediaItemsRequest as BatchCreateMediaItemsRequest;

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
