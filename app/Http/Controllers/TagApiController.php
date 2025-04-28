<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Models\Tag;

class TagApiController extends Controller
{
    /**
     * Get all tags
     */
    public function index()
    {
        return TagResource::collection(Tag::all());
    }

    /**
     * Display a tree of tags.
     *
     * Array of tag objects with nested children in a hierarchical tree structure.
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     color: string,
     *     children: array<int, array{
     *         id: string,
     *         name: string,
     *         color: string,
     *         children: array
     *     }>
     * }>
     */
    public function tree()
    {
        return Tag::buildTrees();
    }

    /**
     * Get a specific tag by id
     */
    public function show(Tag $tag)
    {
        return new TagResource($tag);
    }
}
