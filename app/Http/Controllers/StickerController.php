<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use Illuminate\Http\Request;

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
            'lat' => 'required|double|min:-90|max:90',
            'lon' => 'required|double|min:-180|max:180',
            'image' => 'required|image',
            'tag' => 'required|exists:tags'
        ]);
        $image = $validated['image'];
        unset($validated['image']);
        $sticker = Sticker::create($validated);
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
