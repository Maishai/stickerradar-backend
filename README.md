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
    -F "tag=1" \
    "http://localhost:8000/api/stickers"
```

## Troubleshooting

### Filesize Limits

If you encounter problems uploading images, you might need to adjust your *upload_max_filesize* in `/etc/php/php.ini`.

