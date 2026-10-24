# BUKU PANDUAN OPERASIONAL SISTEM & APLIKASI
## SISTEM INFORMASI MANAJEMEN KOIN NU & PORTAL NU CARE - LAZISNU LAMONGAN
**Versi Dokumen:** 2.0.0 | **Edisi:** Oktober 2026  
**Penerbit:** Pengurus Cabang Nahdlatul Ulama (PCNU) Lamongan - Lembaga Amil Zakat, Infaq, dan Shadaqah Nahdlatul Ulama (NU Care - LAZISNU)

---

## DAFTAR ISI
1. [Informasi Dokumen & Pendahuluan](#1-informasi-dokumen--pendahuluan)
   - 1.1 Identitas Dokumen & Hak Cipta
   - 1.2 Tujuan & Manfaat Buku Panduan
   - 1.3 Ketentuan Hak Akses & Keamanan Akun
   - 1.4 Panduan Membaca Manual (Ikon & Notasi)
2. [Identifikasi Peran Pengguna (Roles)](#2-identifikasi-peran-pengguna-roles)
   - 2.1 Matriks Wewenang & Hak Akses
3. [Panduan Berdasarkan User (Step-by-Step Workflow)](#3-panduan-berdasarkan-user-step-by-step-workflow)
   - 3.1 Modul Pengunjung / Donatur (Warga & Publik)
   - 3.2 Modul Petugas Lapangan Koin (PLK) / Tingkat Ranting
   - 3.3 Modul Pengurus MWC / Koordinator Wilayah (Kecamatan)
   - 3.4 Modul Administrator Cabang (PC LAZISNU Lamongan)
   - 3.5 Modul Pimpinan & Verifikator Laporan Keuangan
4. [Troubleshooting & FAQ (Tanya Jawab Kendala Teknis)](#4-troubleshooting--faq)
5. [Kontak Bantuan & Sekretariat Resmi](#5-kontak-bantuan--sekretariat-resmi)

---

# 1. INFORMASI DOKUMEN & PENDAHULUAN

### 1.1 Identitas Dokumen & Hak Cipta
* **Nama Sistem:** Sistem Informasi Manajemen KOIN NU & Portal Publik NU Care - LAZISNU Lamongan (KOIN NU v2.0)
* **Penerbit:** Lembaga Amil Zakat, Infaq, dan Shadaqah Nahdlatul Ulama (NU Care - LAZISNU) Cabang Lamongan
* **Alamat Kantor:** Gedung PCNU Lamongan Lt. 1, Jl. Kyai Amin No. 9, Lamongan, Jawa Timur 62213
* **Hotline Layanan:** 0851 8899 1926 | **Email:** lazisnulamongan@gmail.com
* **Portal Resmi:** https://lazisnulamongan.or.id

> Hak cipta dilindungi undang-undang. Dilarang menggandakan, mendistribusikan, atau memodifikasi sebagian atau seluruh isi dokumen ini tanpa izin tertulis dari Pengurus Cabang NU Care - LAZISNU Lamongan.

### 1.2 Tujuan & Manfaat Buku Panduan
Buku Panduan Operasional ini disusun sebagai pedoman baku teknis (Standard Operating Procedure / SOP Digital) bagi seluruh tingkatan pengguna, mulai dari Warga Nahdliyin / Donatur Umum, Petugas Lapangan Koin (PLK) di tingkat RT/RW/Ranting (Desa), Pengurus Majelis Wakil Cabang (MWC di tingkat Kecamatan), Pengurus Cabang (PC di tingkat Kabupaten), hingga Dewan Pengawas Syariah dan Pengawas Keuangan.

**Manfaat Utama:**
1. **Transparansi & Akuntabilitas (MANTAP):** Mewujudkan tata kelola Modern, Akuntabel, Transparan, Amanah, dan Profesional.
2. **Keseragaman Operasional:** Menstandarkan prosedur pencatatan kaleng koin, validasi perolehan dana, dan mekanisme bagi hasil penthong.
3. **Kemudahan Pengoperasian:** Memberikan instruksi visual yang mudah dipelajari oleh petugas di tingkat desa/ranting tanpa latar belakang teknis khusus.
4. **Keamanan Data Finansial:** Memastikan setiap rupiah donasi tercatat rapi, terverifikasi berjenjang, dan bebas dari selisih pembulatan.

### 1.3 Ketentuan Hak Akses & Keamanan Akun
1. **Kerahasiaan Kredensial:** Setiap pengguna wajib menjaga kerahasiaan *username* dan *password*. Dilarang meminjamkan akun login kepada pihak lain.
2. **Ganti Kata Sandi Berkala:** Pengguna disarankan mengganti kata sandi bawaan pertama kali login dan melakukan pembaruan secara berkala (minimal 3 bulan sekali).
3. **Penyimpanan Bukti Fisik:** Petugas lapangan tetap wajib menyimpan bukti fisik berupa tanda terima atau foto saat pembukaan kaleng koin sebagai arsip silang (*cross-check*).
4. **Log Aktivitas:** Sistem mencatat setiap aktivitas penambahan data, perubahan status ranting, dan penutupan periode penthong secara otomatis demi jejak audit (*audit trail*).

### 1.4 Panduan Membaca Manual (Arti Simbol / Ikon)
Dalam buku panduan ini digunakan penanda visual untuk mempermudah pembacaan:

* 💡 **[TIPS / REKOMENDASI]:** Petunjuk praktis untuk mempercepat pekerjaan dan tips efisiensi.
* 📌 **[CATATAN PENTING]:** Ketentuan administratif atau aturan regulasi yang wajib dipatuhi.
* ⚠️ **[PERHATIAN / PERINGATAN]:** Tindakan krusial yang berdampak pada perubahan data permanen, penguncian saldo, atau suspensi akun.
* 📷 **[GAMBAR]:** Penanda titik tangkapan layar (*screenshot*) yang perlu disisipkan pada dokumen cetak.

---

# 2. IDENTIFIKASI PERAN PENGGUNA (ROLES)

Sistem dirancang dengan arsitektur **Role-Based Access Control (RBAC)** berjenjang sesuai hierarki organisasi Nahdlatul Ulama:

```
[ Publik / Donatur / Munfiq ] ──> Mengakses Portal Publik, Berita, Kalkulator Zakat, & Donasi Online
             │
[ Petugas Lapangan Koin (PLK) / Admin Ranting ] ──> Mengelola Kaleng Koin, Input Setoran Desa/RT
             │
[ Pengurus MWC (Kecamatan) ] ──> Monitoring Capaian Ranting se-Kecamatan, Validasi Wilayah
             │
[ Administrator PC LAZISNU Cabang ] ──> Super Admin, Kontrol Status Ranting, Penthong & Bagi Hasil
             │
[ Pimpinan / Verifikator Keuangan ] ──> Rekapitulasi Audit WTP, Monitoring Arus Kas Masuk & Keluar
```

### 2.1 Matriks Wewenang & Hak Akses

| Fitur / Modul | Publik / Donatur | PLK / Ranting | Pengurus MWC | Admin PC Cabang | Pimpinan / Verifikator |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Akses Portal Informasi & Berita | ✅ Ya | ✅ Ya | ✅ Ya | ✅ Ya | ✅ Ya |
| Donasi Online (BSI / BRI / QRIS) | ✅ Ya | ✅ Ya | ✅ Ya | ✅ Ya | ✅ Ya |
| Registrasi Donatur & Kaleng Baru | ❌ Tidak | ✅ Wilayah Sendiri | 👁️ Lihat Data | ✅ Semua Data | 👁️ Lihat Data |
| Input Setoran Koin Bulanan | ❌ Tidak | ✅ Wilayah Sendiri | ❌ Tidak | ✅ Hak Penuh | ❌ Tidak |
| Cetak Tanda Terima / Bukti Setor | ❌ Tidak | ✅ Wilayah Sendiri | 👁️ Unduh | ✅ Hak Penuh | 👁️ Unduh |
| Kontrol Status Aktif / Suspend Ranting | ❌ Tidak | ❌ Tidak | 👁️ Lihat Status | ✅ Hak Penuh | 👁️ Lihat Status |
| Buka & Kunci Periode Penthong | ❌ Tidak | ❌ Tidak | 👁️ Lihat Data | ✅ Hak Penuh | 👁️ Lihat Data |
| Hitung Otomatis Bagi Hasil Tasharruf | ❌ Tidak | 👁️ Hak Ranting | 👁️ Hak MWC | ✅ Hak Penuh | ✅ Audit Penuh |
| Verifikasi Mutasi Donasi Masuk | ❌ Tidak | ❌ Tidak | ❌ Tidak | ✅ Hak Penuh | ✅ Hak Penuh |
| Pengaturan Konten & Banner Portal | ❌ Tidak | ❌ Tidak | ❌ Tidak | ✅ Hak Penuh | ❌ Tidak |
| Unduh Laporan Keuangan (Audit WTP) | 👁️ Publik Ringkas | 👁️ Wilayah | 👁️ Kecamatan | ✅ Lengkap (Excel/PDF) | ✅ Lengkap (Excel/PDF) |

---

# 3. PANDUAN BERDASARKAN USER (STEP-BY-STEP WORKFLOW)

## 3.1 MODUL PENGUNJUNG / DONATUR (WARGA & UMUM)

Modul ini ditujukan bagi warga Nahdliyin, munfiq, aghniya, dan masyarakat umum yang ingin menyalurkan zakat, infaq, shadaqah, maupun wakaf melalui NU Care - LAZISNU Lamongan secara transparan dan mudah.

### Workflow Donatur:
```
Buka Web ──> Pilih Program / Rekening ──> Scan QRIS / Transfer Bank ──> Konfirmasi WA ──> Terima Bukti Donasi
```

### A. Cara Mengakses Portal Website Resmi
1. Buka aplikasi peramban (*web browser*) di komputer atau ponsel pintar (seperti Google Chrome, Safari, atau Mozilla Firefox).
2. Ketikkan alamat: **https://lazisnulamongan.or.id** pada kolom URL lalu tekan **Enter**.
3. Halaman beranda interaktif NU Care - LAZISNU Lamongan akan tampil dengan nuansa warna hijau khas Nahdlatul Ulama.
4. Pada perangkat ponsel pintar (*smartphone*), tampilan website otomatis menyesuaikan layar dengan navigasi menu praktis di pojok kanan atas.

[GAMBAR: Tampilan Halaman Depan Portal Beranda NU Care LAZISNU Lamongan pada desktop dan layar mobile smartphone, Kotak pencarian program, logo instansi di navigasi atas, dan nomor hotline WhatsApp 0851 8899 1926 ditandai lingkaran merah]

---

### B. Cara Memilih Rekening dan Program Donasi
LAZISNU Lamongan menerapkan pemisahan rekening bank penerimaan secara ketat sesuai kaidah syariah:

1. **Khusus Zakat (Fitrah, Mal, Profesi, Pertanian):**
   * **Bank:** Bank Syariah Indonesia (BSI)
   * **Nomor Rekening:** `2026070708`
   * **Atas Nama:** Lazisnu Lamongan
2. **Selain Zakat (Infaq, Shadaqah, Kemanusiaan, Koin Peduli):**
   * **Bank:** Bank Rakyat Indonesia (BRI)
   * **Nomor Rekening:** `004101001448562`
   * **Atas Nama:** LAZISNU LAMONGAN
3. **Wakaf Produktif (Pembangunan Gedung & Masjid):**
   * Program Unggulan: *Wakaf Pembangunan Masjid Gus Dur*, Pondok Pesantren, & Sarana Umat.
4. **Donasi Tanggap Darurat & Bencana:**
   * Program Bantuan Kemanusiaan, Siaga Banjir Bengawan Solo, dan Santunan Yatim Dhuafa.

**Langkah Memilih Program:**
* **Langkah 1:** Pada menu navigasi utama, klik menu **Program**.
* **Langkah 2:** Pilih kategori program yang diminati (contoh: *Wakaf Masjid Gus Dur* atau *Peduli Umat*).
* **Langkah 3:** Klik tombol **Donasi Sekarang** pada kartu program yang dipilih.

[GAMBAR: Kartu Program Donasi dengan daftar nomor rekening resmi BSI (Zakat) dan BRI (Non-Zakat), Tombol 'Salin Nomor Rekening' dan tombol 'Donasi Sekarang' dilingkari merah]

---

### C. Cara Donasi Cepat Menggunakan QRIS & Konfirmasi Transfer
1. **Langkah 1:** Pada halaman program atau di area *Metode Pembayaran*, pilih opsi **QRIS Nasional LAZISNU**.
2. **Langkah 2:** Buka aplikasi mobile banking (BSI Mobile, BRImo, Livin', BCA Mobile) atau e-wallet (GoPay, OVO, Dana, LinkAja, ShopeePay) di ponsel Anda.
3. **Langkah 3:** Pilih menu **Scan QR**, lalu arahkan kamera ke barcode QRIS yang terpampang di layar komputer/brosur.
4. **Langkah 4:** Masukkan nominal donasi yang ingin disalurkan (tanpa biaya admin tambahan).
5. **Langkah 5:** Periksa nama *merchant*: **LAZISNU LAMONGAN**, lalu konfirmasi PIN transaksi Anda.
6. **Langkah 6:** Simpan bukti tangkapan layar pembayaran sukses di ponsel Anda.
7. **Langkah 7 (Konfirmasi Otomatis):** Klik tombol **Konfirmasi Donasi via WhatsApp** yang tersedia di halaman web, atau kirimkan struk ke nomor **0851 8899 1926**. Petugas akan memverifikasi dan menerbitkan Bukti Setor Zakat/Infaq Digital (BSZ).

[GAMBAR: Tampilan pop-up QRIS Standar Pembayaran Nasional LAZISNU Lamongan lengkap dengan instruksi scan, Kode barcode QRIS dan tombol 'Konfirmasi via WhatsApp' dilingkari merah]

---

### D. Cara Mengakses Menu "Konsultasi & Layanan" via WhatsApp
1. Di bagian atas header website atau pada menu navigasi, temukan tombol **Konsultasi & Layanan**.
2. Klik tombol tersebut, maka Anda akan otomatis diarahkan ke chat WhatsApp resmi PC LAZISNU Lamongan di nomor **0851 8899 1926**.
3. Layanan ini mencakup:
   * Konsultasi perhitungan hisab Zakat Mal dan Zakat Pertanian/Tambak.
   * Permohonan mobil ambulans gratis LAZISNU untuk warga yang sakit atau jenazah.
   * Jemput donasi/kaleng koin ke rumah bagi warga yang berhalangan hadir.

[GAMBAR: Header Navigasi Website dengan tombol hijau berikon ambulans dan headset bertuliskan 'Konsultasi & Layanan', Tombol 'Konsultasi & Layanan' dan nomor telepon 0851 8899 1926 dilingkari merah]

---

### E. Melihat Transparansi Capaian Program & Kabar Pentasyarufan
1. Klik menu **Kabar** untuk membaca artikel dokumentasi penyaluran bantuan, pembagian beasiswa santri, santunan sembako, dan bedah rumah dhuafa.
2. Klik menu **Laporan** pada portal untuk melihat widget grafik real-time capaian pengumpulan dana KOIN NU di 15 MWC se-Kabupaten Lamongan.

---

## 3.2 MODUL PETUGAS LAPANGAN KOIN (PLK) / TINGKAT RANTING

Petugas Lapangan Koin (PLK) dan Admin Ranting adalah ujung tombak gerakan KOIN NU di tingkat desa/kelurahan dan RT/RW.

### Workflow Petugas Lapangan:
```
Login Akun ──> Registrasi Kaleng Donatur ──> Penarikan Bulanan (Penthong) ──> Input Setoran ──> Cetak Bukti Struk ──> Setor ke MWC
```

### A. Login Petugas Lapangan
1. Buka peramban di ponsel/laptop, akses: **https://lazisnulamongan.or.id/login**.
2. Masukkan **Alamat Email / Username** akun Ranting Anda (contoh: `ranting.karenggeneng@koinnu.id`).
3. Masukkan **Kata Sandi (Password)** Anda.
4. Klik tombol hijau **Masuk ke Sistem**.
5. Jika berhasil, Anda akan masuk ke **Dashboard Admin Ranting**.

💡 *Tips:* Centang kotak **Ingat Saya** pada peramban agar Anda tidak perlu mengetikkan kata sandi setiap kali membuka aplikasi di ponsel lapangan.

[GAMBAR: Formulir Login AdminLTE KOIN NU dengan input email, password, dan logo LAZISNU Lamongan, Tombol hijau 'Masuk ke Sistem' dan opsi 'Ingat Saya' dilingkari merah]

---

### B. Input Registrasi Kaleng Donatur Baru (By-Name By-Address)
Setiap kaleng yang ditaruh di rumah warga Nahdliyin wajib tercatat secara resmi agar tidak terjadi kaleng tak bertuan.

1. **Langkah 1:** Pada menu navigasi sebelah kiri, klik dropdown **Master Data** lalu pilih sub-menu **Donatur**.
2. **Langkah 2:** Klik tombol biru **+ Tambah Donatur Baru** di pojok kanan atas tabel.
3. **Langkah 3:** Lengkapi kolom formulir data warga munfiq:
   * **Nama Lengkap Donatur:** (Contoh: *H. Achmad Fauzi*)
   * **NIK (Opsional):** 16 digit nomor induk kependudukan warga.
   * **Nomor HP / WhatsApp:** Untuk pengiriman struk digital (Contoh: `081234567890`).
   * **Wilayah MWC & Ranting:** Terisi otomatis sesuai wilayah akun Ranting Anda.
   * **Dusun / RT / RW / Alamat Lengkap:** (Contoh: *Dusun Sukomulyo RT 02 RW 01*).
   * **Petugas PLK Pembina:** Pilih nama relawan yang bertanggung jawab mengambil kaleng ini setiap bulan.
4. **Langkah 4 (Nomor Kaleng Otomatis):** Sistem secara cerdas akan membuatkan kode kaleng unik berformat:  
   `KLG-[KODE_MWC]-[KODE_RANTING]-[NOMOR_URUT]` (Contoh: `KLG-M09-R03-0042`).
5. **Langkah 5:** Tempelkan stiker nomor kaleng tersebut pada badan kaleng fisik warga.
6. **Langkah 6:** Klik tombol **Simpan Data Donatur**.

[GAMBAR: Halaman Formulir Pendaftaran Donatur Baru pada AdminLTE dengan input nama, alamat, nomor kaleng terisi otomatis, Input 'Nomor Kaleng Otomatis' dan tombol hijau 'Simpan Donatur' dilingkari merah]

---

### C. Pencatatan Penarikan Infak Kaleng Bulanan (Penthong)
Pengambilan kaleng dilakukan sebulan sekali sesuai jadwal **Penthong** (Buka Kotak) di Ranting.

1. **Langkah 1:** Pada menu kiri, klik menu **Transaksi** $\rightarrow$ pilih **Input Setoran**.
2. **Langkah 2:** Pilih **Nama Petugas Relawan (PLK)** yang melakukan penarikan kotak.
3. **Langkah 3:** Pilih **Periode Penthong Aktif** (Contoh: *Penthong 10 - Oktober 2026*).  
   📌 *Catatan:* Sistem akan secara otomatis mengikat transaksi ke periode penthong yang sedang aktif.
4. **Langkah 4:** Masukkan **Nominal Perolehan (Rp)**.  
   💡 *Fitur Format Ribuan:* Ketik angka nominal murni (misal ketik `1250000`), sistem akan otomatis memformatnya menjadi tampilan rapi berpemisah titik: `Rp 1.250.000` untuk menghindari kekeliruan kelebihan atau kekurangan angka nol (0).
5. **Langkah 5:** Pilih **Tanggal Pengambilan** (otomatis default tanggal hari ini).
6. **Langkah 6 (Upload Foto):** Ambil foto saat pembukaan kaleng atau foto nota fisik melalui kamera ponsel (format JPG/PNG maks 2MB).
7. **Langkah 7:** Klik tombol hijau **Simpan & Konfirmasi Setoran**.

⚠️ **Perhatian Khusus (Status Ranting Non-Aktif):**  
Jika Pengurus Cabang (PC) menonaktifkan status Ranting Anda karena keterlambatan administrasi, tombol *Input Setoran* akan disembunyikan dan muncul pesan berwarna merah:  
*"Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda."*  
Segera hubungi pengurus MWC/Cabang untuk melakukan sinkronisasi administrasi agar akses dibuka kembali.

[GAMBAR: Formulir Input Setoran Transaksi Koin dengan input nominal berformat titik ribuan dan upload bukti, Kotak input nominal 'Rp 1.250.000' dan tombol 'Simpan & Konfirmasi' dilingkari merah]

---

### D. Cetak Bukti Tanda Terima Digital untuk Warga
1. Buka menu **Transaksi** $\rightarrow$ **Riwayat Setoran**.
2. Cari transaksi yang baru dimasukkan, lalu klik tombol biru **Lihat / Cetak Struk** (ikon printer).
3. Halaman Struk Digital Tanda Terima Zakat/Infaq akan muncul berisikan:
   * Nomor Invoice Unik (Contoh: `TRX-20261004-3A8MES43`).
   * Nama Donatur / Ranting, Tanggal, dan Nominal Pasti.
   * Barcode Validasi Keabsahan LAZISNU.
4. Klik tombol **Cetak Struk (PDF)** untuk mencetak melalui printer thermal Bluetooth/kertas, atau klik **Kirim Struk WhatsApp** untuk mengirimkan tanda terima langsung ke nomor WA warga.

[GAMBAR: Tampilan Pop-up Struk Digital Bukti Tanda Terima KOIN NU ber-QR Code dengan stempel resmi LAZISNU, Tombol 'Cetak PDF' dan 'Kirim via WhatsApp' dilingkari merah]

---

### E. Rekap Setoran Koin ke Tingkat MWC / Cabang
1. Buka menu **Laporan** $\rightarrow$ **Rekap Perolehan**.
2. Pilih filter bulan dan tahun pembukuan.
3. Anda dapat melihat total bruto hasil koin ranting Anda dan membandingkan capaian antar RT/RW.
4. Klik tombol hijau **Ekspor ke Excel** untuk mengunduh rekapitulasi data guna dilaporkan pada rapat pleno bulanan pengurus Ranting NU.

---

## 3.3 MODUL PENGURUS MWC / KOORDINATOR WILAYAH (KECAMATAN)

Pengurus MWC mengoordinasikan seluruh Ranting di wilayah kecamatannya (15 MWC di Kabupaten Lamongan).

### A. Memantau Capaian dan Kinerja Ranting
1. Login dengan akun MWC Anda.
2. Pada **Dashboard MWC**, amati ringkasan statistik:
   * Total Donasi Terkumpul di Kecamatan.
   * Jumlah Ranting Aktif vs Ranting Menunggak.
   * Grafik Perolehan Koin per Ranting bulan berjalan.
3. Klik menu **Master Data** $\rightarrow$ **Ranting** untuk melihat daftar status masing-masing Ranting di wilayah Anda. Badge hijau menandakan Ranting **Aktif**, sedangkan badge merah menandakan Ranting **Non-Aktif (Suspended)**.

[GAMBAR: Tampilan Dashboard MWC NU yang memperlihatkan grafik batang perolehan masing-masing Ranting di kecamatannya, Tabel daftar status Ranting dan indikator perolehan dilingkari merah]

---

### B. Memeriksa Laporan Bagi Hasil Tasharruf Penthong Tingkat MWC
1. Buka menu **Laporan** $\rightarrow$ **Bagi Hasil Penthong**.
2. Pilih periode Penthong yang sedang dievaluasi.
3. Anda akan melihat tabel pembagian hak resmi:
   * **Total Pendapatan Kotor Ranting**
   * **Potongan Operasional (20%)**
   * **Hak Ranting (80% dari dana bersih)**
   * **Hak MWC (12% dari dana bersih)** $\rightarrow$ Merupakan dana kas yang berhak dikelola MWC untuk program kemaslahatan di tingkat kecamatan.
4. Cetak laporan ini sebagai dasar serah terima dana bagi hasil dari Pengurus Cabang.

---

## 3.4 MODUL ADMINISTRATOR CABANG (PC LAZISNU LAMONGAN)

Administrator Cabang memiliki hak akses tertinggi (*Super Admin*) untuk mengontrol tata kelola aplikasi secara terpusat.

### A. Manajemen Pengguna & Hak Akses (RBAC)
1. Buka menu **Pengaturan & Akun** $\rightarrow$ pilih **Manajemen Akun Pengguna**.
2. Anda dapat melihat seluruh akun terdaftar (PC, 15 MWC, dan Ranting se-Lamongan).
3. Untuk mengubah sandi petugas yang lupa kata sandi: Klik tombol kuning **Edit** pada baris pengguna bersangkutan, masukkan kata sandi baru, lalu klik **Perbarui Akun**.
4. Anda dapat menonaktifkan akun yang sudah tidak bertugas dengan mengubah status akun menjadi **Non-Aktif**.

[GAMBAR: Halaman Tabel Manajemen Pengguna AdminLTE dengan filter Role PC, MWC, Ranting, Tombol edit akun dan pengaturan hak akses dilingkari merah]

---

### B. Kontrol Status Aktif / Suspend Operasional Ranting
Fitur ini digunakan oleh PC LAZISNU untuk menegakkan kedisiplinan pelaporan dana koin ranting.

1. Buka menu **Master Data** $\rightarrow$ pilih **Ranting**.
2. Cari nama Ranting yang akan diatur.
3. Pada kolom *Status Program*, perhatikan tombol saklar toggle:
   * **Aktifkan:** Klik tombol hijau jika urusan administrasi ranting telah tuntas.
   * **Suspend (Non-Aktifkan):** Klik tombol merah bertuliskan **Nonaktifkan**.
4. Konfirmasikan tindakan pada jendela dialog pop-up.
5. Ranting yang dinonaktifkan seketika terkunci dari input setoran baru, namun saldo historisnya tetap masuk dalam total perolehan cabang.

[GAMBAR: Tabel Master Data Ranting pada akun PC dengan saklar toggle status 'Aktif' dan 'Nonaktif', Tombol aksi ubah status ranting dilingkari merah]

---

### C. Manajemen Periodisasi Penthong & Kunci Bagi Hasil (Tasharruf)
Pembukaan kaleng koin dilakukan secara terkoordinasi sebulan sekali.

1. **Membuka Periode Penthong Baru:**
   * Buka menu **Transaksi** $\rightarrow$ **Periode Penthong**.
   * Klik tombol hijau **+ Buka Penthong Baru**.
   * Isi Nama Periode (misal: *Penthong 11 - Nopember 2026*), Bulan, dan Tahun.
   * Klik **Buka Periode**. Sistem akan otomatis mengaktifkan periode ini dan menutup periode lama.
2. **Menutup & Mengunci Hasil Penthong (Finalisasi Bagi Hasil):**
   * Saat seluruh setoran ranting pada bulan tersebut telah masuk, buka detail Penthong bersangkutan.
   * Periksa rekapitulasi data total bruto yang terkumpul.
   * Klik tombol merah berikon gembok: **Tutup & Kunci Penthong**.
   * Sistem akan menjalankan `DistributionService` untuk menghitung dan membekukan nominal pembagian:
     * Operasional (20%)
     * Dana Bersih (80%):
       * Hak Ranting: 80%
       * Hak MWC: 12%
       * Hak Cabang (PC): 6%
       * Hak Wilayah (PW): 1.5%
       * Hak Pusat (PB): 0.5%
   * Setelah dikunci, data bersifat permanen (*immutable*) dan tidak dapat diubah lagi demi akuntabilitas audit.

[GAMBAR: Halaman Rincian Penthong dengan kartu agregat nominal dan tombol kunci, Tombol merah 'Tutup & Kunci Penthong' dilingkari merah]

---

### D. Manajemen Konten Portal Publik (CMS)
Administrator PC berwenang mengelola informasi di halaman depan website:

1. **Mengubah Banner Slider Utama:** Buka menu **Portal Publik** $\rightarrow$ **Hero Slider**. Anda dapat menambah banner kegiatan, mengubah judul promosi donasi, atau menonaktifkan slide.
2. **Publikasi Berita & Dokumentasi:** Buka **Portal Publik** $\rightarrow$ **Artikel & Kabar**. Masukkan judul berita, kategori pilar, isi artikel lengkap, dan unggah foto dokumentasi penyaluran.
3. **Verifikasi Donasi Online:** Buka **Portal Publik** $\rightarrow$ **Donasi Online**. Cek mutasi transfer dari donatur publik, periksa bukti transfer, lalu klik tombol hijau **Verifikasi** untuk menerbitkan kwitansi resmi.

---

## 3.5 MODUL PIMPINAN & VERIFIKATOR LAPORAN KEUANGAN

Modul ini dirancang khusus untuk Ketua PC LAZISNU, Dewan Pembina, dan Auditor Keuangan Independen.

### A. Melihat Laporan Audit Keuangan (WTP)
1. Buka menu **Laporan** $\rightarrow$ pilih **Laporan Audit WTP**.
2. Sistem menyajikan ikhtisar keuangan berbasis standar akuntansi zakat (PSAK 109).
3. Anda dapat mengunduh dokumen laporan tahunan berformat PDF resmi yang telah diaudit dengan predikat Wajar Tanpa Pengecualian (WTP).

### B. Cetak Rekapitulasi Resmi Berjenjang
1. Buka menu **Laporan** $\rightarrow$ pilih **Cetak Rekap Resmi**.
2. Pilih parameter filter: Periode Penthong, Tingkat MWC, atau Keseluruhan Cabang.
3. Klik tombol biru **Pratinjau Cetak / PDF**.
4. Dokumen akan terbuka dalam format siap cetak ukuran kertas F4/A4 lengkap dengan kop surat resmi NU Care - LAZISNU Lamongan, kolom tanda tangan Pengurus Harian, dan stempel digital.

---

# 4. TROUBLESHOOTING & FAQ (TANYA JAWAB KENDALA TEKNIS)

Berikut adalah daftar solusi atas permasalahan yang paling sering dihadapi petugas di lapangan:

### 1. Kendala Login / Lupa Kata Sandi
* **Gejala:** Muncul notifikasi *"Kredensial yang diberikan tidak cocok dengan data kami"* saat login.
* **Solusi:**
  1. Pastikan tombol Caps Lock di keyboard tidak menyala.
  2. Periksa apakah ada spasi kosong di awal atau akhir pengetikan email/username.
  3. Jika tetap gagal, hubungi Administrator PC LAZISNU melalui WhatsApp di nomor **0851 8899 1926** dengan menyebutkan nama akun dan Ranting/MWC Anda untuk di-*reset* kata sandinya.

---

### 2. Gagal Upload Foto Bukti Setoran / Bukti Transfer
* **Gejala:** Muncul peringatan *"File gambar terlalu besar"* atau upload berputar terus tanpa henti.
* **Solusi:**
  1. Ukuran maksimal foto yang diizinkan sistem adalah **2 Megabyte (MB)** dengan format JPG, JPEG, atau PNG.
  2. Jika memotret langsung dari kamera ponsel beresolusi tinggi, perkecil resolusi foto terlebih dahulu melalui fitur kompresi foto atau kirimkan foto tersebut ke chat WhatsApp lalu unduh kembali sebelum diunggah ke aplikasi.

---

### 3. Salah Input Nominal Setoran atau Salah Pilih Relawan
* **Gejala:** Nominal yang diketik keliru (misal kelebihan angka nol) dan transaksi terlanjur disimpan.
* **Solusi:**
  * **Jika Penthong Masih Berstatus 'Aktif':** Admin PC LAZISNU atau petugas berwenang dapat membuka menu *Riwayat Transaksi*, klik tombol merah *Hapus* pada transaksi yang salah, kemudian lakukan penginputan ulang dengan data yang benar.
  * **Jika Penthong Sudah Berstatus 'Tutup/Dikunci':** Data transaksi yang sudah dikunci tidak dapat dihapus demi keamanan pembukuan. Buatlah surat berita acara penyesuaian (*adjustment*) ke bagian Keuangan PC LAZISNU untuk disesuaikan pada pembukuan Penthong berikutnya.

---

### 4. Muncul Notifikasi "Akses Input Dinonaktifkan oleh PC LAZISNU"
* **Penyebab:** Ranting Anda sedang dalam masa *suspend* (penonaktifan sementara) oleh Cabang.
* **Solusi:**  
  Akun Anda tetap dapat melihat seluruh riwayat data lama (read-only). Segera hubungi pengurus MWC Anda untuk menyelesaikan kewajiban administrasi atau menyetorkan dana fisik penthong bulan sebelumnya. Setelah disetujui PC, status ranting akan diaktifkan kembali dan tombol input akan langsung muncul.

---

### 5. Format Pengaduan Bug / Gangguan Sistem
Jika menemukan halaman error (*Error 500* atau tampilan tidak merespons), kirimkan laporan via WhatsApp ke **0851 8899 1926** dengan format:
```
[LAPORAN GANGGUAN KOIN NU]
Nama Petugas : 
Akun / Ranting : 
Perangkat : (Contoh: HP Android Samsung A12 / Laptop Windows)
Waktu Kejadian : 
Halaman / Menu : 
Deskripsi Kendala : 
(Sertakan foto tangkapan layar / screenshot layar error)
```

---

# 5. KONTAK BANTUAN & SEKRETARIAT RESMI

Untuk konsultasi syariah, permohonan layanan ambulans, asistensi teknis aplikasi, atau konfirmasi donasi resmi, silakan hubungi kontak berikut:

* **Lembaga:** Pengurus Cabang NU Care - LAZISNU Kabupaten Lamongan
* **Alamat Kantor:** Gedung PCNU Lamongan Lt. 1, Jl. Kyai Amin No. 9, Sidokumpul, Kec. Lamongan, Kabupaten Lamongan, Jawa Timur 62213
* **Hotline WhatsApp Resmi (24 Jam Respon Cepat):** **0851 8899 1926**
* **Surat Elektronik (Email):** lazisnulamongan@gmail.com
* **Situs Web Resmi:** https://lazisnulamongan.or.id
* **Media Sosial:** Facebook & Instagram: `@lazisnulamongan`

---
*Buku panduan ini dicetak dan diterbitkan secara resmi oleh PC NU Care - LAZISNU Lamongan sebagai pedoman operasional baku digital.*
