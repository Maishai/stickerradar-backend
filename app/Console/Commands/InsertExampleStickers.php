<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Services\StickerService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class InsertExampleStickers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:insert-example-stickers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert some example stickers';

    /**
     * Execute the console command.
     */
    public function handle(StickerService $stickerService)
    {
        $this->info('Processing antinazi.jpeg');
        $path1 = storage_path('example-images/antinazi.jpeg');
        $file1 = new UploadedFile(
            $path1,
            'antinazi.jpeg',
            File::mimeType($path1),
            null,
            true
        );

        $coordinates1 = $stickerService->extractCoordinatesFromExif($file1);

        $tagIds1 = [Tag::where('name', 'Politik')->first()->id];

        $sticker1 = $stickerService->createSticker(
            $coordinates1,
            $file1,
            $tagIds1
        );
        $this->info('Created sticker ID: '.$sticker1->id);

        $this->info('Processing frauenkampf.jpeg');
        $path2 = storage_path('example-images/frauenkampf.jpeg');
        $file2 = new UploadedFile(
            $path2,
            'frauenkampf.jpeg',
            File::mimeType($path2),
            null,
            true
        );

        $coordinates2 = $stickerService->extractCoordinatesFromExif($file2);

        $tagIds2 = [Tag::where('name', 'Links')->first()->id];

        $sticker2 = $stickerService->createSticker(
            $coordinates2,
            $file2,
            $tagIds2
        );
        $this->info('Created sticker ID: '.$sticker2->id);

        $this->info('Processing polizeigewalt.jpeg');
        $path3 = storage_path('example-images/polizeigewalt.jpeg');
        $file3 = new UploadedFile(
            $path3,
            'polizeigewalt.jpeg',
            File::mimeType($path3),
            null,
            true
        );

        $coordinates3 = $stickerService->extractCoordinatesFromExif($file3);
        $tagIds3 = [Tag::where('name', 'Links')->first()->id];

        $sticker3 = $stickerService->createSticker(
            $coordinates3,
            $file3,
            $tagIds3
        );
        $this->info('Created sticker ID: '.$sticker3->id);

        $this->info('Processing fussball.jpeg');
        $path4 = storage_path('example-images/fussball.jpeg');
        $file4 = new UploadedFile(
            $path4,
            'fussball.jpeg',
            File::mimeType($path4),
            null,
            true
        );
        $coordinates4 = $stickerService->extractCoordinatesFromExif($file4);
        $tagIds4 = [Tag::where('name', 'Fußball')->first()->id];
        $sticker4 = $stickerService->createSticker(
            $coordinates4,
            $file4,
            $tagIds4
        );
        $this->info('Created sticker ID: '.$sticker4->id);

        $this->info('All example stickers inserted successfully!');

        return 0;
    }
}
