<?php

namespace App\Livewire;

use App\Models\Sticker;
use App\Models\Tag;
use Livewire\Component;
use Livewire\WithPagination;

class StickerPreview extends Component
{
    use WithPagination;

    public $tags = [];

    public $selectedTags = [];

    public $perPage = 12;

    protected $queryString = ['selectedTags'];

    public function mount()
    {
        $this->tags = Tag::all()->toArray();
    }

    public function updatedSelectedTags()
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

        return $query->latest()->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.sticker-preview', [
            'stickers' => $this->stickers,
        ]);
    }
}
