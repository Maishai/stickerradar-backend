<?php

namespace App\Http\Requests;

use App\Rules\ImageContainsSticker;
use App\Rules\NoSuperTag;
use App\Rules\StickerImage;
use App\Services\StickerService;
use App\State;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStickerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // merge header into input so rules() can see it
        if ($key = $this->header('X-API-KEY')) {
            $this->merge(['api_key' => $key]);
        }

        if ($this->hasFile('image_file')) {
            $uploaded = $this->file('image_file');
            $imgPath = $uploaded->getRealPath();
            $imgData = base64_encode(file_get_contents($imgPath));
            $b64Image = 'data:'.mime_content_type($imgPath).';base64,'.$imgData;

            $coords = app(StickerService::class)
                ->extractCoordinatesFromExif($uploaded);

            $this->merge([
                'image' => $b64Image,
                'lat' => $coords['lat'] ?? null,
                'lon' => $coords['lon'] ?? null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // base image rules
        $image = ['required', new StickerImage];

        // if no valid API key, run ai classification
        if (! $this->filled('api_key')) {
            $image[] = new ImageContainsSticker;
        }

        return [
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'image' => $image,
            // Instead of a base64 image you can upload a file as multipart request. The coordinates will be extracted from it.
            'image_file' => 'nullable|image',
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
            'state' => [Rule::enum(State::class)],
        ];
    }
}
