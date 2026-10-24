# LAZISNU Application

Sistem Informasi Manajemen LAZISNU berbasis Laravel 12.

Project ini telah dikonfigurasi menggunakan **Docker Compose** dengan arsitektur **Bind Mount Volume** (`.:/var/www/html`). Seluruh file project tetap berada di luar container (pada komputer/server host), sehingga saat update kode cukup melakukan `git pull` tanpa perlu build ulang container.

---

## 🚀 Menjalankan dengan Docker

### 1. Prasyarat
- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/) sudah terinstall.
- Port yang digunakan (dapat disesuaikan di `.env`):
  - **8080** : Laravel development server
  - **8081** : phpMyAdmin (GUI Database)
  - **3306** : MySQL lokal di host (misalnya Laragon)

---

### 2. Langkah Setup Pertama Kali

#### Langkah A: Siapkan Environment (`.env`)
Salin file konfigurasi environment Docker jika belum memiliki `.env`:
```bash
cp .env.docker.example .env
```
> **Catatan:** File `.env` sudah dikonfigurasi untuk memakai MySQL yang berjalan di host (`DB_HOST=host.docker.internal`). Sesuaikan nama database dan kredensial dengan konfigurasi lokal Anda.

#### Langkah B: Jalankan Container Docker
```bash
docker compose up -d --build
```

#### Langkah C: Setup Dependensi & Database Laravel
Jalankan perintah berikut melalui container `app`:
```bash
# 1. Install dependensi Composer
docker compose exec app composer install

# 2. Generate Application Key Laravel
docker compose exec app php artisan key:generate

# 3. Jalankan migrasi database MySQL
docker compose exec app php artisan migrate

# 4. Buat symlink storage publik
docker compose exec app php artisan storage:link

# 5. (Khusus Linux / VPS) Atur izin akses folder storage & cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache
```

---

### 3. Akses Aplikasi

| Layanan | URL | Keterangan / Kredensial |
| :--- | :--- | :--- |
| **Web App** | [http://localhost:8080](http://localhost:8080) | Laravel development server (tanpa Nginx) |
| **phpMyAdmin** | [http://localhost:8081](http://localhost:8081) | Server: `host.docker.internal`<br>Kredensial mengikuti `.env` |
| **MySQL lokal** | `localhost:3306` | MySQL host (misalnya Laragon)<br>DB: sesuai `DB_DATABASE` di `.env` |

---

## 🔄 Alur Pembaruan Kode (`git pull`)

Karena seluruh kode di-mount langsung dari host, pembaruan aplikasi sangat cepat dan mudah:

```bash
# 1. Tarik pembaruan terbaru dari repository Git
git pull origin master

# 2. Update dependensi PHP jika ada perubahan di composer.json
docker compose exec app composer install

# 3. Jalankan migrasi jika ada migrasi database baru
docker compose exec app php artisan migrate --force

# 4. Bersihkan cache aplikasi Laravel
docker compose exec app php artisan optimize:clear
```

---

## 🛠️ Perintah Berguna (Cheat Sheet)

- **Melihat status container:**
  ```bash
  docker compose ps
  ```

- **Melihat log container secara realtime:**
  ```bash
  docker compose logs -f app
  ```

- **Masuk ke terminal container (Bash):**
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
  *(Database tetap dikelola MySQL lokal di host.)*

---

## 📄 Lisensi
Project ini berada di bawah lisensi [MIT License](LICENSE).
