# Sistem Helpdesk Tiket - Praktikum Pemrograman Web

Aplikasi Helpdesk Tiket berbasis **Laravel 12 / 13** dan **Blade / Vue**, mendemonstrasikan implementasi CRUD terstruktur, Form Request, normalisasi input, Service Layer dengan transaksi multi-tabel atomik, pola Post-Redirect-Get (PRG), dan pengujian otomatis dengan Pest & PHPUnit.

---

## 🛠️ Lingkungan & Teknologi

- **PHP**: 8.5+ (CLI x64)
- **Framework**: Laravel 12.x / 13.x
- **DBMS**: PostgreSQL (`pgsql`) / SQLite kompatibel
- **Frontend**: Blade Plain (Accessible HTML5) / Vue Vite
- **Testing**: Pest / PHPUnit

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
Jalankan migrasi tabel:
```bash
php artisan migrate
```

Jalankan seeder untuk mengisi data awal:
```bash
php artisan db:seed
```
Target data yang akan terisi secara otomatis:
- 10 Data Pengguna (*Users*)
- 3 Data Kategori (*Categories*: Akun, Jaringan, Aplikasi)
- 50 Data Tiket (*Tickets*)
- 100 Data Komentar (*Comments* — tepat 2 komentar per tiket)

### 7. Menjalankan Server Lokal
Jalankan server aplikasi Laravel:
```bash
php artisan serve
```

---

## 📖 Modul Pertemuan 4: CRUD, Validasi, dan Lapisan Layanan

### 1. Aturan Bisnis Latihan
- **Create**:
  - Status awal selalu ditentukan otomatis oleh server menjadi `'open'`. Field `status` dari pengguna dilarang (`prohibited`).
  - Satu komentar awal (`note`) wajib tersimpan bersama tiket dalam satu transaksi.
  - Pada demo lokal, pemilik (`user_id`) dapat dipilih dari dropdown user seed.
- **Update**:
  - Pemilik tiket bersifat tetap. Field `user_id` dilarang dikirim (`prohibited`).
  - Seluruh field editable harus dikirim lengkap (pola PUT/PATCH).
  - Satu catatan perubahan (`note`) wajib tersimpan sebagai komentar baru bersama pembaruan tiket.
  - Pilihan status yang sah: `'open'`, `'pending'`, atau `'closed'`.
- **Delete**:
  - Tiket berstatus `'closed'` **tidak boleh dihapus** (ditolak oleh Service dengan `ValidationException`).
  - Tiket berstatus `'open'` atau `'pending'` boleh dihapus beserta seluruh riwayat komentarnya melalui FK `cascadeOnDelete()`.

### 2. Peta Tujuh Aksi Resource Route
Rute dikonfigurasi dengan pembatas parameter numerik `Route::pattern('ticket', '[0-9]+');` untuk mencegah query database yang salah tipe:

| HTTP Verb | Path / URI | Controller Action | Route Name | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/tickets` | `TicketController@index` | `tickets.index` | Daftar tiket (paginasi 10 per halaman) |
| `GET` | `/tickets/create` | `TicketController@create` | `tickets.create` | Form pembuatan tiket baru |
| `POST` | `/tickets` | `TicketController@store` | `tickets.store` | Simpan tiket baru & komentar awal |
| `GET` | `/tickets/{ticket}` | `TicketController@show` | `tickets.show` | Detail tiket dan daftar komentar |
| `GET` | `/tickets/{ticket}/edit` | `TicketController@edit` | `tickets.edit` | Form edit tiket |
| `PUT/PATCH` | `/tickets/{ticket}` | `TicketController@update` | `tickets.update` | Update tiket & simpan catatan perubahan |
| `DELETE` | `/tickets/{ticket}` | `TicketController@destroy` | `tickets.destroy` | Hapus tiket (jika bukan closed) |

### 3. Validasi & Normalisasi Field
Semua validasi dilakukan di lapisan server melalui Form Request (`TicketFormRequest`, `StoreTicketRequest`, `UpdateTicketRequest`):
- `prepareForValidation()`: Melakukan `trim()` hanya pada nilai string (`subject`, `description`, `note`). Tipe data non-string (seperti array) dibiarkan agar ditangkap oleh rule `string` dan tidak menimbulkan fatal error PHP.
- `subject`: `required`, `string`, `max:150`.
- `description`: `required`, `string`, `max:5000`.
- `category_id`: `required`, `integer`, `exists:categories,id`.
- `is_urgent`: `required`, `boolean` (checkbox menggunakan pasangan hidden `0` dan input `1`; nilai rusak seperti `'abc'` ditolak).
- `note`: `required`, `string`, `max:1000`.
- `user_id`: Wajib dan valid saat create; dilarang (`prohibited`) saat update.
- `status`: Dilarang (`prohibited`) saat create; wajib dan restricted ke `open,pending,closed` saat update.

### 4. Lapisan Layanan (Service Layer) & Transaksi
Logika manipulasi data tiket dan komentar dipusatkan di `App\Services\TicketService`:
- Menggunakan `DB::transaction()` untuk menjamin sifat ACID (atomik). Jika penyimpanan komentar gagal, pembuatan/perubahan tiket otomatis di-*rollback*.
- Menggunakan `Ticket::query()->lockForUpdate()->findOrFail($id)` pada update dan delete untuk mencegah *race condition*.
- Exception dibiarkan keluar tanpa blok `catch` yang menelan error, memastikan transaksi ter-rollback sebelum response error diteruskan.

### 5. Batasan Demo Identitas Pengguna
Pada lingkungan praktikum ini, modul autentikasi dan policy otorisasi belum diintegrasikan:
- Method `authorize()` pada Form Request mengembalikan `true`.
- Pemilik tiket dipilih secara manual dari dropdown user seed saat membuat tiket.
- Penulis komentar disamakan dengan pemilik tiket sebagai bentuk penyederhanaan studi kasus lokal.

---

## 🧪 Pengujian Otomatis (Testing)

### Konfigurasi Database Testing
Pengujian otomatis menggunakan database testing terisolasi yang dikonfigurasi melalui `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Menjalankan Test Transaksi Multi-Tabel
Untuk menjalankan verifikasi transaksi dan rollback atomik:
```bash
php artisan test --filter=TicketTransactionTest
```

### Menjalankan Seluruh Kasus Uji CRUD Tiket
```bash
php artisan test --filter=TicketCrudTest
```

### Menjalankan Seluruh Test Suite Proyek
```bash
php artisan test --compact
```

---

## 📑 Matriks 18 Kasus Uji & Dokumentasi

Dokumentasi detail pengujian 18 skenario pengujian sukses, gagal, batas karakter, manipulasi data, dan keamanan output tersedia pada:
- **[Matriks 18 Kasus Uji CRUD](docs/kasus-uji-crud.md)**
- **[Laporan Pertemuan 3 & Analisis Query](docs/laporan.md)**
