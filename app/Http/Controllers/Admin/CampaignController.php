<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Mwc;
use App\Models\ProgramCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::with(['category', 'mwc'])->withCount('donations');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('short_description', 'like', "%{$q}%");
            });
        }

        $campaigns = $query->orderByDesc('is_featured')->orderByDesc('created_at')->paginate(10);
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.campaigns.index', compact('campaigns', 'categories'));
    }

    public function create()
    {
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();

        return view('admin.campaigns.create', compact('categories', 'mwcs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:program_categories,id',
            'mwc_id' => 'nullable|exists:mwcs,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:campaigns,slug',
            'short_description' => 'required|string|max:350',
            'story_description' => 'required|string',
            'target_amount' => 'required|numeric|min:100000',
            'collected_amount' => 'nullable|numeric|min:0',
            'banner_image' => 'nullable|string|max:255',
            'banner_file' => 'nullable|image|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed,suspended',
            'is_featured' => 'nullable|boolean',
            'is_emergency' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']) . '-' . rand(100, 999);
        }

        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $validated['banner_image'] = '/storage/' . $path;
        } elseif (empty($validated['banner_image'])) {
            $validated['banner_image'] = 'https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?q=80&w=800&auto=format&fit=crop';
        }

        $validated['collected_amount'] = $validated['collected_amount'] ?? 0;
        $validated['is_featured'] = $request->has('is_featured');
        $validated['is_emergency'] = $request->has('is_emergency');
        $validated['created_by'] = Auth::id();

        Campaign::create($validated);

        return redirect()->route('admin.campaigns.index')->with('success', 'Kampanye Donasi berhasil diterbitkan.');
    }

    public function edit(Campaign $campaign)
    {
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();

        return view('admin.campaigns.edit', compact('campaign', 'categories', 'mwcs'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:program_categories,id',
            'mwc_id' => 'nullable|exists:mwcs,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:campaigns,slug,' . $campaign->id,
            'short_description' => 'required|string|max:350',
            'story_description' => 'required|string',
            'target_amount' => 'required|numeric|min:100000',
            'collected_amount' => 'nullable|numeric|min:0',
            'banner_image' => 'nullable|string|max:255',
            'banner_file' => 'nullable|image|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:active,completed,suspended',
            'is_featured' => 'nullable|boolean',
            'is_emergency' => 'nullable|boolean',
        ]);

        if ($request->hasFile('banner_file')) {
            $path = $request->file('banner_file')->store('campaigns', 'public');
            $validated['banner_image'] = '/storage/' . $path;
        }

        $validated['collected_amount'] = $validated['collected_amount'] ?? $campaign->collected_amount;
        $validated['is_featured'] = $request->has('is_featured');
        $validated['is_emergency'] = $request->has('is_emergency');

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.index')->with('success', 'Kampanye Donasi berhasil diperbarui.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Kampanye Donasi berhasil dihapus.');
    }

    /**
     * Tampilkan detail kampanye beserta daftar donasinya untuk verifikasi
     */
    public function show(Campaign $campaign)
    {
        $campaign->load([
            'category',
            'mwc',
            'donations' => fn($q) => $q->with('verifier')->latest(),
        ]);

        return view('admin.campaigns.show', compact('campaign'));
    }

    /**
     * Endpoint Verifikasi Donasi dari Modul Kampanye
     * Sinkron otomatis ke online_donations dan collected_amount kampanye.
     */
    public function verifyDonation(Request $request, \App\Services\DonationVerificationService $service)
    {
        $validated = $request->validate([
            'kode_donasi'           => 'required_without:donation_code|nullable|string',
            'donation_code'         => 'required_without:kode_donasi|nullable|string',
            'nominal_terverifikasi' => 'nullable|numeric|min:1000',
        ]);

        $kodeDonasi = $validated['kode_donasi'] ?? $validated['donation_code'];
        $nominal = isset($validated['nominal_terverifikasi']) ? (float) $validated['nominal_terverifikasi'] : null;

        try {
            $result = $service->verifyByCode($kodeDonasi, $nominal);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'               => true,
                    'status'                => 'verified',
                    'kode_donasi'           => $kodeDonasi,
                    'nominal_terverifikasi' => $result['nominal_terverifikasi'],
                    'collected_amount'      => $result['campaign']->collected_amount,
                    'progress_percentage'   => $result['campaign']->progress_percentage,
                    'message'               => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Endpoint Pembatalan Verifikasi Donasi dari Modul Kampanye
     */
    public function cancelDonation(Request $request, \App\Services\DonationVerificationService $service)
    {
        $validated = $request->validate([
            'kode_donasi'   => 'required_without:donation_code|nullable|string',
            'donation_code' => 'required_without:kode_donasi|nullable|string',
        ]);

        $kodeDonasi = $validated['kode_donasi'] ?? $validated['donation_code'];

        try {
            $result = $service->cancelByCode($kodeDonasi);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'             => true,
                    'status'              => 'cancelled',
                    'kode_donasi'         => $kodeDonasi,
                    'collected_amount'    => $result['campaign']->collected_amount,
                    'progress_percentage' => $result['campaign']->progress_percentage,
                    'message'             => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
