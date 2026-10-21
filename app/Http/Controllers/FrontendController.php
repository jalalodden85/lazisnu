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
        // 1. Data Statistik Riil & Rekapitulasi 3 Kategori Pemasukan + Grand Total
        $pemasukanSummary  = app(\App\Services\DonationSummaryService::class)->getSummaryTotals();
        $totalZakat        = $pemasukanSummary['total_zakat'];
        $totalInfaqSedekah = $pemasukanSummary['total_infaq_sedekah'];
        $totalKoinNu       = $pemasukanSummary['total_koin_nu'];
        $grandTotal        = $pemasukanSummary['grand_total'];

        $koinTotal         = $totalKoinNu;
        $onlineTotal       = $totalZakat + $totalInfaqSedekah;
        $totalDanaHimpunan = $grandTotal;

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

        $infaqCampaign = Campaign::where('status', 'active')
            ->where(function ($q) {
                $q->where('kategori_judul', 'like', '%infaq%')
                  ->orWhere('title', 'like', '%infaq%');
            })->first() ?? $featuredCampaigns->first() ?? Campaign::first();

        $zakatCampaign = Campaign::where('status', 'active')
            ->where(function ($q) {
                $q->where('kategori_judul', 'like', '%zakat%')
                  ->orWhere('title', 'like', '%zakat%')
                  ->orWhereHas('category', function ($c) {
                      $c->where('name', 'like', '%zakat%');
                  });
            })->first() ?? $featuredCampaigns->first() ?? Campaign::first();

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
            'totalZakat',
            'totalInfaqSedekah',
            'totalKoinNu',
            'grandTotal',
            'pemasukanSummary',
            'totalKotak',
            'totalRelawan',
            'totalRanting',
            'totalMwc',
            'penerimaManfaat',
            'sliders',
            'categories',
            'featuredCampaigns',
            'infaqCampaign',
            'zakatCampaign',
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
     * Proses Donasi Online Cepat (Infaq / Zakat / Crowdfunding)
     */
    public function storeDonation(Request $request)
    {
        $validated = $request->validate([
            'campaign_id'    => 'required|exists:campaigns,id',
            'donor_name'     => 'required|string|max:150',
            'donor_phone'    => 'required|string|max:20',
            'donor_email'    => 'nullable|email|max:120',
            'alamat'         => 'nullable|string|max:255',
            'kategori'       => 'nullable|string|max:150',
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string|in:transfer_bsi,transfer_bri,transfer_mandiri,transfer_bca,qris,qris_bri,qris_bni,qris_bsi',
            'prayer_message' => 'nullable|string|max:500',
            'is_anonymous'   => 'nullable|boolean',
        ]);

        $campaign = Campaign::with('category')->findOrFail($validated['campaign_id']);

        // Kategori Dinamis:
        // 1. Jika berasal dari halaman kampanye/program spesifik, ambil kategori judul kampanye (contoh: "Donasi NTT", "Masjid Gus Dur").
        // 2. Jika dari form donasi kilat: prioritaskan input tab 'Zakat' / 'Infaq'.
        // 3. Fallback ke judul kampanye atau kategori pilar.
        $rawKategori = !empty($validated['kategori']) ? trim((string) $validated['kategori']) : '';

        if (!empty($rawKategori) && !in_array(strtolower($rawKategori), ['infaq', 'zakat'])) {
            // Kategori kustom dari kampanye spesifik (contoh: "Donasi NTT", "Masjid Gus Dur")
            $kategori = $rawKategori;
        } elseif (!empty($rawKategori)) {
            // Berasal dari tab donasi kilat ("zakat" atau "infaq")
            $kategori = strtolower($rawKategori);
        } else {
            // Fallback ditarik langsung dari data kampanye admin
            $kategori = $campaign->kategori_judul ?: ($campaign->category?->name ?: 'infaq');
        }

        $isZakat = (bool) preg_match('/zakat/i', $kategori);
        $method = $validated['payment_method'];

        // Aturan Validasi Rekening Backend: Tolak manipulasi rekening silang antara Zakat dan Infaq
        if ($isZakat) {
            if (!in_array($method, ['transfer_bsi', 'qris_bsi'], true)) {
                return back()->withInput()->withErrors([
                    'payment_method' => 'Penyaluran Zakat wajib menggunakan rekening khusus Zakat (Transfer Bank BSI atau QRIS BSI).'
                ]);
            }
        } else {
            if (!in_array($method, ['transfer_bri', 'qris', 'qris_bri', 'qris_bni'], true)) {
                return back()->withInput()->withErrors([
                    'payment_method' => 'Penyaluran program donasi & infaq wajib menggunakan rekening selain zakat (Transfer Bank BRI atau QRIS BRI).'
                ]);
            }
        }

        $uniqueCode = rand(101, 899);
        $totalAmount = $validated['amount'] + $uniqueCode;
        $donationCode = 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));

        $donation = OnlineDonation::create([
            'donation_code'   => $donationCode,
            'campaign_id'     => $campaign->id,
            'kategori'        => $kategori,
            'donor_name'      => $validated['donor_name'],
            'donor_phone'     => $validated['donor_phone'],
            'donor_email'     => $validated['donor_email'] ?? null,
            'alamat'          => $validated['alamat'] ?? null,
            'is_anonymous'    => !empty($validated['is_anonymous']),
            'amount'          => $validated['amount'],
            'unique_code'     => $uniqueCode,
            'total_amount'    => $totalAmount,
            'payment_method'  => $method,
            'payment_status'  => 'pending',
            'prayer_message'  => $validated['prayer_message'] ?? null,
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

    /**
     * Download File Gambar QRIS Statis Bank BRI / BSI Sesuai Kategori
     */
    public function downloadQris($code)
    {
        $donation = OnlineDonation::where('donation_code', $code)->first();
        $isZakat = $donation && ($donation->payment_method === 'qris_bsi' || preg_match('/zakat/i', $donation->kategori));

        if ($isZakat) {
            $bsiPath = public_path('qris_lazisnu_bsi.jpg');
            if (file_exists($bsiPath)) {
                return response()->download($bsiPath, 'QRIS_LAZISNU_BSI_ZAKAT_LAMONGAN.jpg', [
                    'Content-Type' => 'image/jpeg',
                ]);
            }
        }

        $briPath = public_path('qris_lazisnu_bri.jpg');
        if (file_exists($briPath)) {
            return response()->download($briPath, 'QRIS_LAZISNU_BRI_LAMONGAN.jpg', [
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

            // Kolom KATEGORI: Prioritaskan kategori judul kampanye spesifik (contoh: "Donasi NTT", "Masjid Gus Dur"), lalu kategori transaksi (Zakat / Infaq)
            if (!empty($item->campaign?->kategori_judul) 
                && !in_array(strtolower($item->campaign->kategori_judul), ['infaq', 'infaq nu', 'koin nu', 'zakat'])
                && strtolower((string) $item->kategori) !== 'zakat') {
                $kategoriJudul = $item->campaign->kategori_judul;
            } elseif (!empty($item->kategori)) {
                $kategoriJudul = ucfirst($item->kategori);
            } else {
                $kategoriJudul = $item->campaign?->kategori_judul 
                    ?: ($item->campaign?->kategori_judul_display 
                    ?: 'Infaq');
            }

            $badgeClass = match (strtolower($kategoriJudul)) {
                'zakat', 'zakat maal', 'zakat fitrah' => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                'koin nu', 'koin', 'koin kemandirian' => 'bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle',
                'kampanye bencana', 'bencana', 'darurat', 'donasi ntt' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
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
