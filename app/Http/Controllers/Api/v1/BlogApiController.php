<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class BlogApiController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeScope('blogs:read');

        $blogs = Blog::where('team_id', $request->user()->current_team_id)
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags'])
            ->latest()
            ->get();

        return response()->json($blogs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeScope('blogs:create');

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string',
            'content'     => 'nullable|string',
            'is_published'=> 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'exists:tags,id',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['title']));

        $blog = Blog::create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'excerpt'      => $validated['excerpt'] ?? null,
            'content'      => $validated['content'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'user_id'      => $request->user()->id,
            'team_id'      => $request->user()->current_team_id,
            'category_id'  => $validated['category_id'] ?? null,
        ]);

        if (!empty($validated['tag_ids'])) {
            $blog->tags()->sync($validated['tag_ids']);
        }

        return response()->json($blog->load(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('blogs:read');

        $blog = Blog::where('team_id', $request->user()->current_team_id)
            ->where('slug', $slug)
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags'])
            ->firstOrFail();

        return response()->json($blog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('blogs:update');

        $blog = Blog::where('team_id', $request->user()->current_team_id)
            ->where('slug', $slug)
            ->firstOrFail();

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string',
            'content'     => 'nullable|string',
            'is_published'=> 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'exists:tags,id',
        ]);

        if ($validated['title'] !== $blog->title) {
            $blog->slug = $this->uniqueSlug(Str::slug($validated['title']), $blog->id);
        }

        if ($request->boolean('is_published') && !$blog->is_published) {
            $blog->published_at = now();
        }

        $blog->fill([
            'title'        => $validated['title'],
            'excerpt'      => $validated['excerpt'] ?? null,
            'content'      => $validated['content'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'category_id'  => $validated['category_id'] ?? null,
        ])->save();

        if (isset($validated['tag_ids'])) {
            $blog->tags()->sync($validated['tag_ids']);
        }

        return response()->json($blog->load(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('blogs:delete');

        $blog = Blog::where('team_id', $request->user()->current_team_id)
            ->where('slug', $slug)
            ->firstOrFail();

        $blog->tags()->sync([]);
        $blog->delete();

        return response()->json([
            'message' => 'Blog post deleted successfully.'
        ]);
    }

    /**
     * Generate unique slug.
     */
    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug;
        $i = 1;
        while (Blog::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $orig . '-' . $i++;
        }
        return $slug;
    }
}
