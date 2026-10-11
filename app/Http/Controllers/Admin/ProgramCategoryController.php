<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProgramCategoryController extends Controller
{
    public function index()
    {
        $categories = ProgramCategory::withCount('campaigns')
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:120|unique:program_categories,slug',
            'icon' => 'required|string|max:50',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ProgramCategory::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Pilar Program berhasil ditambahkan.');
    }

    public function edit(ProgramCategory $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, ProgramCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:120|unique:program_categories,slug,' . $category->id,
            'icon' => 'required|string|max:50',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Pilar Program berhasil diperbarui.');
    }

    public function destroy(ProgramCategory $category)
    {
        if ($category->campaigns()->count() > 0) {
            return back()->with('error', 'Pilar tidak dapat dihapus karena masih memiliki program/kampanye aktif terkait.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Pilar Program berhasil dihapus.');
    }
}
