<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Tag::all()->toJson();
    }

    public function tree()
    {
        return Tag::buildTrees();
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        return $tag->toJson();
    }

    public function update(Request $request, string $id)
    {
        //
    }
}
