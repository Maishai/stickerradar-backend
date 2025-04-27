<?php

namespace App\Http\Requests;

use App\Rules\ImageContainsSticker;
use App\Rules\NoSuperTag;
use App\Rules\StickerImage;
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
            'tags' => ['required', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
            'state' => [Rule::enum(State::class)],
        ];
    }
}
