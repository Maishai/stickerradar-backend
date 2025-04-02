<?php

namespace App\Livewire;

use App\Models\Tag;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tags')]
class TagsComponent extends Component
{
    public string $name = '';

    public $super_tag = null;

    public string $color = '#000000';

    public $tagTrees;

    public $rootNodeNames;

    public $selectedRootName = null;

    public array $tags = [];

    public $tag = null; //Selected tag

    public $decodedTagTree;

    public function mount()
    {
        $this->tags = Tag::all()->toArray();

        $this->tagTrees = Tag::buildTrees();
        $this->rootNodeNames = $this->tagTrees->pluck('name');
        $this->selectedRootName = $this->rootNodeNames->first();
        $this->decodeTagTree();
    }

    public function saveTag()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'super_tag' => 'nullable|exists:tags,id',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        Tag::create($validated);

        $this->reset(['name', 'super_tag', 'color']);

        // Refresh data
        $this->tags = Tag::all()->toArray();
        $this->tagTrees = Tag::buildTrees();
        $this->rootNodeNames = $this->tagTrees->pluck('name');

        session()->flash('success', 'Tag created successfully!');
    }

    public function deleteTag()
    {
        $validated = $this->validate([
            'tag' => 'required|exists:tags,id',
        ]);

        $tag = Tag::findOrFail($validated['tag']);
        // Check if any tag with this tag as super_tag exists
        for ($i = 0; $i < count($this->tags); $i++) {
            if ($this->tags[$i]['super_tag'] == $tag->id) {
                session()->flash('delete_error', 'Cannot delete this tag as it is a super tag for other tags!');
                return;
            }
        }
        $tag->delete();

        $this->tags = Tag::all()->toArray();
        $this->tagTrees = Tag::buildTrees();
        $this->rootNodeNames = $this->tagTrees->pluck('name');

        session()->flash('delete_success', 'Tag deleted successfully!');
    }

    public function getSelectedTagTreeProperty()
    {
        return json_encode(collect($this->tagTrees->firstWhere('name', $this->selectedRootName)));
    }

    public function decodeTagTree()
    {
        $jsonString = $this->getSelectedTagTreeProperty();
        $this->decodedTagTree = json_decode($jsonString, true);
    }

    public function updatedSelectedRootName()
    {
        $this->decodeTagTree();
    }

    public function render()
    {
        return view('livewire.tags');
    }
}
