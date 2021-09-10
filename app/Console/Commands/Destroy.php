<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use mpyw\Cowitter\Client;

class Destroy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tm:destroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Destroy';

    /**
     * @var Client
     */
    protected $twitter;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->twitter = app(Client::class);
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws
     */
    public function handle()
    {
        $tweets = $this->twitter->get('statuses/user_timeline', ['count' => 200]);

        collect($tweets)->each(function ($tweet) {
            $res = $this->twitter->post('statuses/destroy/'.$tweet->id);

            $this->info($res->text);
        });
    }
}
