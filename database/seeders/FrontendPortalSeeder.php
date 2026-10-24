<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Campaign;
use App\Models\FinancialReport;
use App\Models\OnlineDonation;
use App\Models\ProgramCategory;
use App\Models\SiteSetting;
use App\Models\Slider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FrontendPortalSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::first();
        $adminId = $adminUser ? $adminUser->id : null;

        // 1. Program Categories (5 Pilar NU Care)
        $categoriesData = [
            [
                'id' => 1,
                'name' => 'NU Care Cerdas',
                'slug' => 'nu-care-cerdas',
                'icon' => 'fa-graduation-cap',
                'color_code' => '#1e40af',
                'description' => 'Program pendidikan meliputi beasiswa santri dhuafa, bantuan kitab kuning, renovasi madrasah/pesantren prasejahtera, dan tunjangan kehormatan bagi guru ngaji kampung.',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'NU Care Sehat',
                'slug' => 'nu-care-sehat',
                'icon' => 'fa-heart-pulse',
                'color_code' => '#dc2626',
                'description' => 'Program pelayanan kesehatan terpadu, armada mobil ambulans gratis 24 jam, khitanan massal dhuafa, dan bantuan pembiayaan operasi mustahik gawat darurat.',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'NU Care Berdaya',
                'slug' => 'nu-care-berdaya',
                'icon' => 'fa-chart-line',
                'color_code' => '#d97706',
                'description' => 'Program pemberdayaan ekonomi mustahik melalui modal usaha bergulir tanpa bunga, bantuan gerobak usaha Nahdliyin, serta pendampingan wirausaha mandiri.',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'NU Care Damai',
                'slug' => 'nu-care-damai',
                'icon' => 'fa-hand-holding-heart',
                'color_code' => '#0a6b38',
                'description' => 'Program dakwah kemanusiaan, respon tanggap darurat bencana alam, santunan rutin anak yatim & janda dhuafa, serta syiar Islam rahmatan lil \'alamin.',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'NU Care Hijau',
                'slug' => 'nu-care-hijau',
                'icon' => 'fa-leaf',
                'color_code' => '#16a34a',
                'description' => 'Kepedulian kelestarian lingkungan hidup lewat gerakan Sedekah Sampah Berkah, sanitasi air bersih pesantren, dan penanaman pohon penghijauan bumi.',
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($categoriesData as $cat) {
            ProgramCategory::updateOrCreate(['id' => $cat['id']], $cat);
        }

        // 2. Sliders Utama
        $sliders = [
            [
                'title' => 'Gerakan Koin NU: Dari Umat, Oleh Umat, Kembali ke Umat',
                'tagline' => 'Satu koin receh yang disisihkan dengan ikhlas setiap fajar menjadi jalan kemandirian pendidikan santri dan kesehatan ribuan dhuafa.',
                'image_desktop' => 'https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?q=80&w=1200&auto=format&fit=crop',
                'button_text' => 'Tunaikan Infaq Koin',
                'button_url' => '#donasi-cepat',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'Layanan Mobil Ambulans Gratis Siaga 24 Jam Tanpa Syarat',
                'tagline' => 'Armada kemanusiaan LAZISNU siap mengantar jemput pasien rujukan, jenazah, dan tanggap darurat bencana untuk seluruh lapisan masyarakat.',
                'image_desktop' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?q=80&w=1200&auto=format&fit=crop',
                'button_text' => 'Sedekah Operasional Ambulans',
                'button_url' => '#program-pilihan',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Kemandirian Pesantren & Beasiswa Generasi Emas Nahdliyin',
                'tagline' => 'Dukung santri-santri penghafal Al-Qur\'an dan kader ulama nusantara agar tetap dapat menuntut ilmu dengan fasilitas yang layak dan bermartabat.',
                'image_desktop' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?q=80&w=1200&auto=format&fit=crop',
                'button_text' => 'Dukung Beasiswa Santri',
                'button_url' => '#program-pilihan',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        Slider::truncate();
        foreach ($sliders as $s) {
            Slider::create($s);
        }

        // 3. Campaigns Penggalangan Dana
        $campaigns = [
            [
                'category_id' => 4,
                'title' => 'Gerakan Masif Kaleng Koin Infaq NU untuk Kemandirian Umat',
                'slug' => 'gerakan-masif-kaleng-koin-infaq-nu',
                'short_description' => 'Program pengumpulan koin infaq subuh di setiap rumah warga Nahdliyin untuk mendanai kemandirian organisasi, santunan mustahik, dan aksi sosial.',
                'story_description' => '<p>Gerakan Koin NU adalah ruh kemandirian Jam\'iyyah Nahdlatul Ulama. Melalui kaleng koin yang ditempatkan di rumah-rumah warga, setiap recehan yang dimasukkan dengan niat ikhlas menjadi kekuatan dahsyat untuk membiayai operasional dakwah, membebaskan dhuafa dari jerat rentenir, dan menopang keluarga yang sedang ditimpa musibah.</p><p>Hasil perolehan koin dihitung secara terbuka setiap bulan di hadapan pengurus ranting dan disalurkan kembali dalam bentuk program 5 pilar NU Care.</p>',
                'target_amount' => 500000000.00,
                'collected_amount' => 318450000.00,
                'banner_image' => 'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?q=80&w=800&auto=format&fit=crop',
                'start_date' => Carbon::now()->subMonths(3)->toDateString(),
                'end_date' => null, // Berkelanjutan
                'status' => 'active',
                'is_featured' => true,
                'is_emergency' => false,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 2,
                'title' => 'Bahan Bakar & Perawatan Rutin Armada Ambulans Siaga Umat 24 Jam',
                'slug' => 'bahan-bakar-dan-perawatan-rutin-ambulans-siaga-24-jam',
                'short_description' => 'Bantu biaya operasional bensin, servis berkala, dan oksigen medis ambulans LAZISNU agar tetap gratis melayani pasien dhuafa setiap saat.',
                'story_description' => '<p>Mobil Ambulans LAZISNU melayani puluhan pasien dhuafa setiap pekannya untuk rujukan ke rumah sakit daerah maupun luar kota. Layanan ini diberikan 100% GRATIS tanpa dipungut biaya sepeser pun kepada kaum yang membutuhkan.</p><p>Sedekah Anda memastikan armada ini selalu siap meluncur kapanpun panggilan darurat berdering di call center kami.</p>',
                'target_amount' => 85000000.00,
                'collected_amount' => 61250000.00,
                'banner_image' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?q=80&w=800&auto=format&fit=crop',
                'start_date' => Carbon::now()->subMonths(2)->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->toDateString(),
                'status' => 'active',
                'is_featured' => true,
                'is_emergency' => true,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 1,
                'title' => 'Beasiswa Pendidikan Santri Tahfidz Dhuafa & Anak Yatim',
                'slug' => 'beasiswa-pendidikan-santri-tahfidz-dhuafa-dan-anak-yatim',
                'short_description' => 'Dukungan biaya kitab, syahriah bulanan pesantren, dan perlengkapan belajar bagi 150 santri berprestasi dari keluarga prasejahtera.',
                'story_description' => '<p>Banyak anak yatim dan dhuafa memiliki tekad kuat mendalami ilmu agama dan menghafal Al-Qur\'an di pesantren, namun terhambat biaya hidup. LAZISNU melalui pilar NU Care Cerdas berkomitmen mendampingi mereka hingga tuntas mengabdi kepada masyarakat.</p>',
                'target_amount' => 120000000.00,
                'collected_amount' => 84300000.00,
                'banner_image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=800&auto=format&fit=crop',
                'start_date' => Carbon::now()->subMonths(1)->toDateString(),
                'end_date' => Carbon::now()->addMonths(6)->toDateString(),
                'status' => 'active',
                'is_featured' => true,
                'is_emergency' => false,
                'created_by' => $adminId,
            ],
            [
                'category_id' => 3,
                'title' => 'Bantuan Modal Usaha Tanpa Riba & Gerobak Mandiri Berdaya',
                'slug' => 'bantuan-modal-usaha-tanpa-riba-gerobak-mandiri-berdaya',
                'short_description' => 'Pemberian sarana usaha gerobak dan suntikan modal bibit dagang bagi kepala keluarga dhuafa agar mandiri secara ekonomi.',
                'story_description' => '<p>Melalui pilar NU Care Berdaya, kami mendampingi pedagang kecil dan buruh serabutan untuk memiliki usaha mandiri berkelanjutan dengan dukungan modal usaha zakat produktif dan pembinaan manajemen pembukuan sederhana.</p>',
                'target_amount' => 95000000.00,
                'collected_amount' => 43700000.00,
                'banner_image' => 'https://images.unsplash.com/photo-1556742049-0a67c5574f73?q=80&w=800&auto=format&fit=crop',
                'start_date' => Carbon::now()->subMonths(1)->toDateString(),
                'end_date' => Carbon::now()->addMonths(5)->toDateString(),
                'status' => 'active',
                'is_featured' => false,
                'is_emergency' => false,
                'created_by' => $adminId,
            ],
        ];

        Campaign::truncate();
        foreach ($campaigns as $camp) {
            $createdCamp = Campaign::create($camp);

            // Tambahkan contoh beberapa donatur online
            OnlineDonation::create([
                'donation_code' => 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'campaign_id' => $createdCamp->id,
                'donor_name' => 'H. Ahmad Syarifuddin',
                'donor_phone' => '081234567891',
                'is_anonymous' => false,
                'amount' => 250000.00,
                'unique_code' => 124,
                'total_amount' => 250124.00,
                'payment_method' => 'transfer_bsi',
                'payment_status' => 'verified',
                'prayer_message' => 'Semoga berkah untuk kemaslahatan umat dan kesehatan keluarga.',
                'verified_at' => Carbon::now()->subHours(2),
                'verified_by' => $adminId,
            ]);

            OnlineDonation::create([
                'donation_code' => 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'campaign_id' => $createdCamp->id,
                'donor_name' => 'Hamba Allah',
                'donor_phone' => '085712349999',
                'is_anonymous' => true,
                'amount' => 100000.00,
                'unique_code' => 88,
                'total_amount' => 100088.00,
                'payment_method' => 'qris',
                'payment_status' => 'verified',
                'prayer_message' => 'Niat infaq untuk almarhum orang tua, semoga husnul khotimah.',
                'verified_at' => Carbon::now()->subHours(5),
                'verified_by' => $adminId,
            ]);
        }

        // 4. Articles & Kabar Pentasyarufan
        $articles = [
            [
                'category_id' => 4,
                'title' => 'LAZISNU Salurkan Paket Sembako & Santunan 250 Anak Yatim di Gedung PCNU',
                'slug' => 'lazisnu-salurkan-paket-sembako-santunan-250-anak-yatim',
                'post_type' => 'pentasyarufan',
                'excerpt' => 'Hasil perolehan Koin NU yang dihimpun relawan ranting disalurkan kembali dalam bentuk santunan uang tunai dan paket sembako berkah bagi keluarga dhuafa.',
                'content' => '<p>Sebagai wujud pertanggungjawaban amanah publik, NU Care - LAZISNU kembali menyelenggarakan agenda pentasyarufan akbar. Kegiatan yang dihadiri oleh jajaran Syuriah dan Tanfidziyah PCNU ini menyalurkan bantuan kepada 250 anak yatim dan dhuafa yang tersebar di wilayah MWC.</p><p>Ketua LAZISNU menyampaikan terima kasih setinggi-tingginya kepada para munfiq dan donatur yang secara istiqomah mengisi kaleng koin di rumah masing-masing. "Koin yang mungkin dianggap kecil, ketika disatukan dengan keikhlasan, menjelma menjadi senyuman anak-anak yatim dan keringanan bagi fakir miskin," tuturnya.</p>',
                'thumbnail_image' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=800&auto=format&fit=crop',
                'author_id' => $adminId,
                'status' => 'published',
                'published_at' => Carbon::now()->subDays(2),
                'views_count' => 428,
            ],
            [
                'category_id' => 2,
                'title' => 'Ambulans LAZISNU Sigap Layani Antar Rujukan Pasien Lansia ke RSUD Provinsi',
                'slug' => 'ambulans-lazisnu-sigap-layani-antar-rujukan-pasien-lansia',
                'post_type' => 'pentasyarufan',
                'excerpt' => 'Tim relawan driver ambulans siaga bergerak pukul 02.00 dini hari mengantar warga prasejahtera yang memerlukan penanganan medis intensif tanpa biaya.',
                'content' => '<p>Panggilan darurat masuk ke layanan hotline LAZISNU mengabarkan adanya pasien lansia dengan kondisi kritis yang membutuhkan rujukan mendesak ke rumah sakit pusat. Armada siaga langsung diberangkatkan lengkap dengan tabung oksigen dan peralatan darurat.</p><p>Pihak keluarga menyampaikan rasa haru dan terima kasih atas kesigapan tim LAZISNU yang mengantar hingga selesai registrasi tanpa meminta imbalan apapun.</p>',
                'thumbnail_image' => 'https://images.unsplash.com/photo-1587745416684-47953f16f02f?q=80&w=800&auto=format&fit=crop',
                'author_id' => $adminId,
                'status' => 'published',
                'published_at' => Carbon::now()->subDays(4),
                'views_count' => 615,
            ],
            [
                'category_id' => 1,
                'title' => 'Panduan Fiqih ZIS: Cara Praktis Menghitung Zakat Penghasilan & Nisab Emas 2026',
                'slug' => 'panduan-fiqih-zis-cara-praktis-menghitung-zakat-penghasilan-2026',
                'post_type' => 'edukasi_zis',
                'excerpt' => 'Ulasan ringkas syarat nisab zakat profesi, batasan haul, serta rumus perhitungan 2,5% setelah dikurangi kebutuhan pokok sesuai keputusan Bahtsul Masail.',
                'content' => '<p>Zakat penghasilan atau zakat profesi merupakan kewajiban bagi setiap muslim yang telah mencapai nisab setara 85 gram emas murni dalam setahun. Dengan harga emas saat ini di kisaran Rp 1.450.000 per gram, maka nisab tahunan adalah Rp 123.250.000 atau sekitar Rp 10.270.000 per bulan.</p><p>Apabila penghasilan bersih seseorang telah mencapai angka tersebut, maka dikeluarkan zakatnya sebesar 2,5% demi menyucikan harta dan membantu hak kaum mustahik.</p>',
                'thumbnail_image' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?q=80&w=800&auto=format&fit=crop',
                'author_id' => $adminId,
                'status' => 'published',
                'published_at' => Carbon::now()->subDays(6),
                'views_count' => 1420,
            ],
            [
                'category_id' => 3,
                'title' => 'Kemandirian Ranting NU Menguat Lewat Pengelolaan Koin Infaq yang Akuntabel',
                'slug' => 'kemandirian-ranting-nu-menguat-lewat-pengelolaan-koin-infaq',
                'post_type' => 'berita',
                'excerpt' => 'Pemanfaatan sistem digital pencatatan koin membuat transparansi perolehan meningkat drastis dan memperkuat kepercayaan warga Nahdliyin.',
                'content' => '<p>Penggunaan aplikasi manajemen Koin NU di tingkat ranting dan MWC telah membawa perubahan signifikan. Donatur mendapatkan kepastian bahwa infaq receh mereka tercatat dengan rapi dan disalurkan secara profesional sesuai kaidah syariah.</p>',
                'thumbnail_image' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=800&auto=format&fit=crop',
                'author_id' => $adminId,
                'status' => 'published',
                'published_at' => Carbon::now()->subDays(9),
                'views_count' => 380,
            ],
        ];

        Article::truncate();
        foreach ($articles as $art) {
            Article::create($art);
        }

        // 5. Financial Reports (Transparansi Publik)
        $reports = [
            [
                'title' => 'Laporan Pertanggungjawaban Keuangan & Penyaluran Koin Tahunan 2025',
                'report_year' => 2025,
                'report_period' => 'Tahunan (Audit KAP)',
                'total_inflow' => 3245600000.00,
                'total_outflow' => 3110250000.00,
                'audit_status' => 'Opini Wajar Tanpa Pengecualian (WTP)',
                'file_pdf' => '#',
                'published_at' => '2026-01-15',
            ],
            [
                'title' => 'Laporan Akuntabilitas Penyaluran Program Ramadhan Berkah 1447 H',
                'report_year' => 2026,
                'report_period' => 'Khusus Ramadhan',
                'total_inflow' => 450000000.00,
                'total_outflow' => 442500000.00,
                'audit_status' => 'Terverifikasi Tim Pengawas Internal',
                'file_pdf' => '#',
                'published_at' => '2026-04-20',
            ],
            [
                'title' => 'Laporan Perolehan & Pentasyarufan Koin NU Semester I 2026',
                'report_year' => 2026,
                'report_period' => 'Semester I 2026',
                'total_inflow' => 1650800000.00,
                'total_outflow' => 1520400000.00,
                'audit_status' => 'Transparan & Akuntabel',
                'file_pdf' => '#',
                'published_at' => '2026-07-10',
            ],
        ];

        FinancialReport::truncate();
        foreach ($reports as $rep) {
            FinancialReport::create($rep);
        }

        // 6. Site Settings
        $settings = [
            'site_name' => 'NU Care - LAZISNU',
            'site_tagline' => 'Berkhidmat untuk Kemandirian Umat, Mantap Melayani Dhuafa',
            'branch_name' => 'Pengurus Cabang Nahdlatul Ulama Lamongan',
            'phone_wa_hotline' => '0851 8899 1926',
            'email_support' => 'lazisnulamongan@gmail.com',
            'office_address' => 'Jl. Kyai Amin No 9 Lamongan 62213',
            'office_hours' => 'Senin - Sabtu: 08.00 - 16.00 WIB (Layanan & Ambulans 24 Jam)',
            'legal_kemenag' => 'Izin Operasional SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Resmi BAZNAS',
            'audit_opinion' => 'Wajar Tanpa Pengecualian (WTP)',
            'gold_price_per_gram' => '1450000',
            'website_url' => 'https://lazisnulamongan.or.id',
            'instagram_url' => 'https://www.instagram.com/lazisnulamongan?xtok=MXN1aXRja2MxM3Ricw==',
            'bank_bsi_zakat' => '2026070708',
            'bank_bsi_zakat_name' => 'Lazisnu Lamongan',
            'bank_bri_infaq' => '004101001448562',
            'bank_bri_infaq_name' => 'LAZISNU LAMONGAN',
            'bank_bsi_koin' => '2026070708',
            'bank_bsi_koin_name' => 'Lazisnu Lamongan',
            'bank_bri_zakat' => '2026070708',
            'bank_bri_zakat_name' => 'Lazisnu Lamongan',
        ];

        foreach ($settings as $key => $val) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $val, 'group_name' => 'general']);
        }
    }
}
