<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('blogs')->orderBy('name')->get();

        return Inertia::render('manage/categories/index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Blog::class);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'required|string|max:20',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        Category::create([...$validated, 'slug' => $slug]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    // Route model binding uses {category:slug}
    public function update(Request $request, $current_team, Category $category)
    {
        Gate::authorize('create', Blog::class);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'color'       => 'required|string|max:20',
        ]);

        // Regenerate slug only when name changes
        if (strtolower($validated['name']) !== strtolower($category->name)) {
            $category->slug = $this->uniqueSlug(Str::slug($validated['name']), $category->id);
        }

        $category->fill($validated)->save();

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function destroy($current_team, Category $category)
    {
        Gate::authorize('create', Blog::class);

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted.');
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug;
        $i    = 1;
        while (
            Category::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $orig . '-' . $i++;
        }

        return $slug;
    }
}
