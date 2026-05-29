<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Blog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('blogs')->orderBy('name')->get();

        return Inertia::render('manage/tags/index', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Blog::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        Tag::create([...$validated, 'slug' => $slug]);

        return redirect()->back()->with('success', 'Tag created successfully.');
    }

    // Route model binding uses {tag:slug}
    public function update(Request $request, $current_team, Tag $tag)
    {
        Gate::authorize('create', Blog::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        if (strtolower($validated['name']) !== strtolower($tag->name)) {
            $tag->slug = $this->uniqueSlug(Str::slug($validated['name']), $tag->id);
        }

        $tag->fill($validated)->save();

        return redirect()->back()->with('success', 'Tag updated successfully.');
    }

    public function destroy($current_team, Tag $tag)
    {
        Gate::authorize('create', Blog::class);

        $tag->delete();

        return redirect()->back()->with('success', 'Tag deleted.');
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug;
        $i    = 1;
        while (
            Tag::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $orig . '-' . $i++;
        }

        return $slug;
    }
}
