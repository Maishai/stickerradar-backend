<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxTileSize implements ValidationRule
{
    protected float $maxSize;

    public function __construct(float $maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();

        if (! isset($data['min_lat'], $data['max_lat'], $data['min_lon'], $data['max_lon'])) {
            $fail('All bounds must be provided.');

            return;
        }

        $latDiff = abs($data['max_lat'] - $data['min_lat']);
        $lonDiff = abs($data['max_lon'] - $data['min_lon']);

        if ($latDiff * $lonDiff > $this->maxSize) {
            $fail("The selected area is too large. Max size is $this->maxSize.");
        }
    }
}
