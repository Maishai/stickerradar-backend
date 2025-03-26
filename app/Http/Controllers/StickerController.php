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
    public function index()
    {
        $stickers = Sticker::all();
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
            'tag' => 'required|exists:tags,id'
        ]);

        $image = $validated['image'];
        $extension = $image->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $validated['filename'] = $filename;
        $sticker = Sticker::create(Arr::except($validated, ['image', 'tag']));
        $sticker->tags()->attach($validated['tag']);

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
