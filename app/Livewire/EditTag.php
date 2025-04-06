<?php

namespace App\Livewire;

use App\Models\Tag;
use Livewire\Component;

class EditTag extends Component
{
    public Tag $tag;

    public string $name = '';

    public $super_tag = null;

    public string $color = '#000000';

    public array $tags = [];

    public function mount(Tag $tag)
    {
        $this->tags = Tag::all()->toArray();

        $this->tag = $tag;
        $this->name = $tag->name;
        $this->super_tag = $tag->super_tag;
        $this->color = $tag->color;
    }

    public function updateTag()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'super_tag' => 'nullable|exists:tags,id',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $this->tag->update($validated);

        session()->flash('update_success', 'Tag updated successfully!');
    }

    public function deleteTag()
    {

        for ($i = 0; $i < count($this->tags); $i++) {
            if ($this->tags[$i]['super_tag'] == $this->tag->id) {
                session()->flash('delete_error', 'Cannot delete this tag as it is a super tag for other tags!');

                return;
            }
        }
        $this->tag->delete();

        session()->flash('delete_success', 'Tag deleted successfully!');
    }

    public function render()
    {
        return view('livewire.edit-tag');
    }
}
