<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection; // Import Collection

class Tag extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['name', 'super_tag', 'color'];

    /**
     * Get the parent tag (super tag) that this tag belongs to.
     */
    public function superTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'super_tag');
    }

    /**
     * Get the direct child tags (sub-tags) of this tag.
     */
    public function subTags(): HasMany
    {
        return $this->hasMany(Tag::class, 'super_tag');
    }

    /**
     * Build a nested collection of tag trees.
     * Root nodes are tags with no super_tag.
     *
     * @return Collection<array-key, array> A collection where each item is a root tag array
     *                                      with 'id', 'name', 'color', and a 'children' collection.
     */
    public static function buildTrees(): Collection
    {
        $tags = Tag::all();

        return $tags->whereNull('super_tag')
            ->map(fn ($tag) => static::buildTree($tags, $tag))
            ->values();
    }

    /**
     * Recursively build a single tag tree starting from a given tag model.
     *
     * @param  Collection  $allTags  All tags fetched from the database.
     * @param  Tag  $currentTag  The tag model to build the subtree for.
     * @return array The tag node array including its 'children' collection.
     *               Format: ['id' => '...', 'name' => '...', 'color' => '...', 'children' => Collection([...])]
     */
    private static function buildTree(Collection $allTags, Tag $currentTag): array
    {
        // Find direct children of the current tag from the pre-fetched collection
        $children = $allTags->where('super_tag', $currentTag->id);

        return [
            'id' => $currentTag->id,
            'name' => $currentTag->name,
            'color' => $currentTag->color ?? 'steelblue', // Use a default color if none is set
            // Recursively build trees for each child
            'children' => $children->map(fn ($child) => static::buildTree($allTags, $child))->values(),
        ];
    }

    /**
     * Find a specific tag node array within the nested structure returned by buildTrees.
     *
     * @param  string  $tagId  The ID of the tag to find.
     * @return array|null The found tag node array (['id' => ..., 'name' => ..., 'color' => ..., 'children' => ...]), or null if not found.
     */
    public static function findTagNodeInTrees(string $tagId): ?array
    {
        $trees = static::buildTrees()->toArray();

        return static::searchTreeRecursive($tagId, $trees);
    }

    /**
     * Recursive helper to search for a tag ID within a nested array structure.
     * This method expects the input $nodes to be an array (like the output of buildTrees()->toArray()).
     *
     * @param  string  $tagId  The ID to search for.
     * @param  array  $nodes  The current level of nodes (an array of node arrays, each with 'children' as array).
     * @return array|null The found node array, or null if not found.
     */
    private static function searchTreeRecursive(string $tagId, array $nodes): ?array
    {
        foreach ($nodes as $node) {
            if (($node['id'] ?? null) === $tagId) {
                return $node; // Found the tag
            }

            $children = $node['children'] ?? null;
            if ($children instanceof Collection) {
                $children = $children->toArray();
            } elseif (! is_array($children)) {
                $children = [];
            }

            if (! empty($children)) {
                $foundInChild = static::searchTreeRecursive($tagId, $children);
                if ($foundInChild) {
                    return $foundInChild;
                }
            }
        }

        return null; // Tag not found in this set of nodes
    }

    /**
     * Get all descendant tag IDs for a given tag ID.
     * This includes direct children, grandchildren, and so on.
     *
     * @param  string  $tagId  The ID of the tag to get descendants for.
     * @return array An array of descendant tag IDs. Returns an empty array if the tag is not found.
     */
    public static function getDescendantIds(string $tagId): array
    {
        // Find the starting node in the tree structure
        $tagNode = static::findTagNodeInTrees($tagId);

        if (! $tagNode) {
            return []; // If the tag itself isn't found, it has no descendants in this hierarchy
        }

        $descendantIds = [];
        // Use a private recursive helper to collect IDs starting from the found node
        static::collectDescendantIdsRecursive($tagNode, $descendantIds);

        return $descendantIds;
    }

    /**
     * Recursive helper to collect all descendant tag IDs from a given node array.
     * Collects IDs by reference into the $descendantIds array.
     * This method expects the input $node to be an array (like from findTagNodeInTrees).
     *
     * @param  array  $node  The starting tag node array (with 'children' as array).
     * @param  array  &$descendantIds  The array to collect descendant IDs into (passed by reference).
     */
    private static function collectDescendantIdsRecursive(array $node, array &$descendantIds): void
    {
        $children = $node['children'];
        if (! empty($children)) {
            foreach ($children as $childNode) {
                // Add the current child's ID to the list
                if (($childNode['id'] ?? null) !== null) {
                    $descendantIds[] = $childNode['id'];
                }

                static::collectDescendantIdsRecursive($childNode, $descendantIds);
            }
        }
    }
}
