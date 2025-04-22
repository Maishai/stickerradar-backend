<?php

namespace App\Rules;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSuperTag implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1) Normalize input
        $submitted = array_unique((array) $value);

        // 2) Get the hierarchy as a nested array
        $trees = Tag::buildTrees()->toArray();

        // 3) Build parent & name maps
        $parentMap = [];
        $nameMap = [];

        $buildMaps = function ($nodes, ?string $parentId = null) use (&$parentMap, &$nameMap, &$buildMaps) {
            foreach ($nodes as $node) {
                $id = $node['id'];
                $name = $node['name'];

                $nameMap[$id] = $name;
                if ($parentId !== null) {
                    $parentMap[$id] = $parentId;
                }

                if (! empty($node['children'])) {
                    $buildMaps($node['children'], $id);
                }
            }
        };

        $buildMaps($trees);

        // 4) Helper to get all ancestors of a tag
        $getAncestors = function (string $id) use ($parentMap): array {
            $ancestors = [];
            while (isset($parentMap[$id])) {
                $ancestors[] = $parentMap[$id];
                $id = $parentMap[$id];
            }

            return $ancestors;
        };

        // 5) Fail on any tag that shares an ancestor in the submitted list
        foreach ($submitted as $tagId) {
            foreach ($getAncestors($tagId) as $ancestorId) {
                if (in_array($ancestorId, $submitted, true)) {
                    $fail("You can’t select “{$nameMap[$tagId]}” together with its parent “{$nameMap[$ancestorId]}.”");

                    return;
                }
            }
        }
    }
}
