# Sistem Helpdesk Tiket - Praktikum Pemrograman Web

Aplikasi Helpdesk Tiket berbasis **Laravel 13** dan **Vue / Blade**, mendemonstrasikan implementasi relasi Eloquent, Database Seeding deterministik, dan optimasi performa query melalui Eager Loading untuk mencegah problem N+1.

---

## 🛠️ Lingkungan & Teknologi

- **PHP**: 8.5.10 (CLI x64)
- **Framework**: Laravel 13.32.0
- **DBMS**: PostgreSQL (`pgsql`) / SQLite kompatibel
- **Frontend**: Vue / Vite / Blade
- **Testing**: Pest 5.2

---

## 🚀 Panduan Instalasi & Pengaturan Lingkungan (Clone Baru)

Ikuti langkah-langkah berikut untuk menjalankan proyek dari hasil clone repositori baru:

### 1. Clone Repositori & Masuk Direktori
```bash
git clone https://github.com/username/repo.git
cd repo
```

### 2. Instal Dependensi Composer & Node
```bash
composer install
npm install
```

### 3. Konfigurasi File Environment
Salin file template `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
> **Catatan Keamanan:** File `.env.example` sudah disediakan bebas dari kredensial atau rahasia sensitif. Pastikan file `.env` yang berisi kredensial asli tidak pernah di-commit ke Git.

### 4. Generate Application Key
```bash
php artisan key:generate
```

### 5. Konfigurasi Database Latihan
Pastikan database telah dibuat di PostgreSQL Anda, lalu sesuaikan nilai pada `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=belajarlaravel
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 6. Menjalankan Migration & Database Seeding

#### ⚠️ PERINGATAN PENTING MENGENAI `migrate:fresh`
> **Peringatan:** Menjalankan perintah `php artisan migrate:fresh` akan **MENGHAPUS SELURUH TABEL** yang ada di dalam database tanpa konfirmasi tambahan! Gunakan hanya di lingkungan pengembangan (*local/development*) dan jangan pernah dijalankan di lingkungan produksi (*production*).

Jalankan migrasi tabel:
```bash
php artisan migrate
```

Jalankan seeder untuk mengisi data awal:
```bash
php artisan db:seed
```
> **Catatan Seeding:** Perintah `php artisan db:seed` dirancang khusus untuk tabel target yang masih **kosong**. Jika ingin mereset dan mengisi ulang seluruh tabel latihan dari nol, gunakan:
> ```bash
> php artisan migrate:fresh --seed
> ```
Target data yang akan terisi secara otomatis:
- 10 Data Pengguna (*Users*)
- 3 Data Kategori (*Categories*: Akun, Jaringan, Aplikasi)
- 50 Data Tiket (*Tickets*)
- 100 Data Komentar (*Comments* — tepat 2 komentar per tiket)

### 7. Menjalankan Server Lokal & Frontend
Jalankan server aplikasi Laravel:
```bash
php artisan serve
```
Dan jalankan Vite dev server di terminal terpisah:
```bash
npm run dev
```

---

## 📌 Halaman & Akses Tiket

Setelah server berjalan, Anda dapat mengakses:
- **Daftar Tiket (Paginasi 10 per halaman)**: `http://localhost:8000/tickets`
- **Halaman 2 Tiket**: `http://localhost:8000/tickets?page=2`
- **Detail Tiket**: `http://localhost:8000/tickets/1`
- **API Endpoint JSON**: `http://localhost:8000/api/tickets/1`

---

## 📑 Dokumentasi Lengkap & Laporan Pengujian

Seluruh dokumentasi teknis, diagram, dan bukti pengujian lengkap tersimpan di folder [`docs/`](docs/):
- **[Laporan Pengujian & Analisis Lengkap](docs/laporan.md)**:
  1. Lingkungan & DBMS
  2. ERD & Kamus Data detail
  3. Bukti pengujian Migrate - Rollback - Migrate
  4. Hasil validasi jumlah data seed (10/3/50/100) pada rekonstruksi ke-1 dan ke-2
  5. Bukti pengujian relasi dua arah antar model
  6. Dokumentasi halaman pertama dan kedua
  7. Tabel komparasi query Lazy vs Eager Loading ($N=10$ dan $N=20$)
  8. Analisis kendala dan solusinya
