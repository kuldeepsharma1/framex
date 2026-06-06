<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class CategoryApiController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeScope('categories:read');

        $categories = Category::withCount(['blogs' => fn($q) => $q->where('team_id', $request->user()->current_team_id)])
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorizeScope('categories:create');

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'required|string|max:20',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $category = Category::create([...$validated, 'slug' => $slug]);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('categories:read');

        $category = Category::where('slug', $slug)
            ->withCount(['blogs' => fn($q) => $q->where('team_id', $request->user()->current_team_id)])
            ->firstOrFail();

        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('categories:update');

        $category = Category::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'required|string|max:20',
        ]);

        if (strtolower($validated['name']) !== strtolower($category->name)) {
            $category->slug = $this->uniqueSlug(Str::slug($validated['name']), $category->id);
        }

        $category->fill($validated)->save();

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $slug): JsonResponse
    {
        $this->authorizeScope('categories:delete');

        $category = Category::where('slug', $slug)->firstOrFail();
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.'
        ]);
    }

    /**
     * Generate unique slug.
     */
    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug;
        $i = 1;
        while (Category::where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $orig . '-' . $i++;
        }
        return $slug;
    }
}
