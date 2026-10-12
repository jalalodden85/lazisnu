# Panduan Menjalankan LAZISNU Menggunakan Docker Compose

Konfigurasi Docker ini dibuat dengan konsep **Bind Mount Volume (`.:/var/www/html`)**. Artinya seluruh kode dan file project tetap berada di komputer/server host (di luar container). 

### Keuntungan Arsitektur Ini:
1. **Mudah Diupdate:** Setiap kali ada pembaruan kode, Anda cukup menjalankan `git pull` di host. Perubahan kode langsung aktif tanpa perlu build ulang container (`docker compose build`).
2. **Memakai Database Lokal:** Container aplikasi terhubung ke MySQL yang sudah berjalan di host, misalnya MySQL Laragon.
3. **Lengkap dengan phpMyAdmin:** Disediakan GUI phpMyAdmin untuk melihat dan mengelola database lokal.

---

## 1. Struktur Service Docker

| Service | Container Name | Port Host | Fungsi |
| :--- | :--- | :--- | :--- |
| **`app`** | `lazisnu_app` | **8080** | Laravel development server di PHP 8.2 (tanpa Nginx) |
| **`phpmyadmin`** | `lazisnu_phpmyadmin` | **8081** | Web UI phpMyAdmin |

---

## 2. Cara Menjalankan Pertama Kali

### Langkah 1: Siapkan File `.env`
Pastikan file `.env` sudah ada. Jika belum ada, salin dari `.env.docker.example`:
```bash
cp .env.docker.example .env
```
Pengaturan database lokal bawaan:
- `DB_CONNECTION=mysql`
- `DB_HOST=host.docker.internal`
- `DB_PORT=3306`
- `DB_DATABASE=lazisnu`
- `DB_USERNAME=root`
- `DB_PASSWORD=` *(sesuaikan dengan kredensial MySQL lokal Anda)*

Pastikan MySQL host berjalan dan database yang diatur pada `DB_DATABASE` sudah tersedia. Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan akun MySQL lokal Anda. Jangan memetakan port MySQL di Compose karena database sudah berjalan di host.

*(Catatan: Jika port `8080` atau `8081` bentrok, Anda bisa mengubah `APP_PORT` atau `FORWARD_PMA_PORT` di dalam file `.env`). Aplikasi dijalankan dengan PHP development server; gunakan hanya untuk pengembangan lokal, bukan production.*

### Langkah 2: Jalankan Container
Jalankan perintah berikut di root folder project:
```bash
docker compose up -d --build
```

### Langkah 3: Install Dependensi & Setup Laravel
Jalankan perintah ini di terminal:
```bash
# 1. Install dependensi vendor Composer
docker compose exec app composer install

# 2. Generate Application Key Laravel
docker compose exec app php artisan key:generate

# 3. Jalankan migrasi database
docker compose exec app php artisan migrate

# 4. (Opsional) Buat symlink storage publik
docker compose exec app php artisan storage:link

# 5. (Khusus Linux / VPS) Berikan izin tulis folder storage & cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### Langkah 4: Buka Aplikasi di Browser
- **Web App:** [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)
  - Server: `host.docker.internal`
  - Username/password: sesuai konfigurasi MySQL lokal di `.env`

---

## 3. Alur Update Project (`git pull`)

Karena seluruh file di-mount langsung dari host, alur update sangat cepat:

```bash
# 1. Tarik pembaruan kode terbaru dari git
git pull origin main

# 2. Update dependensi jika ada perubahan composer.json
docker compose exec app composer install

# 3. Jalankan migrasi jika ada migrasi database baru
docker compose exec app php artisan migrate --force

# 4. Bersihkan cache Laravel
docker compose exec app php artisan optimize:clear
```

---

## 4. Perintah Tambahan yang Sering Digunakan

- **Melihat status container:**
  ```bash
  docker compose ps
  ```
- **Melihat log container (misal ada error):**
  ```bash
  docker compose logs -f app
  ```
- **Masuk ke dalam terminal container app (Bash):**
  ```bash
  docker compose exec app bash
  ```
- **Menjalankan perintah Artisan:**
  ```bash
  docker compose exec app php artisan tinker
  docker compose exec app php artisan route:list
  ```
- **Menghentikan container:**
  ```bash
  docker compose down
  ```
  *(Container Docker tidak menyimpan database; data tetap dikelola MySQL lokal di host.)*
