<?php

namespace App\Console\Commands;

use App\Models\Sticker;
use App\Models\Tag;
use App\Services\StickerService;
use GuzzleHttp\Promise\Each;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MirrorServerData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mirror-server-data {server?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mirror data from production for local development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $defaultServer = env('PRODUCTION_URL', 'https://stickerradar.maishai.de');
        $server = $this->argument('server') ?: $defaultServer;
        if (! $this->confirm('This will delete all local sticker and tag data. Do you want to continue?')) {
            return;
        }

        $this->info("Mirroring data from: $server");

        Sticker::truncate();
        Storage::disk('public')->deleteDirectory('stickers');
        Tag::truncate();

        $tags = collect(Http::acceptJson()->get("$server/api/tags")->json('data'));
        $tags->each(function ($tag) {
            $tagModel = new Tag([
                'name' => $tag['name'],
                'super_tag' => $tag['super_tag'],
                'color' => $tag['color'],
            ]);
            $tagModel->id = $tag['id'];
            $tagModel->save();
        });

        $this->info('Mirrored '.count($tags).' tags');

        $stickers = collect(Http::acceptJson()->get("$server/api/stickers")->json('data'));
        $stickers->each(function ($sticker) {
            $stickerModel = new Sticker([
                'lat' => $sticker['lat'],
                'lon' => $sticker['lon'],
                'last_seen' => $sticker['last_seen'],
                'filename' => $sticker['filename'],
                'state' => $sticker['state'],
            ]);
            $stickerModel->id = $sticker['id'];
            $stickerModel->save();

            foreach ($sticker['tags'] as $tag) {
                $stickerModel->tags()->attach($tag);
            }
        });

        $this->info('Mirroring '.count($stickers).' sticker images and generating thumbnails. This may take a while...');

        $bar = $this->output->createProgressBar(count($stickers));

        if (! Storage::disk('public')->exists('stickers')) {
            Storage::disk('public')->makeDirectory('stickers');
        }

        Http::pool(function (Pool $pool) use ($stickers, $server, $bar) {
            return [
                Each::ofLimit(
                    (function () use ($pool, $stickers, $server, $bar) {
                        foreach ($stickers as $sticker) {
                            $filename = $sticker['filename'];
                            $imagePath = "$server/storage/stickers/$filename";

                            yield $pool
                                ->as($filename)
                                ->get($imagePath)
                                ->then(function ($response) use ($filename, $bar) {
                                    Storage::disk('public')->put("stickers/$filename", $response->body());
                                    $storedImagePath = Storage::disk('public')->path("stickers/$filename");
                                    StickerService::createThumbnail($filename, $storedImagePath);
                                    $bar->advance();
                                });
                        }
                    })(),
                    16
                ),
            ];
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done mirroring server state 🤌🏻');
    }
}
