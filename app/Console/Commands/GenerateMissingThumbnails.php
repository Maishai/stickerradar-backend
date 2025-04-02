<?php

namespace App\Console\Commands;

use App\Services\StickerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateMissingThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-missing-thumbnails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate thumbnails for stickers that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(StickerService $stickerService)
    {
        $this->info('Scanning for stickers without thumbnails...');

        $files = Storage::disk('public')->files('stickers');

        // Filter out the thumbnails directory itself
        $stickerFiles = array_filter($files, function ($file) {
            return ! Str::startsWith($file, 'stickers/thumbnails/');
        });

        $this->info('Found '.count($stickerFiles).' sticker files to check.');

        $count = 0;
        $bar = $this->output->createProgressBar(count($stickerFiles));

        foreach ($stickerFiles as $file) {
            $filename = basename($file);

            $thumbnailPath = 'stickers/thumbnails/'.$filename;
            if (! Storage::disk('public')->exists($thumbnailPath)) {
                $filepath = Storage::disk('public')->path($file);
                $stickerService->createThumbnail($filename, $filepath);
                $count++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Generated $count missing thumbnails.");

        return Command::SUCCESS;
    }
}
