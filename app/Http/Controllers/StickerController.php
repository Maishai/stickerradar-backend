<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class StickerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sticker::query();

        if ($request->has('min_lat') && $request->has('max_lat')) {
            $query->whereBetween('lat', [$request->query('min_lat'), $request->query('max_lat')]);
        }

        if ($request->has('min_lon') && $request->has('max_lon')) {
            $query->whereBetween('lon', [$request->query('min_lon'), $request->query('max_lon')]);
        }

        $stickers = $query->get();
        return $stickers->toJson();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|min:-90|max:90',
            'lon' => 'required|numeric|min:-180|max:180',
            'image' => 'required|image|mimes:jpg,jpeg,png,gif|max:4096',
            'tag' => 'required|array',
            'tag.*' => 'exists:tags,id',
        ]);

        $image = $validated['image'];
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $validated['filename'] = $filename;
        $sticker = Sticker::create(Arr::except($validated, ['image', 'tag']));

        foreach ($validated['tag'] as $tagId) {
            $sticker->tags()->attach($tagId);
        }

        Storage::disk('public')->putFileAs('stickers', $image, $filename);

        return $sticker;
    }

    /**
     * Display the specified resource.
     */
    public function show(Sticker $sticker)
    {
        return $sticker->toJson();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
