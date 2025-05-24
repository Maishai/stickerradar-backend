<?php

use App\Livewire\EditSticker;
use App\Livewire\EditTag;
use App\Livewire\ImageUpload;
use App\Livewire\StickerPreview;
use App\Livewire\TagsComponent;
use App\Models\Sticker;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', url('app'));

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::view('', 'dashboard')->name('dashboard');
    Route::get('tags', TagsComponent::class)->name('tags.index');
    Route::get('tags/{tag}', EditTag::class)->name('tags.edit');
    Route::get('stickers', StickerPreview::class)->name('stickers.index');
    Route::get('stickers/upload', ImageUpload::class)->name('stickers.upload');
    Route::get('stickers/{sticker}', EditSticker::class)->name('stickers.show');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::name('app')->get('/app/{any?}', function ($any = null) {
    abort_unless(
        File::exists(public_path('app/index.html')),
        503,
        'SPA entry point not found. Have you run your front‐end build? (php artisan frontend:build)'
    );
    $html = File::get(public_path('app/index.html'));

    try {
        $segments = $any ? explode('/', trim($any, '/')) : [];

        // handle url like /app/explore/{stickerId}
        if (count($segments) === 2 && $segments[0] === 'explore') {
            $stickerId = $segments[1];
            if ($sticker = Sticker::with('tags')->find($stickerId)) {
                $names = $sticker->tags->pluck('name')->toArray();

                if (count($names) > 1) {
                    $last = array_pop($names);
                    $tagList = implode(', ', $names).' und '.$last;
                    $description = 'Schau dir den Sticker mit den Tags '.e($tagList).' an!';
                } elseif (count($names) === 1) {
                    $tagList = $names[0];
                    $description = 'Schau dir den Sticker mit dem Tag '.e($tagList).' an!';
                } else {
                    $description = 'Schau dir diesen Sticker an!';
                }

                $title = e('Ein Sticker wurde mit Dir geteilt!');
                $filename = $sticker->filename;
                $thumbUrl = config('app.url').'/storage/stickers/thumbnails/'.$filename;
                $pageUrl = url("/app/explore/{$stickerId}");
                $siteName = e('StickerRadar');
                $type = e('article');

                $metaTags = "\n"
                    ."<!-- Dynamic OG tags for Sticker #{$stickerId} -->\n"
                    ."<meta property=\"og:title\"        content=\"{$title}\" />\n"
                    ."<meta property=\"og:description\"  content=\"{$description}\" />\n"
                    ."<meta property=\"og:image\"        content=\"{$thumbUrl}\" />\n"
                    ."<meta property=\"og:url\"          content=\"{$pageUrl}\" />\n"
                    ."<meta property=\"og:site_name\"    content=\"{$siteName}\" />\n"
                    ."<meta property=\"og:type\"         content=\"{$type}\" />\n"
                    ."<!-- End Dynamic OG tags -->\n";

                // Inject before </head>
                $html = str_ireplace('</head>', $metaTags.'</head>', $html);
            }
        }
    } catch (\Throwable $e) {
    }

    return response($html, 200)
        ->header('Content-Type', 'text/html');
})
    ->where('any', '^(?!.*\..*$).*$');

require __DIR__.'/auth.php';
