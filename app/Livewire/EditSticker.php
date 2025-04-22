<?php

namespace App\Livewire;

use App\Models\Sticker;
use App\Models\Tag;
use App\Rules\NoSuperTag;
use App\State;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EditSticker extends Component
{
    public Sticker $sticker;

    public $selectedTags;

    public $tags;

    public $selectedState;

    public $lastSeen;

    public function mount(Sticker $sticker)
    {
        $this->sticker = $sticker;
        $this->selectedTags = $sticker->tags->pluck('id')->toArray();
        $this->selectedState = $sticker->state;
        $this->lastSeen = $sticker->last_seen;
        $this->tags = Tag::all();
    }

    public function save()
    {
        $this->validate([
            'selectedTags' => ['array', new NoSuperTag],
            'selectedTags.*' => 'exists:tags,id',
            'selectedState' => [Rule::enum(State::class)],
            'lastSeen' => 'date',
        ]);
        // fucking datepicker returns the date of the day before
        $date = Carbon::parse($this->lastSeen)->addDay()->format('Y-m-d');

        $this->sticker->update([
            'state' => $this->selectedState,
            'last_seen' => $date,
        ]);
        $this->sticker->tags()->sync($this->selectedTags);
        redirect()->route('stickers.index');
    }

    public function delete()
    {
        $this->sticker->delete();

        return redirect()->route('stickers.index');
    }

    public function render()
    {
        return view('livewire.edit-sticker');
    }
}
