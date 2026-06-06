import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    FolderOpen, Tag, Plus, Pencil, Trash2,
    Save, X, Loader2, Folder, Check, BookOpen,
    Hash, ChevronRight, LayoutGrid
} from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';

const PALETTE = [
    { hex: '#6366f1', label: 'Indigo' },
    { hex: '#8b5cf6', label: 'Violet' },
    { hex: '#ec4899', label: 'Pink' },
    { hex: '#ef4444', label: 'Red' },
    { hex: '#f97316', label: 'Orange' },
    { hex: '#eab308', label: 'Yellow' },
    { hex: '#22c55e', label: 'Green' },
    { hex: '#14b8a6', label: 'Teal' },
    { hex: '#3b82f6', label: 'Blue' },
    { hex: '#64748b', label: 'Slate' },
];

function ColorPicker({ value, onChange }: { value: string; onChange: (c: string) => void }) {
    return (
        <div className="flex gap-2 flex-wrap">
            {PALETTE.map(({ hex, label }) => (
                <button
                    key={hex}
                    type="button"
                    title={label}
                    onClick={() => onChange(hex)}
                    className="relative h-7 w-7 rounded-full transition-transform hover:scale-110 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    style={{ backgroundColor: hex }}
                >
                    {value === hex && (
                        <span className="absolute inset-0 flex items-center justify-center">
                            <Check className="h-3.5 w-3.5 text-white drop-shadow" />
                        </span>
                    )}
                    {value === hex && (
                        <span className="absolute -inset-1 rounded-full border-2 border-foreground/40 pointer-events-none" />
                    )}
                </button>
            ))}
        </div>
    );
}

function CategoryCard({
    cat,
    tp,
    onEdit,
}: {
    cat: any;
    tp: string;
    onEdit: (cat: any) => void;
}) {
    return (
        <div
            className="group relative flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-all duration-200"
            style={{ borderLeftWidth: 4, borderLeftColor: cat.color }}
        >
            {/* Color blob */}
            <div
                className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white text-lg font-black shadow-sm"
                style={{ backgroundColor: cat.color }}
            >
                {cat.name.charAt(0).toUpperCase()}
            </div>

            {/* Info */}
            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-semibold truncate">{cat.name}</span>
                    <Badge
                        variant="secondary"
                        className="text-xs px-2 py-0 rounded-full font-normal shrink-0"
                    >
                        {cat.blogs_count ?? 0} post{(cat.blogs_count ?? 0) !== 1 ? 's' : ''}
                    </Badge>
                </div>
                {cat.description && (
                    <p className="text-xs text-muted-foreground mt-0.5 truncate">{cat.description}</p>
                )}
                <code className="mt-1 inline-block text-[10px] text-muted-foreground bg-muted/60 px-1.5 py-0.5 rounded font-mono">
                    {cat.slug}
                </code>
            </div>

            {/* Actions – hidden until hover */}
            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <Link
                    href={`/blogs?category=${cat.slug}`}
                    target="_blank"
                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                    title="View posts"
                >
                    <BookOpen className="h-3.5 w-3.5" />
                </Link>
                <button
                    onClick={() => onEdit(cat)}
                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors cursor-pointer"
                    title="Edit"
                >
                    <Pencil className="h-3.5 w-3.5" />
                </button>
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <button
                            className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-destructive hover:bg-destructive/10 transition-colors cursor-pointer"
                            title="Delete"
                        >
                            <Trash2 className="h-3.5 w-3.5" />
                        </button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Delete Category?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be undone. This will permanently delete the category "{cat.name}". All associated posts will become uncategorized.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel className="cursor-pointer">Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                variant={'destructive'}
                                onClick={() => router.delete(`${tp}/manage/categories/${cat.slug}`)}
                                className="bg-destructive cursor-pointer text-white hover:bg-destructive/90"
                            >
                                Delete
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </div>
    );
}

