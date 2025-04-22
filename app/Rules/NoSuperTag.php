<?php

namespace App\Rules;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSuperTag implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute  The name of the attribute being validated (e.g., 'tag_ids').
     * @param  mixed  $value  The value of the attribute being validated (the array of tag IDs).
     * @param  Closure  $fail  The callback function to call on failure.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $submittedTagIds = array_unique($value);

        if (count($submittedTagIds) <= 1) {
            return;
        }

        foreach ($submittedTagIds as $potentialSuperTagId) {
            $superTagNode = Tag::findTagNodeInTrees($potentialSuperTagId);

            if ($superTagNode) {
                $descendantIds = Tag::getDescendantIds($potentialSuperTagId);
                $otherSubmittedTagIds = array_diff($submittedTagIds, [$potentialSuperTagId]);
                $violatingDescendantIds = array_intersect($descendantIds, $otherSubmittedTagIds);

                if (! empty($violatingDescendantIds)) {
                    $violatingTagId = reset($violatingDescendantIds);
                    $superTagName = $superTagNode['name'];
                    $violatingTagNode = Tag::findTagNodeInTrees($violatingTagId);
                    $violatingTagName = $violatingTagNode['name'];

                    $fail("The tag '{$violatingTagName}' cannot be a descendant of '{$superTagName}'.");

                    return;
                }
            }
        }
    }
}
