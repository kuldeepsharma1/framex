<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class BlogController extends Controller
{
    public function publicIndex(Request $request)
    {
        $query = Blog::query()->where('is_published', true)
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags'])
            ->latest('published_at');

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('tag')) {
            $query->whereHas('tags', fn($q) => $q->where('slug', $request->tag));
        }

        $blogs = $query->paginate(12)->withQueryString();
        $categories = Category::query()->withCount(['blogs' => fn($q) => $q->where('is_published', true)])->get();
        $tags = Tag::query()->withCount(['blogs' => fn($q) => $q->where('is_published', true)])->get();

        return Inertia::render('blogs/index', compact('blogs', 'categories', 'tags'));
    }

    public function publicShow(string $slug)
    {
        $blog = Blog::query()->where('slug', $slug)->where('is_published', true)
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags'])
            ->firstOrFail();

        $related = Blog::query()->where('is_published', true)
            ->where('id', '!=', $blog->id)
            ->where('category_id', $blog->category_id)
            ->with(['user', 'category'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('blogs/show', compact('blog', 'related'));
    }

    public function index()
    {
        $blogs = Blog::query()->where('team_id', Auth::user()->current_team_id)
            ->with(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags'])
            ->latest()
            ->paginate(15);
            
        return Inertia::render('manage/blogs/index', ['blogs' => $blogs]);
    }

    public function create()
    {
        Gate::authorize('create', Blog::class);

        return Inertia::render('manage/blogs/create', [
            'categories' => Category::query()->orderBy('name', 'asc')->get(),
            'tags'       => Tag::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Blog::class);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string',
            'content'     => 'nullable|string',
            'is_published'=> 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'exists:tags,id',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['title']));

        $imagePath = null;
        if ($request->hasFile('cover_image')) {
            $imagePath = $request->file('cover_image')->store('blogs', 'public');
        }

        $blog = Blog::query()->create([
            'title'        => $validated['title'],
            'slug'         => $slug,
            'excerpt'      => $validated['excerpt'] ?? null,
            'content'      => $validated['content'] ?? null,
            'cover_image'  => $imagePath,
            'is_published' => $request->boolean('is_published'),
            'published_at' => $request->boolean('is_published') ? now() : null,
            'user_id'      => Auth::id(),
            'team_id'      => Auth::user()->current_team_id ?? null,
            'category_id'  => $validated['category_id'] ?? null,
        ]);

        if (!empty($validated['tag_ids'])) {
            $blog->tags()->sync($validated['tag_ids']);
        }

        return redirect()->route('manage.blogs.index')->with('success', 'Blog post created successfully.');
    }

    public function show(string $current_team, Blog $blog)
    {
        Gate::authorize('view', $blog);

        $blog->load(['user' => fn ($q) => $q->select('id', 'name', 'email'), 'category', 'tags']);
        
        return Inertia::render('manage/blogs/show', ['blog' => $blog]);
    }

    public function edit(string $current_team, Blog $blog)
    {
        Gate::authorize('update', $blog);

        $blog->load(['tags']);
        
        return Inertia::render('manage/blogs/edit', [
            'blog'       => $blog,
            'categories' => Category::query()->orderBy('name', 'asc')->get(),
            'tags'       => Tag::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function update(Request $request, string $current_team, Blog $blog)
    {
        Gate::authorize('update', $blog);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string',
            'content'     => 'nullable|string',
            'is_published'=> 'boolean',
            'category_id' => 'nullable|exists:categories,id',
            'tag_ids'     => 'nullable|array',
            'tag_ids.*'   => 'exists:tags,id',
            'cover_image' => 'nullable|image|max:5120',
        ]);

        if ($validated['title'] !== $blog->title) {
            $blog->slug = $this->uniqueSlug(Str::slug($validated['title']), $blog->id);
        }

        if ($request->hasFile('cover_image')) {
            if ($blog->cover_image) {
                Storage::disk('public')->delete($blog->cover_image);
            }
            $blog->cover_image = $request->file('cover_image')->store('blogs', 'public');
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

        $blog->tags()->sync($validated['tag_ids'] ?? []);

        return redirect()->route('manage.blogs.index', ['current_team' => $current_team])
            ->with('success', 'Blog post updated successfully.');
    }

    public function destroy(string $current_team, Blog $blog)
    {
        Gate::authorize('delete', $blog);

        if ($blog->cover_image) {
            Storage::disk('public')->delete($blog->cover_image);
        }
        
        $blog->tags()->sync([]);
        Blog::destroy($blog->id);
        
        return redirect()->back()->with('success', 'Blog post deleted.');
    }

    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $orig = $slug; 
        $i = 1;
        
        while (Blog::query()->where('slug', $slug)->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $orig . '-' . $i++;
        }
        
        return $slug;
    }
}