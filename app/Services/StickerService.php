<?php

namespace App\Services;

use App\Models\Sticker;
use App\State;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class StickerService
{
    /**
     * Create a new sticker with the given data and image
     */
    public function createSticker(array $cords, string $base64Image, array $tagIds, State $state = State::EXISTS): Sticker
    {
        if (! preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
            throw new \InvalidArgumentException('Invalid base64 image format.');
        }

        $extension = strtolower($matches[1]);
        $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64Image));

        if ($imageData === false) {
            throw new \InvalidArgumentException('Failed to decode base64 image.');
        }

        $filename = Str::uuid().'.'.$extension;

        $sticker = Sticker::create([
            'lat' => $cords['lat'],
            'lon' => $cords['lon'],
            'filename' => $filename,
        ]);

        $sticker->tags()->sync($tagIds);

        $sticker->stateHistory()->create([
            'state' => $state,
            'last_seen' => now(),
        ]);

        // Save the decoded image to the filesystem
        Storage::disk('public')->put("stickers/{$filename}", $imageData);
        $filepath = Storage::disk('public')->path("stickers/{$filename}");

        $this->createThumbnail($filename, $filepath);

        return $sticker;
    }

    /**
     * Create a thumbnail for the given image
     */
    public static function createThumbnail(string $filename, string $filepath): void
    {
        logger("Creating thumbnail for $filename");
        Storage::disk('public')->makeDirectory('stickers/thumbnails');
        Image::read($filepath)
            ->scale(width: 400)
            ->save(Storage::disk('public')->path('stickers/thumbnails/'.$filename));
    }

    /**
     * Extract GPS coordinates from image EXIF data
     */
    public function extractCoordinatesFromExif(UploadedFile $image): ?array
    {
        $exif = @exif_read_data($image->getRealPath());
        $latRaw = $exif['GPSLatitude'] ?? null;
        $lonRaw = $exif['GPSLongitude'] ?? null;
        $latRef = $exif['GPSLatitudeRef'] ?? 'N';
        $lonRef = $exif['GPSLongitudeRef'] ?? 'E';

        if ($latRaw && $lonRaw) {
            $lat = $this->convertDMSToDecimal($latRaw[0], $latRaw[1], $latRaw[2], $latRef);
            $lon = $this->convertDMSToDecimal($lonRaw[0], $lonRaw[1], $lonRaw[2], $lonRef);

            return [
                'lat' => $lat,
                'lon' => $lon,
            ];
        }

        return null;
    }

    /**
     * Convert DMS (Degrees, Minutes, Seconds) coordinates to decimal format
     */
    private function convertDMSToDecimal($degrees, $minutes, $seconds, $direction): float
    {
        // Convert fractions to decimal values
        $degrees = $this->convertToDecimal($degrees);
        $minutes = $this->convertToDecimal($minutes);
        $seconds = $this->convertToDecimal($seconds);

        // Calculate decimal degrees
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        // Apply negative value for South or West coordinates
        if ($direction == 'S' || $direction == 'W') {
            $decimal = -$decimal;
        }

        return $decimal;
    }

    /**
     * Convert a fraction to decimal
     */
    private function convertToDecimal($fraction): float
    {
        if (is_string($fraction) && strpos($fraction, '/') !== false) {
            [$numerator, $denominator] = explode('/', $fraction);

            return $numerator / $denominator;
        }

        return floatval($fraction);
    }
}
