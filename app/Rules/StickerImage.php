<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StickerImage implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^data:image\/(\w+);base64,/', $value)) {
            $fail('The :attribute must be a valid base64 encoded image (JPG, JPEG) with a maximum size of 4MB.');

            return;
        }

        [$type, $data] = explode(';', $value);
        [, $data] = explode(',', $data);

        if (! in_array($type, ['data:image/jpg', 'data:image/jpeg'])) {
            $fail('The :attribute must be a valid base64 encoded image (JPG, JPEG).');

            return;
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            $fail('The :attribute is not a valid base64 encoded string.');

            return;
        }

        if (strlen($decoded) > 4 * 1024 * 1024) {
            $fail('The :attribute must not exceed 4MB.');

            return;
        }

        $imageInfo = getimagesizefromstring($decoded);
        if ($imageInfo === false || ! in_array($imageInfo['mime'], ['image/jpg', 'image/jpeg'])) {
            $fail('The :attribute must be a valid image of type JPG or JPEG.');

            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_buffer($finfo, $decoded);
        finfo_close($finfo);

        if (! in_array($mimeType, ['image/jpg', 'image/jpeg'])) {
            $fail('The :attribute must be a valid image of type JPG or JPEG.');

            return;
        }
    }
}
