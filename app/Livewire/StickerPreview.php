<?php

namespace App\Livewire;

use App\Models\Sticker;
use App\Models\Tag;
use Livewire\Component;

class StickerPreview extends Component
{
    public $tags = [];

    public $selectedTags = [];

    public $searchQuery = '';

    public function mount()
    {
        $this->tags = Tag::all()->toArray();
    }

    public function updatedSelectedTags()
    {
        $this->resetPage();
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function getStickersProperty()
    {
        $query = Sticker::query()->with('tags');

        // Filter by selected tags if any
        if (! empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('tags.id', $this->selectedTags);
            }, '=', count($this->selectedTags));
        }

        return $query->latest()->get();
    }

    public function render()
    {
        return view('livewire.sticker-preview', [
            'stickers' => $this->stickers,
        ]);
    }
}
