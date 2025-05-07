<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Rules\NoSuperTag;
use App\StickerInclusion;
use App\Traits\WithBounds;
use App\Traits\WithClustering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ClusterShowMultipleRequest extends FormRequest
{
    use WithBounds, WithClustering;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // include or hide stickers. Dynamic mode includes them, if there are max 15 in total (not per cluster)
            'include_stickers' => ['nullable', Rule::enum(StickerInclusion::class)],
            'tags' => ['nullable', 'array', new NoSuperTag],
            'tags.*' => 'uuid|exists:tags,id',
            'date' => ['nullable', 'date'],
        ] + $this->getBoundsRules() + $this->getClusteringRules();
    }

    /**
     * Get the tags as a Collection of Tag models.
     *
     * @return \Illuminate\Support\Collection|Tag[]
     */
    public function tags(): Collection
    {
        $ids = $this->input('tags', []);

        return Tag::findMany($ids);
    }
}
