<?php

namespace App\Http\Requests;

use App\StickerInclusion;
use App\Traits\WithBounds;
use App\Traits\WithClustering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClusterIndexRequest extends FormRequest
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
        ] + $this->getBoundsRules() + $this->getClusteringRules();
    }
}
