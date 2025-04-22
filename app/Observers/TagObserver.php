<?php

namespace App\Observers;

use App\Models\Tag;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TagObserver
{
    /**
     * Handle the Tag "created" event.
     */
    public function created(Tag $tag): void
    {
        $this->rebuildTagTrees();
    }

    /**
     * Handle the Tag "updated" event.
     */
    public function updated(Tag $tag): void
    {
        $this->rebuildTagTrees();
    }

    /**
     * Handle the Tag "deleted" event.
     */
    public function deleted(Tag $tag): void
    {
        $this->rebuildTagTrees();
    }

    private static function rebuildTagTrees()
    {
        Log::info('Rebuilding Tag Tree Cache');
        Cache::forget('tagtrees');
        Tag::buildTrees();
    }
}
