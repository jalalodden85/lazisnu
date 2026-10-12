<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\Donator;
use App\Models\FinancialReport;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\OnlineDonation;
use App\Models\ProgramCategory;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\Mustahiq;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    /**
     * Halaman Beranda Utama (Landing Page Portal NU Care)
     */
    public function index()
    {
        // 1. Data Statistik Riil & Terintegrasi
        $koinTotal = KoinTransaction::where('status', 'confirmed')->sum('amount');
        $onlineTotal = OnlineDonation::where('payment_status', 'verified')->sum('amount');
        $totalDanaHimpunan = $koinTotal + $onlineTotal;

        $totalKotak = Donator::count();
        $totalRelawan = Relawan::where('status', 'active')->count();
        if ($totalRelawan === 0) {
            $totalRelawan = Relawan::count();
        }
        $totalRanting = Ranting::where('is_active', true)->count();
        if ($totalRanting === 0) {
            $totalRanting = Ranting::count();
        }
        $totalMwc = Mwc::count();

        // Penerima Manfaat Riil / Mustahiq Aktif Terdata
        $totalMustahiqAktif = Mustahiq::where('status', 'aktif')->count();
        $totalPenerimaRiil  = DB::table('pentasyarufan_mustahiq')->distinct()->count('mustahiq_id');
        $penerimaManfaat    = $totalMustahiqAktif > 0 ? $totalMustahiqAktif : $totalPenerimaRiil;

        // 2. Banner Slider
        $sliders = Slider::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 3. 5 Pilar NU Care
        $categories = ProgramCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->withCount(['campaigns' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get();

        // 4. Kampanye Donasi Unggulan & Aktif
        $featuredCampaigns = Campaign::with('category')
            ->where('status', 'active')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        // 5. Kabar Pentasyarufan & Berita Terkini (5 default, dan daftar kabar untuk filter)
        $allArticles = Article::with('category')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->take(15)
            ->get();
        $latestArticles = $allArticles->take(5);

        // 6. Laporan Transparansi Keuangan
        $financialReports = FinancialReport::orderByDesc('report_year')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        // 7. Pengaturan Situs & Rekening
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.index', compact(
            'koinTotal',
            'totalDanaHimpunan',
            'totalKotak',
            'totalRelawan',
            'totalRanting',
            'totalMwc',
            'penerimaManfaat',
            'sliders',
            'categories',
            'featuredCampaigns',
            'latestArticles',
            'allArticles',
            'financialReports',
            'settings'
        ));
    }

    /**
     * Halaman Daftar Semua Kampanye Donasi
     */
    public function programs(Request $request)
    {
        $query = Campaign::with('category')->where('status', 'active');

        if ($request->filled('pilar')) {
            $category = ProgramCategory::where('slug', $request->pilar)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('short_description', 'like', "%{$keyword}%");
            });
        }

        $campaigns = $query->orderByDesc('is_featured')->orderByDesc('created_at')->paginate(9);
        $categories = ProgramCategory::where('is_active', true)->orderBy('sort_order')->get();
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.programs', compact('campaigns', 'categories', 'settings'));
    }

    /**
     * Halaman Detail Kampanye & Form Donasi
     */
    public function programDetail($slug)
    {
        $campaign = Campaign::with(['category', 'donations' => function ($q) {
            $q->where('payment_status', 'verified')->orderByDesc('created_at')->take(10);
        }])->where('slug', $slug)->firstOrFail();

        $relatedCampaigns = Campaign::where('id', '!=', $campaign->id)
            ->where('category_id', $campaign->category_id)
            ->where('status', 'active')
            ->take(3)
            ->get();

        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.program_detail', compact('campaign', 'relatedCampaigns', 'settings'));
    }

    /**
     * Halaman Daftar Berita & Kabar Pentasyarufan
     */
    public function articles(Request $request)
    {
        $query = Article::with('category')->where('status', 'published');

        if ($request->filled('tipe')) {
            $query->where('post_type', $request->tipe);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('excerpt', 'like', "%{$keyword}%");
            });
        }

        $articles = $query->orderByDesc('published_at')->paginate(9);
        $categories = ProgramCategory::where('is_active', true)->get();
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.articles', compact('articles', 'categories', 'settings'));
    }

    /**
     * Halaman Detail Berita
     */
    public function articleDetail($slug)
    {
        $article = Article::with(['category', 'author'])->where('slug', $slug)->firstOrFail();
        $article->increment('views_count');

        $relatedArticles = Article::where('id', '!=', $article->id)
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.article_detail', compact('article', 'relatedArticles', 'settings'));
    }

    /**
     * Halaman Kalkulator Zakat Komprehensif
     */
    public function calculator()
    {
        $settings = SiteSetting::pluck('value', 'key')->toArray();
        $goldPrice = (float) ($settings['gold_price_per_gram'] ?? 1450000);

        // Nisab 85 gram emas
        $nisabYearly = $goldPrice * 85;
        $nisabMonthly = $nisabYearly / 12;

        return view('frontend.calculator', compact('settings', 'goldPrice', 'nisabYearly', 'nisabMonthly'));
    }

    /**
     * Halaman Laporan Publik Transparansi & Akuntabilitas
     */
    public function reports()
    {
        $reports = FinancialReport::orderByDesc('report_year')
            ->orderByDesc('published_at')
            ->paginate(12);

        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.reports', compact('reports', 'settings'));
    }

    /**
     * Proses Donasi Online Cepat (Infaq / Crowdfunding)
     */
    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'donor_name' => 'required|string|max:150',
            'donor_phone' => 'required|string|max:20',
            'donor_email' => 'nullable|email|max:120',
            'amount' => 'required|numeric|min:10000',
            'payment_method' => 'required|string|in:transfer_bsi,transfer_bri,transfer_mandiri,transfer_bca,qris',
            'prayer_message' => 'nullable|string|max:500',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $uniqueCode = rand(101, 899);
        $totalAmount = $validated['amount'] + $uniqueCode;
        $donationCode = 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $donation = OnlineDonation::create([
            'donation_code' => $donationCode,
            'campaign_id' => $validated['campaign_id'],
            'donor_name' => $validated['donor_name'],
            'donor_phone' => $validated['donor_phone'],
            'donor_email' => $validated['donor_email'] ?? null,
            'is_anonymous' => !empty($validated['is_anonymous']),
            'amount' => $validated['amount'],
            'unique_code' => $uniqueCode,
            'total_amount' => $totalAmount,
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
            'prayer_message' => $validated['prayer_message'] ?? null,
        ]);

        return redirect()->route('donation.success', ['code' => $donation->donation_code])
            ->with('success', 'Niat donasi Anda telah tercatat. Silakan selesaikan pembayaran.');
    }

    /**
     * Halaman Instruksi Pembayaran Donasi
     */
    public function donationSuccess($code)
    {
        $donation = OnlineDonation::with('campaign')->where('donation_code', $code)->firstOrFail();
        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.donation_success', compact('donation', 'settings'));
    }
}
