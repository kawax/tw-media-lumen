<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use mpyw\Cowitter\Client;
use Revolution\Google\Photos\Contracts\Factory as Photos;
use Carbon\Carbon;

class Download extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tm:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download';

    /**
     * @var Client
     */
    protected $twitter;

    /**
     * @var Photos
     */
    protected $photos;

    /**
     * @var \Illuminate\Filesystem\FilesystemManager
     */
    protected $storage;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->twitter = app(Client::class);
        $this->photos = app(Photos::class);
        $this->storage = app('filesystem');
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws
     */
    public function handle()
    {
        $options = [
            'count'            => 200,
            //            'trim_user'        => false,
            'include_entities' => true,
            //            'exclude_replies'  => true,
        ];

        if ($this->storage->disk('local')->exists('since_id')) {
            $since_id = $this->storage->disk('local')->get('since_id');
            $options['since_id'] = $since_id;
        } else {
            $since_id = 0;
        }

        info('since_id start: ' . $since_id);

        $tweets = $this->twitter->get('statuses/home_timeline', $options);

        collect($tweets)->each(function ($tweet) use (&$since_id) {
            if ($since_id < $tweet->id) {
                $since_id = $tweet->id;
            }

            if (isset($tweet->retweeted_status)) {
                return;
            }

            if (empty($tweet->extended_entities)) {
                return;
            }

            $media = $tweet->extended_entities->media;
            foreach ($media as $medium) {
                if ($medium->type === 'photo') {
                    $this->photo($medium);
                } elseif ($medium->type === 'video') {
                    $this->video($medium);
                }
            }
        });

        $this->storage->disk('local')->put('since_id', $since_id);
        info('since_id end: ' . $since_id);
    }

    /**
     * 画像.
     *
     * @param $medium
     */
    private function photo($medium)
    {
        $url = $medium->media_url_https;

        $this->download($url);
    }

    /**
     * 動画.
     *
     * @param $medium
     */
    private function video($medium)
    {
        $variants = collect($medium->video_info->variants);

        $video = $variants->reject(function ($v) {
            return empty($v->bitrate);
        })->sort(function ($v) {
            return $v->bitrate;
        })->last();

        $url = $video->url;

        $this->download($url);
    }

    /**
     * ファイルをダウンロードして保存.
     *
     * @param string $url
     */
    private function download(string $url)
    {
        info($url);

        /**
         * @var \mpyw\Cowitter\Media $response
         */
        $response = $this->twitter->getOut($url);

        $path = parse_url($url, PHP_URL_PATH);
        $file = pathinfo($path, PATHINFO_BASENAME);

        // Google Drive
        $this->storage->cloud()->put($file, $response->getBinaryString());

        //Google Photos
        $this->putPhotos($file, $response->getBinaryString());
    }

    /**
     * @param $name
     * @param $file
     */
    private function putPhotos($name, $file)
    {
        $token = [
            'access_token'  => config('photos.access_token'),
            'refresh_token' => config('photos.refresh_token'),
            'expires_in'    => 3600,
            'created'       => Carbon::now()->subDay()->getTimestamp(),
        ];

        try {
            $uploadToken = $this->photos->setAccessToken($token)->upload($name, $file);

            $this->photos->batchCreate([$uploadToken]);
            //, config('photos.album_id')
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }
}
