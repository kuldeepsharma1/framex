import { Head, Link, usePage, router } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
    FolderOpen, Tag, Plus, Pencil, Trash2,
    Save, X, Loader2, Hash, Check, BookOpen, ChevronRight, Search
} from 'lucide-react';
import { useState, useMemo } from 'react';
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

function TagPill({
    tag,
    tp,
    onEdit,
}: {
    tag: any;
    tp: string;
    onEdit: (tag: any) => void;
}) {
    return (
        <div className="group relative flex items-center gap-3 rounded-2xl border bg-card px-4 py-3 shadow-sm hover:shadow-md transition-all duration-200">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                <Hash className="h-4 w-4 text-primary" />
            </div>

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2 flex-wrap">
                    <span className="text-sm font-semibold">#{tag.name}</span>
                    <Badge variant="secondary" className="text-xs px-1.5 py-0 rounded-full font-normal shrink-0">
                        {tag.blogs_count ?? 0} post{(tag.blogs_count ?? 0) !== 1 ? 's' : ''}
                    </Badge>
                </div>
                <code className="mt-0.5 inline-block text-[10px] text-muted-foreground bg-muted/60 px-1.5 py-0.5 rounded font-mono">
                    {tag.slug}
                </code>
            </div>

            <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <Link
                    href={`/blogs?tag=${tag.slug}`}
                    target="_blank"
                    className="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                    title="View posts"
                >
                    <BookOpen className="h-3.5 w-3.5" />
                </Link>
                <button
                    onClick={() => onEdit(tag)}
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
                            <AlertDialogTitle>Delete Tag?</AlertDialogTitle>
                            <AlertDialogDescription>
                                This action cannot be undone. This will permanently delete the tag "#{tag.name}".
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel className="cursor-pointer">Cancel</AlertDialogCancel>
                            <AlertDialogAction
                                variant={'destructive'}
                                onClick={() => router.delete(`${tp}/manage/tags/${tag.slug}`)}
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

function TagForm({
    title,
    initialName = '',
    onSubmit,
    onCancel,
    processing,
}: {
    title: string;
    initialName?: string;
    onSubmit: (name: string) => void;
    onCancel: () => void;
    processing: boolean;
}) {
    const [name, setName] = useState(initialName);

    return (
        <div className="rounded-2xl border bg-card shadow-lg overflow-hidden">
            <div className="flex items-center gap-3 px-5 py-4 border-b bg-muted/30">
                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                    <Hash className="h-4 w-4 text-primary" />
                </div>
                <span className="font-semibold text-sm">{title}</span>
            </div>

            <div className="p-5 space-y-4">
                <div className="space-y-1.5">
                    <Label htmlFor="tag-name" className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                        Tag Name <span className="text-destructive">*</span>
                    </Label>
                    <Input
                        id="tag-name"
                        value={name}
                        onChange={e => setName(e.target.value)}
                        onKeyDown={e => e.key === 'Enter' && name.trim() && onSubmit(name.trim())}
                        placeholder="e.g. React, Design System, API…"
                        className="h-9"
                        autoFocus
                    />
                    <p className="text-xs text-muted-foreground">Slug will be auto-generated from the name</p>
                </div>

                {/* Preview */}
                {name.trim() && (
                    <div className="flex items-center gap-2 p-3 rounded-xl bg-muted/40 border">
                        <span className="text-xs text-muted-foreground">Preview:</span>
                        <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 border border-primary/20 px-3 py-0.5 text-xs font-semibold text-primary">
                            <Hash className="h-2.5 w-2.5" />{name.trim()}
                        </span>
                    </div>
                )}
            </div>

            <div className="flex items-center justify-end gap-2 px-5 py-4 bg-muted/20 border-t">
                <Button type="button" variant="ghost" size="sm" onClick={onCancel}>
                    <X className="mr-1.5 h-3.5 w-3.5" /> Cancel
                </Button>
                <Button
                    type="button"
                    size="sm"
                    disabled={processing || !name.trim()}
                    onClick={() => onSubmit(name.trim())}
                >
                    {processing ? <Loader2 className="mr-1.5 h-3.5 w-3.5 animate-spin" /> : <Save className="mr-1.5 h-3.5 w-3.5" />}
                    Save Tag
                </Button>
            </div>
        </div>
    );
}

export default function TagsIndex({ tags }: { tags: any[] }) {
    const { currentTeam } = usePage().props as any;
    const tp = currentTeam?.slug ? `/${currentTeam.slug}` : '';

    const [creating, setCreating] = useState(false);
    const [createProcessing, setCreateProcessing] = useState(false);
    const [editSlug, setEditSlug] = useState<string | null>(null);
    const [editProcessing, setEditProcessing] = useState(false);
    const [search, setSearch] = useState('');

    const filtered = useMemo(
        () => tags.filter(t => t.name.toLowerCase().includes(search.toLowerCase())),
        [tags, search]
    );

    const totalPosts = tags.reduce((s: number, t: any) => s + (t.blogs_count ?? 0), 0);

    const handleCreate = (name: string) => {
        setCreateProcessing(true);
        router.post(`${tp}/manage/tags`, { name }, {
            onSuccess: () => { setCreating(false); setCreateProcessing(false); },
            onError: () => setCreateProcessing(false),
        });
    };

    const handleEdit = (slug: string, name: string) => {
        setEditProcessing(true);
        router.put(`${tp}/manage/tags/${slug}`, { name }, {
            onSuccess: () => { setEditSlug(null); setEditProcessing(false); },
            onError: () => setEditProcessing(false),
        });
    };

    return (
        <>
            <Head title="Tags — Blog Management" />

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
                            <span className="text-foreground font-medium">Tags</span>
                        </nav>

                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <h1 className="text-2xl font-black tracking-tight flex items-center gap-2.5">
                                    <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10">
                                        <Hash className="h-5 w-5 text-primary" />
                                    </span>
                                    Tags
                                </h1>
                                <p className="text-sm text-muted-foreground mt-1">
                                    Label posts for discovery and cross-content navigation
                                </p>
                            </div>

                            <div className="flex items-center gap-2 shrink-0">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`${tp}/manage/categories`}>
                                        <FolderOpen className="mr-1.5 h-3.5 w-3.5" /> Categories
                                    </Link>
                                </Button>
                                <Button size="sm" onClick={() => { setCreating(true); setEditSlug(null); }}>
                                    <Plus className="mr-1.5 h-3.5 w-3.5" /> New Tag
                                </Button>
                            </div>
                        </div>

                        {/* Stats strip */}
                        <div className="mt-5 flex gap-6 border-t pt-4">
                            <div className="text-center">
                                <p className="text-2xl font-black tabular-nums">{tags.length}</p>
                                <p className="text-xs text-muted-foreground">Total Tags</p>
                            </div>
                            <div className="w-px bg-border" />
                            <div className="text-center">
                                <p className="text-2xl font-black tabular-nums">{totalPosts}</p>
                                <p className="text-xs text-muted-foreground">Tagged Posts</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* ── Body ── */}
                <div className="flex-1 overflow-auto p-6">
                    <div className="max-w-3xl mx-auto space-y-4">

                        {/* Create form */}
                        {creating && (
                            <TagForm
                                title="New Tag"
                                onSubmit={handleCreate}
                                onCancel={() => setCreating(false)}
                                processing={createProcessing}
                            />
                        )}

                        {/* Edit form */}
                        {editSlug && (() => {
                            const tag = tags.find(t => t.slug === editSlug)!;
                            return (
                                <TagForm
                                    key={`edit-${editSlug}`}
                                    title={`Edit — #${tag.name}`}
                                    initialName={tag.name}
                                    onSubmit={(name) => handleEdit(editSlug, name)}
                                    onCancel={() => setEditSlug(null)}
                                    processing={editProcessing}
                                />
                            );
                        })()}

                        {/* Search — show when ≥5 tags */}
                        {tags.length >= 5 && !creating && !editSlug && (
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                <Input
                                    value={search}
                                    onChange={e => setSearch(e.target.value)}
                                    placeholder="Search tags…"
                                    className="pl-9 h-9"
                                />
                            </div>
                        )}

                        {/* Empty state */}
                        {tags.length === 0 && !creating && (
                            <div className="rounded-2xl border-2 border-dashed py-20 text-center">
                                <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-muted mb-4">
                                    <Hash className="h-8 w-8 text-muted-foreground/50" />
                                </div>
                                <h3 className="font-semibold text-lg">No tags yet</h3>
                                <p className="text-sm text-muted-foreground mt-1 mb-6 max-w-xs mx-auto">
                                    Tags help readers find related posts and improve content discovery
                                </p>
                                <Button onClick={() => setCreating(true)}>
                                    <Plus className="mr-1.5 h-4 w-4" /> Create your first tag
                                </Button>
                            </div>
                        )}

                        {/* Search empty */}
                        {tags.length > 0 && filtered.length === 0 && (
                            <div className="rounded-xl border-2 border-dashed py-10 text-center">
                                <p className="text-sm font-medium text-muted-foreground">No tags match "{search}"</p>
                                <button onClick={() => setSearch('')} className="mt-2 text-xs text-primary hover:underline">Clear search</button>
                            </div>
                        )}

                        {/* Tag list */}
                        {filtered.length > 0 && (
                            <div className="space-y-2.5">
                                <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                    {search ? `Results (${filtered.length})` : `All Tags (${tags.length})`}
                                </p>
                                {filtered.map((tag: any) => (
                                    <TagPill
                                        key={tag.slug}
                                        tag={tag}
                                        tp={tp}
                                        onEdit={(t) => { setEditSlug(t.slug); setCreating(false); }}
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
