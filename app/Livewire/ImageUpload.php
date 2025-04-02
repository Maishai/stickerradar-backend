<?php

namespace App\Livewire;

use App\Models\Sticker;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImageUpload extends Component
{
    use WithFileUploads;

    public $photo;

    public $lat;

    public $lon;

    public $tags = [];

    public $selectedTags = [];

    public $noCoordinatesError = false;

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
        ]);

        // Reset coordinates and error state
        $this->lat = null;
        $this->lon = null;
        $this->noCoordinatesError = false;

        $exif = @exif_read_data($this->photo->getRealPath());
        $latRaw = $exif['GPSLatitude'] ?? null;
        $lonRaw = $exif['GPSLongitude'] ?? null;
        $latRef = $exif['GPSLatitudeRef'] ?? 'N';
        $lonRef = $exif['GPSLongitudeRef'] ?? 'E';

        if ($latRaw && $lonRaw) {
            $this->lat = $this->convertDMSToDecimal($latRaw[0], $latRaw[1], $latRaw[2], $latRef);
            $this->lon = $this->convertDMSToDecimal($lonRaw[0], $lonRaw[1], $lonRaw[2], $lonRef);
        } else {
            $this->noCoordinatesError = true;
        }
    }

    private function convertDMSToDecimal($degrees, $minutes, $seconds, $direction)
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

    private function convertToDecimal($fraction)
    {
        if (strpos($fraction, '/') !== false) {
            [$numerator, $denominator] = explode('/', $fraction);

            return $numerator / $denominator;
        }

        return $fraction;
    }

    public function save()
    {
        $this->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'selectedTags' => 'required|array',
            'selectedTags.*' => 'exists:tags,id',
        ]);

        $extension = $this->photo->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Create sticker record
        $sticker = Sticker::create([
            'lat' => $this->lat,
            'lon' => $this->lon,
            'filename' => $filename,
        ]);

        // Attach tags to sticker
        foreach ($this->selectedTags as $tagId) {
            $sticker->tags()->attach($tagId);
        }

        // Store the image
        Storage::disk('public')->putFileAs('stickers', $this->photo, $filename);

        // Redirect to preview page instead of resetting form
        return redirect()
            ->route('stickers.preview', ['sticker' => $sticker->id])
            ->with('message', 'Sticker uploaded successfully!');
    }

    public function mount()
    {
        $this->tags = Tag::all()->toArray();
    }

    public function render()
    {
        return view('livewire.image-upload');
    }
}
