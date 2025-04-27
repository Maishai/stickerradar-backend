# StickerRadar Backend

This Backend uses Laravel 12.

## Setup

Get started by installing PHP, Laravel, Composer and Node.js using the [Docs](https://laravel.com/docs/12.x/installation).
To setup all dependencies, type `composer run setup`.
Perfect, now you can start the backend by typing `composer run dev`.

## Links

- [Backend](https://stickerradar.maishai.de)
- [API Docs](https://stickerradar.maishai.de/docs/api)
- [Telescope](https://stickerradar.maishai.de/telescope)

## Troubleshooting

### Filesize Limits

If you encounter problems uploading images, you might need to adjust your _upload_max_filesize_ in your `php.ini`.
You can find the file with `php --ini`.

### Stored Files can't be accessed

You need to link your storage by executing `php artisan storage:link` to create a symlink to your local storage.