function CategoryForm({
    title,
    initialData = { name: '', description: '', color: '#6366f1' },
    onSubmit,
    onCancel,
    processing,
}: {
    title: string;
    initialData?: { name: string; description: string; color: string };
    onSubmit: (data: { name: string; description: string; color: string }) => void;
    onCancel: () => void;
    processing: boolean;
}) {
    const [name, setName] = useState(initialData.name);
    const [description, setDescription] = useState(initialData.description);
    const [color, setColor] = useState(initialData.color);

    return (
        <div className="rounded-2xl border bg-card shadow-lg overflow-hidden">
            {/* Form header */}
            <div className="flex items-center gap-3 px-5 py-4 border-b bg-muted/30">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                    <Folder className="h-4 w-4 text-primary" />
                </div>
                <span className="font-semibold text-sm">{title}</span>
            </div>

            {/* Fields */}
            <div className="p-5 space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                        <Label htmlFor="cat-name" className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                            Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="cat-name"
                            value={name}
                            onChange={e => setName(e.target.value)}
                            placeholder="e.g. Engineering, Design…"
                            className="h-9"
                            autoFocus
                        />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="cat-desc" className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                            Description
                        </Label>
                        <Input
                            id="cat-desc"
                            value={description}
                            onChange={e => setDescription(e.target.value)}
                            placeholder="Brief description…"
                            className="h-9"
                        />
                    </div>
                </div>

                <div className="space-y-2">
                    <Label className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                        Color
                    </Label>
                    <ColorPicker value={color} onChange={setColor} />
                    {/* Preview badge */}
                    <div className="flex items-center gap-2 mt-2">
                        <span className="text-xs text-muted-foreground">Preview:</span>
                        <span
                            className="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-semibold text-white"
                            style={{ backgroundColor: color }}
                        >
                            <span className="h-1.5 w-1.5 rounded-full bg-white/60" />
                            {name || 'Category Name'}
                        </span>
                    </div>
                </div>
            </div>

            {/* Actions */}
            <div className="flex items-center justify-end gap-2 px-5 py-4 bg-muted/20 border-t">
                <Button type="button" variant="ghost" size="sm" onClick={onCancel}>
                    <X className="mr-1.5 h-3.5 w-3.5" /> Cancel
                </Button>
                <Button
                    type="button"
                    size="sm"
                    disabled={processing || !name.trim()}
                    onClick={() => onSubmit({ name, description, color })}
                >
                    {processing ? <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" /> : <Save className="mr-1.5 h-3.5 w-3.5" />}
                    Save Category
                </Button>
            </div>
        </div>
    );
}

export default function CategoriesIndex({ categories }: { categories: any[] }) {
    const { currentTeam } = usePage().props as any;
    const tp = currentTeam?.slug ? `/${currentTeam.slug}` : '';

    const { post, processing: createProcessing } = useForm({});
    const [creating, setCreating] = useState(false);
    const [editSlug, setEditSlug] = useState<string | null>(null);
    const [editProcessing, setEditProcessing] = useState(false);



    const handleCreate = (data: { name: string; description: string; color: string }) => {
        router.post(`${tp}/manage/categories`, data, {
            onSuccess: () => setCreating(false),
        });
    };

    const handleEdit = (slug: string, data: { name: string; description: string; color: string }) => {
        setEditProcessing(true);
        router.put(`${tp}/manage/categories/${slug}`, data, {
            onSuccess: () => { setEditSlug(null); setEditProcessing(false); },
            onError: () => setEditProcessing(false),
        });
    };

    const totalPosts = categories.reduce((sum: number, c: any) => sum + (c.blogs_count ?? 0), 0);

    return (
        <>
            <Head title="Categories — Blog Management" />

            <div className="flex h-full flex-1 flex-col">
                {/* ── Page header ── */}
                <div className="border-b bg-card/60 backdrop-blur-sm">
                    <div className="px-6 py-5">
                        {/* Breadcrumb */}
                        <nav className="flex items-center gap-1.5 text-xs text-muted-foreground mb-3">
                            <Link href={`${tp}/manage/blogs`} className="hover:text-foreground transition-colors flex items-center gap-1">
                                <BookOpen className="h-3 w-3" /> Blog
                            </Link>
                            <ChevronRight className="h-3 w-3" />
                            <span className="text-foreground font-medium">Categories</span>
                        </nav>

                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-black tracking-tight flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10">
                                        <Folder className="h-5 w-5 text-primary" />
                                    </span>
                                    Categories
                                </h1>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Organize your blog posts into meaningful collections
                                </p>
                            </div>

                            <div className="flex items-center gap-2 shrink-0">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`${tp}/manage/tags`}>
                                        <Hash className="mr-1.5 h-3.5 w-3.5" /> Tags
                                    </Link>
                                </Button>
                                <Button size="sm" onClick={() => { setCreating(true); setEditSlug(null); }}>
                                    <Plus className="mr-1.5 h-3.5 w-3.5" /> New Category
                                </Button>
                            </div>
                        </div>

                        {/* Stats strip */}
                        <div className="mt-5 flex gap-6 border-t pt-4">
                            <div className="text-center">
                                <p className="text-2xl font-black tabular-nums">{categories.length}</p>
                                <p className="text-xs text-muted-foreground">Categories</p>
                            </div>
                            <div className="w-px bg-border" />
                            <div className="text-center">
                                <p className="text-2xl font-black tabular-nums">{totalPosts}</p>
                                <p className="text-xs text-muted-foreground">Categorized Posts</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Body ── */}
                <div className="flex-1 overflow-auto p-6">
                    <div className="max-w-3xl mx-auto space-y-4">

                        {/* Create form */}
                        {creating && (
                            <CategoryForm
                                title="New Category"
                                onSubmit={handleCreate}
                                onCancel={() => setCreating(false)}
                                processing={createProcessing}
                            />
                        )}

                        {/* Edit form */}
                        {editSlug && (
                            (() => {
                                const cat = categories.find(c => c.slug === editSlug)!;
                                return (
                                    <CategoryForm
                                        key={`edit-${editSlug}`}
                                        title={`Edit — ${cat.name}`}
                                        initialData={{ name: cat.name, description: cat.description || '', color: cat.color }}
                                        onSubmit={(data) => handleEdit(editSlug, data)}
                                        onCancel={() => setEditSlug(null)}
                                        processing={editProcessing}
                                    />
                                );
                            })()
                        )}

                        {/* Empty state */}
                        {categories.length === 0 && !creating && (
                            <div className="rounded-2xl border-2 border-dashed py-20 text-center">
                                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-muted mb-4">
                                    <Folder className="h-8 w-8 text-muted-foreground/50" />
                                </div>
                                <h3 className="font-semibold text-lg">No categories yet</h3>
                                <p className="text-sm text-muted-foreground mt-1 mb-6 max-w-xs mx-auto">
                                    Create categories to organize your blog posts and help readers navigate your content
                                </p>
                                <Button onClick={() => setCreating(true)}>
                                    <Plus className="mr-1.5 h-4 w-4" /> Create your first category
                                </Button>
                            </div>
                        )}

                        {/* Category cards */}
                        {categories.length > 0 && (
                            <div className="space-y-2.5">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                        All Categories ({categories.length})
                                    </p>
                                </div>
                                {categories.map((cat: any) => (
                                    <CategoryCard
                                        key={cat.slug}
                                        cat={cat}
                                        tp={tp}
                                        onEdit={(c) => { setEditSlug(c.slug); setCreating(false); }}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
