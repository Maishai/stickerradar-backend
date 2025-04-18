<?php

namespace App\Livewire;

use App\Models\Tag;
use App\Services\StickerService;
use App\State;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Upload Image')]
class ImageUpload extends Component
{
    use WithFileUploads;

    public $photo;

    public $lat;

    public $lon;

    public $tags = [];

    public $selectedTags = [];

    public $selectedState = State::EXISTS;

    public $noCoordinatesError = false;

    protected StickerService $stickerService;

    public function boot(StickerService $stickerService)
    {
        $this->stickerService = $stickerService;
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
        ]);

        // Reset coordinates and error state
        $this->lat = null;
        $this->lon = null;
        $this->noCoordinatesError = false;

        $coordinates = $this->stickerService->extractCoordinatesFromExif($this->photo);

        if ($coordinates) {
            $this->lat = $coordinates['lat'];
            $this->lon = $coordinates['lon'];
        } else {
            $this->noCoordinatesError = true;
        }
    }

    public function save()
    {
        $this->validate([
            'photo' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'selectedTags' => 'required|array',
            'selectedTags.*' => 'exists:tags,id',
            'selectedState' => [Rule::enum(State::class)],
        ]);

        $data = [
            'lat' => $this->lat,
            'lon' => $this->lon,
        ];

        $imgPath = $this->photo->getRealPath();
        $imgData = base64_encode(file_get_contents($imgPath));
        $b64Image = 'data:'.mime_content_type($imgPath).';base64,'.$imgData;

        $sticker = $this->stickerService->createSticker(
            $data,
            $b64Image,
            $this->selectedTags,
            $this->selectedState ?? State::EXISTS,
        );

        return redirect()
            ->route('stickers.index', ['sticker' => $sticker->id])
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
