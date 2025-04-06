# StickerRadar Backend

This Backend uses Laravel 12.

## Setup

Get started by installing PHP, Laravel, Composer and Node.js using the [Docs](https://laravel.com/docs/12.x/installation).
To setup all dependencies, type `composer run setup`.
Perfect, now you can start the backend by typing `composer run dev`.

## Example Requests

```bash
curl -X POST \
    -H "Accept: application/json" \
    -H "Content-Type: multipart/form-data" \
    -F "lat=40.7121" \
    -F "lon=70.1212" \
    -F "image=@/home/simon/Bilder/Wallpapers/bryggen.jpg" \
    -F "tags[]=0195f7eb-7b39-709b-8d64-f1a19e00c228" \
    -F "tags[]=0195f87c-1408-71cf-8afa-42f809530de9" \
    -F "tags[]=0195f7eb-7b36-7184-8c17-b1491075ea7f" \
    "http://localhost:8000/api/stickers"
```

## Troubleshooting

### Filesize Limits

If you encounter problems uploading images, you might need to adjust your _upload_max_filesize_ in your `php.ini`.
You can find the file with `php --ini`.

### Stored Files can't be accessed

You need to link your storage by executing `php artisan storage:link` to create a symlink to your local storage.
