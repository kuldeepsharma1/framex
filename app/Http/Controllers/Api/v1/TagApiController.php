<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class TagApiController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeScope('tags:read');

        $tags = Tag::withCount(['blogs' => fn($q) => $q->where('team_id', $request->user()->current_team_id)])
            ->orderBy('name')
            ->get();

        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeScope('tags:create');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $tag = Tag::create([...$validated, 'slug' => $slug]);

        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('tags:read');

        $tag = Tag::where('slug', $slug)
            ->withCount(['blogs' => fn($q) => $q->where('team_id', $request->user()->current_team_id)])
            ->firstOrFail();

        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('tags:update');

        $tag = Tag::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        if (strtolower($validated['name']) !== strtolower($tag->name)) {
            $tag->slug = $this->uniqueSlug(Str::slug($validated['name']), $tag->id);
        }

        $tag->fill($validated)->save();

        return response()->json($tag);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('tags:delete');

        $tag = Tag::where('slug', $slug)->firstOrFail();
        $tag->delete();

        return response()->json([
            'message' => 'Tag deleted successfully.'
        ]);
    }

    /**
     * Generate unique slug.
     */
    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug;
        $i = 1;
        while (Tag::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $orig . '-' . $i++;
        }
        return $slug;
    }
}
