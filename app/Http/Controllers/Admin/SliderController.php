<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    public function index()
    {
        $sliders = Slider::orderBy('sort_order')->get();
        return view('admin.sliders.index', compact('sliders'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'tagline' => 'nullable|string|max:255',
            'image_desktop' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|max:3072',
            'button_text' => 'nullable|string|max:50',
            'button_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('sliders', 'public');
            $validated['image_desktop'] = '/storage/' . $path;
        } elseif (empty($validated['image_desktop'])) {
            $validated['image_desktop'] = 'https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?q=80&w=1200&auto=format&fit=crop';
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['button_text'] = $validated['button_text'] ?: 'Donasi Sekarang';
        $validated['button_url'] = $validated['button_url'] ?: '#donasi-cepat';

        Slider::create($validated);

        return redirect()->route('admin.sliders.index')->with('success', 'Banner Slider berhasil ditambahkan.');
    }

    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    public function update(Request $request, Slider $slider)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'tagline' => 'nullable|string|max:255',
            'image_desktop' => 'nullable|string|max:255',
            'image_file' => 'nullable|image|max:3072',
            'button_text' => 'nullable|string|max:50',
            'button_url' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('sliders', 'public');
            $validated['image_desktop'] = '/storage/' . $path;
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $slider->update($validated);

        return redirect()->route('admin.sliders.index')->with('success', 'Banner Slider berhasil diperbarui.');
    }

    public function destroy(Slider $slider)
    {
        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'Banner Slider berhasil dihapus.');
    }
}
