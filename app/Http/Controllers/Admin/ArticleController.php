<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Mwc;
use App\Models\ProgramCategory;
use App\Models\Ranting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::with(['category', 'author']);

        if ($request->filled('post_type')) {
            $query->where('post_type', $request->post_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('excerpt', 'like', "%{$q}%");
            });
        }

        $articles = $query->orderByDesc('published_at')->paginate(10);
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.articles.index', compact('articles', 'categories'));
    }

    public function create()
    {
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();

        return view('admin.articles.create', compact('categories', 'mwcs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:program_categories,id',
            'mwc_id' => 'nullable|exists:mwcs,id',
            'ranting_id' => 'nullable|exists:rantings,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:articles,slug',
            'post_type' => 'required|in:berita,pentasyarufan,edukasi_zis,pengumuman',
            'excerpt' => 'required|string|max:400',
            'content' => 'required|string',
            'thumbnail_image' => 'nullable|string|max:255',
            'thumbnail_file' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . rand(100, 999);
        }

        if ($request->hasFile('thumbnail_file')) {
            $path = $request->file('thumbnail_file')->store('articles', 'public');
            $validated['thumbnail_image'] = '/storage/' . $path;
        } elseif (empty($validated['thumbnail_image'])) {
            $validated['thumbnail_image'] = 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=800&auto=format&fit=crop';
        }

        $validated['author_id'] = Auth::id();
        $validated['published_at'] = $validated['published_at'] ?? now();

        Article::create($validated);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel / Kabar Pentasyarufan berhasil dipublikasikan.');
    }

    public function edit(Article $article)
    {
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();
        $rantings = $article->mwc_id ? Ranting::where('mwc_id', $article->mwc_id)->get() : collect();

        return view('admin.articles.edit', compact('article', 'categories', 'mwcs', 'rantings'));
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:program_categories,id',
            'mwc_id' => 'nullable|exists:mwcs,id',
            'ranting_id' => 'nullable|exists:rantings,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:articles,slug,' . $article->id,
            'post_type' => 'required|in:berita,pentasyarufan,edukasi_zis,pengumuman',
            'excerpt' => 'required|string|max:400',
            'content' => 'required|string',
            'thumbnail_image' => 'nullable|string|max:255',
            'thumbnail_file' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        if ($request->hasFile('thumbnail_file')) {
            $path = $request->file('thumbnail_file')->store('articles', 'public');
            $validated['thumbnail_image'] = '/storage/' . $path;
        }

        $article->update($validated);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil dihapus.');
    }
}
