<?php

namespace App\Rules;

use App\Models\Sticker;
use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContainsUncertainTag implements ValidationRule
{
    protected Sticker $sticker;

    public function __construct(Sticker $sticker)
    {
        $this->sticker = $sticker;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tagIds = $this->sticker->tags->pluck('id')->toArray();
        $uncertainTagId = Tag::query()
            ->where('name', 'Ich weiß es nicht')
            ->pluck('id')
            ->first();

        foreach ($tagIds as $tagId) {
            if ($tagId === $uncertainTagId) {
                return;
            }
        }
        $fail("Sticker updates are only allowed when the tag 'Ich weiß es nicht' is included.");

    }
}
