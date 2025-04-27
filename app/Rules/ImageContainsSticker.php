<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageContainsSticker implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            if ($value instanceof TemporaryUploadedFile) {

                $imgPath = $value->getRealPath();
                $imgData = base64_encode(file_get_contents($imgPath));
                $value = 'data:'.mime_content_type($imgPath).';base64,'.$imgData;
            } else {
                $fail('Invalid image format');
            }
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => env('CLASSIFIER_API_KEY'),
            ])
                ->timeout(5)
                ->post(env('CLASSIFIER_URI'), [
                    'image_base64' => $this->stripBase64Header($value),
                ]);
            if ($response->successful()) {
                $prob = $response->json('sticker_probability', 0);
                if ($prob <= 0.95) {
                    Log::info("Rejecting sticker, since probability is only $prob");
                    $fail("The {$attribute} does not appear to contain a sticker (probability: ".round($prob * 100, 1).'%).');
                }
                Log::info("Accepting sticker with probability of $prob");
            }
        } catch (\Throwable $e) {
            // network error, timeout, invalid JSON, etc. => treat as valid
        }
    }

    private function stripBase64Header(string $base64): string
    {
        if (Str::contains($base64, ',')) {
            return Str::after($base64, ',');
        }

        return $base64;
    }
}
