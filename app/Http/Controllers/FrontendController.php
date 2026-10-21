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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            'campaign_id'    => 'required|exists:campaigns,id',
            'donor_name'     => 'required|string|max:150',
            'donor_phone'    => 'required|string|max:20',
            'donor_email'    => 'nullable|email|max:120',
            'alamat'         => 'nullable|string|max:255',
            'kategori'       => 'nullable|string|max:50',
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string|in:transfer_bsi,transfer_bri,transfer_mandiri,transfer_bca,qris',
            'prayer_message' => 'nullable|string|max:500',
            'is_anonymous'   => 'nullable|boolean',
        ]);

        $uniqueCode = rand(101, 899);
        $totalAmount = $validated['amount'] + $uniqueCode;
        $donationCode = 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $donation = OnlineDonation::create([
            'donation_code'   => $donationCode,
            'campaign_id'     => $validated['campaign_id'],
            'kategori'        => $validated['kategori'] ?? 'Infaq',
            'donor_name'      => $validated['donor_name'],
            'donor_phone'     => $validated['donor_phone'],
            'donor_email'     => $validated['donor_email'] ?? null,
            'alamat'          => $validated['alamat'] ?? null,
            'is_anonymous'    => !empty($validated['is_anonymous']),
            'amount'          => $validated['amount'],
            'unique_code'     => $uniqueCode,
            'total_amount'    => $totalAmount,
            'payment_method'  => $validated['payment_method'],
            'payment_status'  => 'pending',
            'prayer_message'  => $validated['prayer_message'] ?? null,
        ]);

        // Generate Dynamic QRIS jika metode pembayaran QRIS
        if ($donation->payment_method === 'qris') {
            app(\App\Services\PaymentGatewayService::class)->generateDynamicQris($donation);
        }

        return redirect()->route('donation.success', ['code' => $donation->donation_code])
            ->with('success', 'Niat donasi Anda telah tercatat. Silakan selesaikan pembayaran.');
    }

    /**
     * Halaman Instruksi Pembayaran Donasi
     */
    public function donationSuccess($code)
    {
        $donation = OnlineDonation::with('campaign')->where('donation_code', $code)->firstOrFail();
        
        // Pastikan QRIS dinamis di-generate jika belum ada
        if ($donation->payment_method === 'qris' && empty($donation->qris_url) && $donation->payment_status === 'pending') {
            app(\App\Services\PaymentGatewayService::class)->generateDynamicQris($donation);
            $donation->refresh();
        }

        $settings = SiteSetting::pluck('value', 'key')->toArray();

        return view('frontend.donation_success', compact('donation', 'settings'));
    }

    /**
     * Download File Gambar QRIS Dinamis
     */
    public function downloadQris($code)
    {
        $donation = OnlineDonation::where('donation_code', $code)->firstOrFail();
        $fileName = 'QRIS-LAZISNU-' . $donation->donation_code . '.png';

        $qrisUrl = $donation->qris_url;

        if (!empty($qrisUrl)) {
            try {
                if (filter_var($qrisUrl, FILTER_VALIDATE_URL)) {
                    $response = Http::timeout(10)->get($qrisUrl);
                    if ($response->successful()) {
                        $contentType = $response->header('Content-Type') ?: 'image/png';
                        return response($response->body(), 200, [
                            'Content-Type'        => $contentType,
                            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gagal fetch QRIS url untuk download [{$code}]: " . $e->getMessage());
            }
        }

        // Fallback jika file lokal
        $localPath = public_path('qris_lazisnu_bri.jpg');
        if (file_exists($localPath)) {
            return response()->download($localPath, 'QRIS-LAZISNU-' . $donation->donation_code . '.jpg', [
                'Content-Type' => 'image/jpeg',
            ]);
        }

        return redirect()->back()->with('error', 'Gambar QRIS belum dapat diunduh.');
    }

    /**
     * DataTables Server-Side Endpoint: Transparansi Penerimaan ZIS & KOIN NU
     */
    public function transparencyData(Request $request)
    {
        // 1. Total awal data terverifikasi (recordsTotal)
        $totalRecords = OnlineDonation::where(function ($q) {
            $q->where('payment_status', 'verified')
              ->orWhere('payment_status', 'success');
        })->count();

        // 2. Base Query: Ambil relasi kampanye termasuk kategori_judul
        $query = OnlineDonation::with(['campaign:id,title,kategori_judul,category_id', 'campaign.category:id,name'])
            ->where(function ($q) {
                $q->where('payment_status', 'verified')
                  ->orWhere('payment_status', 'success');
            });

        // 3. Filter Kategori Dropdown (Semua, Zakat, Infaq, Shadaqah, Wakaf, KOIN NU, Kampanye Bencana)
        $category = $request->input('category', $request->input('kategori'));
        if (!empty($category) && $category !== 'Semua') {
            $query->where(function ($q) use ($category) {
                $q->where('kategori', 'like', "%{$category}%")
                  ->orWhereHas('campaign', function ($camp) use ($category) {
                      $camp->where('kategori_judul', 'like', "%{$category}%")
                           ->orWhere('title', 'like', "%{$category}%")
                           ->orWhereHas('category', function ($c) use ($category) {
                               $c->where('name', 'like', "%{$category}%");
                           });
                  });
            });
        }

        // 4. Search Bar Real-Time (Nama Donatur, Alamat, Kategori Judul)
        $searchValue = $request->input('search.value');
        if (empty($searchValue)) {
            $searchValue = $request->input('search');
        }
        $searchValue = is_string($searchValue) ? trim($searchValue) : '';

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                // Keamanan privasi: jika donatur anonim, jangan bocorkan nama asli melalui search
                $q->where(function ($sub) use ($searchValue) {
                    $sub->where('donor_name', 'like', "%{$searchValue}%")
                        ->where('is_anonymous', false);
                })
                ->orWhere('alamat', 'like', "%{$searchValue}%")
                ->orWhere('donation_code', 'like', "%{$searchValue}%")
                ->orWhere('kategori', 'like', "%{$searchValue}%")
                ->orWhereHas('campaign', function ($camp) use ($searchValue) {
                    $camp->where('kategori_judul', 'like', "%{$searchValue}%")
                         ->orWhere('title', 'like', "%{$searchValue}%");
                });
            });
        }

        // 5. Total data setelah difilter (recordsFiltered)
        $filteredRecords = $query->count();

        // 6. Ordering & Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        // Default urutan terbaru
        $query->orderByDesc('verified_at')->orderByDesc('id');

        // Jika length == -1 (Semua / All), batasi sampai batas aman 5.000 untuk mencegah memory crash
        if ($length > 0) {
            $donations = $query->skip($start)->take($length)->get();
        } else {
            $donations = $query->take(5000)->get();
        }

        // 7. Format data sesuai standar DataTables
        $data = [];
        foreach ($donations as $index => $item) {
            $rowNo = $start + $index + 1;
            $nominal = $item->nominal_terverifikasi && $item->nominal_terverifikasi > 0
                ? (float) $item->nominal_terverifikasi
                : (float) $item->amount;

            $dateFormatted = ($item->verified_at ?? $item->created_at)->format('d M Y, H:i');

            // Format nama donatur dengan penyamaran "Hamba Allah"
            $donorHtml = $item->is_anonymous
                ? '<span class="badge bg-light text-secondary border px-2 py-1"><i class="fa-solid fa-user-secret me-1"></i> Hamba Allah</span>'
                : '<span class="fw-semibold text-dark"><i class="fa-solid fa-circle-user text-success me-1"></i> ' . e($item->donor_name) . '</span>';

            // Kolom KATEGORI: Judul kolom tetap "KATEGORI", tapi isinya menampilkan kategori_judul
            $kategoriJudul = $item->campaign?->kategori_judul 
                ?: ($item->campaign?->kategori_judul_display 
                ?: ($item->kategori ?: 'Infaq'));

            $badgeClass = match (strtolower($kategoriJudul)) {
                'zakat', 'zakat maal', 'zakat fitrah' => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                'koin nu', 'koin', 'koin kemandirian' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle',
                'kampanye bencana', 'bencana', 'darurat' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
                default => 'bg-success bg-opacity-10 text-success border border-success-subtle',
            };
            $kategoriHtml = '<span class="badge ' . $badgeClass . ' px-2 py-1 rounded-pill fw-semibold">' . e($kategoriJudul) . '</span>';

            // Alamat desa/kecamatan saja
            $alamatHtml = '<i class="fa-solid fa-location-dot text-danger opacity-75 me-1"></i> <span class="text-secondary">' . e($item->public_alamat) . '</span>';

            // Nominal format
            $nominalHtml = '<span class="fw-extrabold text-success font-monospace">Rp ' . number_format($nominal, 0, ',', '.') . '</span>';

            $data[] = [
                'no'           => $rowNo,
                'tanggal'      => $dateFormatted,
                'nama_donatur' => $donorHtml,
                'kategori'     => $kategoriHtml,
                'alamat'       => $alamatHtml,
                'nominal'      => $nominalHtml,
            ];
        }

        return response()->json([
            'draw'            => intval($request->input('draw', 1)),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data'            => $data,
        ]);
    }
}
