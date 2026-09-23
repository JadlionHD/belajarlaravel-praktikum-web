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

## 🔐 Modul Pertemuan 5: REST API, Autentikasi, dan Otorisasi

### 1. Spesifikasi Aktual Lingkungan & Paket
- **PHP**: 8.5+
- **Laravel Framework**: 12.x
- **Laravel Sanctum**: v4.3.3 (Personal Access Token)
- **DBMS**: PostgreSQL (`pgsql`) / SQLite `:memory:` untuk pengujian
- **Format Header**: `Accept: application/json`, `Content-Type: application/json`

### 2. Mode Autentikasi Token (Stateless)
- **Token Mode**: Personal Access Token (PAT) Sanctum melalui header `Authorization: Bearer <token>` tanpa session cookie.
- **Masa Berlaku**: Token berlaku selama 2 jam sejak diterbitkan.
- **Keamanan Respons**: Login menyertakan header `Cache-Control: no-store, private` agar token tidak tersimpan di cache perantara/browser.
- **Pencabutan Token (Logout)**: `POST /api/v1/auth/logout` mencabut token aktif saat ini (`currentAccessToken()->delete()`) dan menghasilkan `204 No Content`. Token lain milik pengguna yang sama tetap sah.

### 3. Penutupan Rute Publik Lama
Seluruh endpoint tiket lama (`/tickets`) di `routes/web.php` yang tidak memiliki autentikasi atau policy telah dinonaktifkan secara total. Tidak ada lagi jalur publik yang dapat membaca atau memanipulasi data tiket.

### 4. Peta Kontrak REST API v1 (`/api/v1`)

| Method | Endpoint | Middleware / Auth | Status | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `POST` | `/api/v1/auth/login` | `throttle:api-login` | `200` / `401` / `422` | Autentikasi pengguna, menghasilkan Bearer token |
| `POST` | `/api/v1/auth/logout` | `auth:sanctum`, `throttle:api-v1` | `204` | Mencabut access token yang sedang digunakan |
| `GET` | `/api/v1/me` | `auth:sanctum`, `throttle:api-v1` | `200` | Mendapatkan identitas ringkas pengguna (`id`, `name`) |
| `GET` | `/api/v1/categories` | `auth:sanctum`, `throttle:api-v1` | `200` | Daftar kategori tiket terurut nama (`id`, `name`) |
| `GET` | `/api/v1/tickets` | `auth:sanctum`, `throttle:api-v1` | `200` / `422` | Daftar tiket milik sendiri dengan paginasi (`per_page` 1–50) |
| `POST` | `/api/v1/tickets` | `auth:sanctum`, `throttle:api-v1` | `201` / `422` | Buat tiket baru + catatan awal, mengembalikan header `Location` |
| `GET` | `/api/v1/tickets/{ticket}` | `auth:sanctum`, `throttle:api-v1` | `200` / `403` / `404` | Detail tiket milik sendiri |
| `PUT/PATCH` | `/api/v1/tickets/{ticket}` | `auth:sanctum`, `throttle:api-v1` | `200` / `403` / `404` / `422` | Update tiket sendiri (wajib payload lengkap) + catatan baru |
| `DELETE` | `/api/v1/tickets/{ticket}` | `auth:sanctum`, `throttle:api-v1` | `204` / `403` / `404` / `422` | Hapus tiket (gagal 422 jika status `closed`) |
| `GET` | `/api/v1/reports/summary` | `auth:sanctum`, `throttle:api-v1` | `200` / `403` | Laporan agregat jumlah tiket (khusus Admin via Gate) |

> **Catatan Pola PATCH**: Pada latihan ini, endpoint `PATCH` mewajibkan payload lengkap yang sama dengan `PUT`.

### 5. Otorisasi Berlapis (Policy & Gate)
- **`TicketPolicy` (Kepemilikan)**:
  - `viewAny`, `create`: Diizinkan untuk pengguna terautentikasi.
  - `view`, `update`, `delete`: Menegakkan aturan kepemilikan ketat `(int) $user->id === (int) $ticket->user_id`. Pengguna lain (misal: Budi mengakses tiket Ani) ditolak dengan **`403 Forbidden`**.
- **Gate `view-ticket-summary` (Peran Admin)**:
  - Hanya pengguna dengan flag `is_admin = true` yang dapat mengakses `/api/v1/reports/summary`. Pengguna reguler ditolak dengan `403 Forbidden`.
  - Admin **tidak** memiliki izin otomatis untuk mengubah atau menghapus tiket milik pengguna lain.

### 6. Integritas Transaksional (`ApiTicketService`)
- Operasi manipulasi tiket dibungkus dalam `DB::transaction()` dengan `lockForUpdate()`.
- Pemilik tiket (`user_id`) mutlak diambil dari objek `$actor` (model `User` terautentikasi), **bukan** dari request body.
- Payload request dilarang memuat `user_id` atau `is_admin` (`prohibited`).
- Tiket berstatus `closed` tidak boleh dihapus (`422 Unprocessable Content`).
- Penghapusan tiket terbuka (`open`/`pending`) otomatis menghapus riwayat komentar melalui database cascade.

### 7. Transformasi Data & Kerahasiaan (`TicketResource`)
- Menggunakan API Resource dengan allowlist atribut: `id`, `subject`, `description`, `status`, `is_urgent`, `created_at`, `updated_at`, serta `owner` dan `category` (jika ter-load).
- Atribut sensitif seperti `password`, `remember_token`, `email`, `is_admin`, dan `access_token` **tidak pernah** diekspos ke klien.

### 8. Pembatasan Request (Rate Limiter)
- **`api-login`**: Maksimal 5 permintaan per menit per IP address. Permintaan ke-6 menghasilkan **`429 Too Many Requests`** dengan header `Retry-After`.
- **`api-v1`**: Maksimal 60 permintaan per menit per pengguna terautentikasi.

### 9. Konfigurasi CORS (`config/cors.php`)
- `allowed_origins`: `['http://localhost:5173']`
- `allowed_headers`: `['Accept', 'Authorization', 'Content-Type']`
- `exposed_headers`: `['Location', 'Retry-After']`
- `supports_credentials`: `false`

### 10. Data Uji Demo (`ApiDemoSeeder`)
Jalankan seeder untuk mengisi akun pengujian:
```bash
php artisan db:seed --class=ApiDemoSeeder
```
- **Ani (Pemilik Tiket 1)**: `ani@example.test` | Password: `LatihanWeb2!2026` | `is_admin: false`
- **Budi (Pemilik Tiket 2)**: `budi@example.test` | Password: `LatihanWeb2!2026` | `is_admin: false`
- **Admin**: `admin@example.test` | Password: `LatihanWeb2!2026` | `is_admin: true`
- **Kategori**: `Jaringan`

### 11. Pengujian dengan Koleksi Postman
1. File koleksi telah disediakan di: [docs/helpdesk-v1.postman_collection.json](docs/helpdesk-v1.postman_collection.json).
2. Impor file tersebut ke Postman Desktop (**Import** ➜ pilih file JSON).
3. **Pengaturan Variabel Demo (Sangat Penting)**:
   - Koleksi sengaja mengosongkan variabel `password_demo` agar aman dari commit Git.
   - Buka koleksi **`Helpdesk API v1 - Pertemuan 5`** ➜ tab **Variables**.
   - Pada baris **`password_demo`**, isi kolom **Initial Value** dan **Current Value** dengan:
     ```text
     LatihanWeb2!2026
     ```
   - Tekan **Save** (`Ctrl + S`).
   - *(Catatan: Jika `password_demo` dibiarkan kosong, request login akan ditolak dengan `422 Unprocessable Content` sehingga token tidak tersimpan dan menyebabkan seluruh request berikutnya gagal).*
4. **Menjalankan Runner**:
   - Klik kanan koleksi ➜ **Run collection**.
   - Jalankan secara berurutan. Seluruh 31 assertions pengujian akan otomatis berstatus **PASS 100%**.
   - Jika mendapati status `429 Too Many Requests` akibat pengujian cepat berulang, jalankan `php artisan cache:clear` di terminal lalu ulangi.
5. **Ekspor Bersih**:
   - Kosongkan kembali `password_demo` dan token pada koleksi sebelum mengekspor ulang atau melakukan commit ke Git.

### 12. Contoh Kontrak Request & Response Bersih

#### A. Login Sukses (`POST /api/v1/auth/login`)
**Request Body**:
```json
{
  "email": "ani@example.test",
  "password": "LatihanWeb2!2026",
  "device_name": "postman-worksheet"
}
```
**Response (`200 OK`, `Cache-Control: no-store, private`)**:
```json
{
  "token_type": "Bearer",
  "access_token": "1|uT3L9...",
  "expires_at": "2026-09-22T08:00:00+00:00",
  "user": {
    "id": 1,
    "name": "Ani"
  }
}
```

#### B. Pembuatan Tiket (`POST /api/v1/tickets`)
**Headers**: `Authorization: Bearer <token_ani>`  
**Request Body**:
```json
{
  "subject": "Wi-Fi ruang kuliah putus",
  "description": "Koneksi putus sejak pagi.",
  "category_id": 1,
  "is_urgent": false,
  "note": "Laporan awal."
}
```
**Response (`201 Created`, Header `Location: http://127.0.0.1:8000/api/v1/tickets/1`)**:
```json
{
  "data": {
    "id": 1,
    "subject": "Wi-Fi ruang kuliah putus",
    "description": "Koneksi putus sejak pagi.",
    "status": "open",
    "is_urgent": false,
    "owner": {
      "id": 1,
      "name": "Ani"
    },
    "category": {
      "id": 1,
      "name": "Jaringan"
    },
    "created_at": "2026-09-22T06:00:00+00:00",
    "updated_at": "2026-09-22T06:00:00+00:00"
  }
}
```
*(Perhatikan: Field sensitif seperti `email`, `password`, `is_admin`, dan `access_token` disaring dan tidak bocor ke output).*

#### C. Pembaruan Tiket Lengkap (`PUT /api/v1/tickets/{id}`)
**Headers**: `Authorization: Bearer <token_ani>`  
**Request Body**:
```json
{
  "subject": "Wi-Fi sedang diperiksa",
  "description": "Petugas memeriksa koneksi.",
  "category_id": 1,
  "is_urgent": false,
  "status": "pending",
  "note": "Perubahan status oleh pemilik."
}
```
**Response (`200 OK`)**: Mengembalikan objek `TicketResource` terbaru dan otomatis menambah 1 komentar riwayat pada database.

#### D. Penghapusan Tiket (`DELETE /api/v1/tickets/{id}`)
**Headers**: `Authorization: Bearer <token_ani>`  
- **Tiket Open / Pending**: Menghasilkan **`204 No Content`** dengan response body kosong, menghapus record tiket dan seluruh komentarnya secara berantai (*cascade*).
- **Tiket Closed**: Ditolak dengan **`422 Unprocessable Content`** (`"Tiket closed tidak boleh dihapus."`).

#### E. Laporan Ringkasan Admin (`GET /api/v1/reports/summary`)
**Headers**: `Authorization: Bearer <token_admin>`  
**Response (`200 OK`)**:
```json
{
  "data": {
    "ticket_count": 2
  }
}
```
*(Pengguna non-admin yang memanggil endpoint ini akan ditolak dengan `403 Forbidden`).*

### 13. Strategi Versioning API (`v1` ke `v2`)
- Prefix `/api/v1` dan namespace `App\Http\Controllers\Api\V1` menegakkan kontrak API yang stabil.
- Perubahan kompatibilitas mundur (*breaking changes*) seperti perubahan struktur respons, perubahan tipe data, atau penambahan field wajib baru harus dirilis di bawah versi baru (misalnya `/api/v2`).
- Klien eksisting dapat terus menggunakan `/api/v1` tanpa risiko kegagalan integrasi selama masa transisi.

### 14. Menjalankan Automated Feature Test (18 Test Cases)
Untuk menjalankan pengujian otomatis seluruh 18 Test Case (TC-01 s/d TC-18) Pertemuan 5:
```bash
vendor/bin/pest tests/Feature/ApiV1TicketTest.php
```
Seluruh 18 pengujian mencakup:
- **TC-01**: Login (200, 401 pesan identik, 422)
- **TC-02**: Penolakan tanpa autentikasi (401)
- **TC-03**: Server identity & penolakan manipulasi body (201 & 422)
- **TC-04**: Paginasi privat per pengguna
- **TC-05**: IDOR detail (403 tanpa data bocor)
- **TC-06**: IDOR update/delete (403 dan DB tidak berubah)
- **TC-07**: Update pemilik & penambahan komentar
- **TC-08**: Validasi field & batasan karakter (422)
- **TC-09**: ID tidak ditemukan atau non-numerik (404)
- **TC-10**: Aturan tiket closed & cascade delete (422, 204, 404)
- **TC-11**: Hak akses Gate Admin & proteksi tiket user (403 & 200)
- **TC-12**: Pencegahan kebocoran data sensitif (allowlist data)
- **TC-13**: Logout token aktif (204) dan validitas token sekunder
- **TC-14**: Penolakan token kadaluwarsa (401)
- **TC-15**: Penegakan Rate Limiter (429 & Retry-After)
- **TC-16**: Penegakan kebijakan CORS Origin
- **TC-17**: Rollback transaksi saat kegagalan komentar
- **TC-18**: Verifikasi penutupan seluruh rute tiket lama (404)

---

## 🎨 Modul Pertemuan 6: Dasar Vue dan Desain Komponen

### 1. Spesifikasi Aktual Lingkungan & Paket Frontend
- **Node.js**: `v24.21.0`
- **NPM**: `11.19.0`
- **Vue**: `3.5.42` (SFC Composition API dengan `<script setup>`)
- **Vite Bundler**: `8.3.0` (`vite-plus` v0.3.0)
- **Plugin Vue**: `@vitejs/plugin-vue` v6.0.9
- **Inertia.js Integration**: `@inertiajs/vue3` v3.7.1
- **Akses Halaman Demo**: `http://localhost:8000/pertemuan6` atau `http://localhost:5173/pertemuan6`

### 2. Menjalankan Frontend
```bash
# Instalasi dependensi
npm install

# Menjalankan server development (HMR)
npm run dev

# Kompilasi build produksi
npm run build
```

### 3. Struktur & Batas Tanggung Jawab Komponen
Studi kasus membagi UI helpdesk lokal menjadi 7 jenis komponen yang bersih dan terisolasi:

```text
App (Single Source of Truth: tickets, selectedStatus, showForm, formVersion)
├── BasePanel (Title: "Daftar tiket", Slot Default & Footer)
│   ├── TicketFilter (Props: modelValue ◄──► Emits: update:modelValue)
│   └── TicketList (Props: tickets, categories ◄── Emits: advance)
│       └── TicketCard (Props: ticket, categoryName ◄── Emits: advance)
│           └── TicketStatus (Props: status [computed label])
└── BasePanel (Title: "Tiket baru", Slot Default & Footer)
    └── TicketForm (Props: categories ◄── Emits: submit) [Draft Lokal: form]
```

### 4. Tabel Kontrak Komunikasi (Props, Emits, dan Slots)

| Komponen | Path File | Props Diterima | Emits Dikirim | Slot | Tanggung Jawab Utama |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`App`** | `src/App.vue` | - | - | - | Pemilik state `tickets`, filter `selectedStatus`, title watcher, eksekusi penambahan & transisi tiket. |
| **`BasePanel`** | `src/components/BasePanel.vue` | `title` (String, req) | - | `<slot />`, `<slot name="footer" />` | Kerangka panel reusable dengan header judul, body dinamis, dan footer opsional. |
| **`TicketStatus`** | `src/components/TicketStatus.vue` | `status` (String, req) | - | - | Badge visual dan label teks dari `computed` (`open`, `pending`, `closed`). |
| **`TicketCard`** | `src/components/TicketCard.vue` | `ticket` (Object, req), `categoryName` (String) | `advance(id)` | - | Tampilan detail kartu tiket, indikator mendesak, dan tombol "Lanjutkan status" (disabled jika closed). |
| **`TicketList`** | `src/components/TicketList.vue` | `tickets` (Array, req), `categories` (Array, req) | `advance(id)` | - | Conditional rendering empty state (`tickets.length === 0`), perulangan kartu `:key="ticket.id"`, dan relay event `advance`. |
| **`TicketFilter`** | `src/components/TicketFilter.vue` | `modelValue` (String, req) | `update:modelValue` | - | Kontrol dropdown filter status dengan pola 2-way binding manual tanpa mutasi prop. |
| **`TicketForm`** | `src/components/TicketForm.vue` | `categories` (Array, req) | `submit(payload)` | - | Draft form `reactive` lokal, autofocus via `onMounted`, validasi client, dan pembuatan payload objek baru. |

### 5. Kepemilikan State (*State Ownership*) & Alur Komunikasi
- **Props Mengalir Turun (*Top-Down*)**: Data tiket (`tickets`) dan kategori (`categories`) hanya dimiliki oleh `App.vue`. Komponen anak hanya menerima data melalui props dalam mode baca (*read-only*).
- **Event Mengalir Naik (*Bottom-Up*)**: Komponen anak dilarang keras mengubah props secara langsung (`props.ticket.status = '...'` dilarang). Untuk meminta perubahan status, `TicketCard` mengirim `emit('advance', id)` -> di-*relay* oleh `TicketList` -> ditangkap oleh `App.vue` yang secara resmi memutasi `tickets.value`.
- **Draft Lokal Form**: `TicketForm` memiliki state draft sendiri menggunakan `reactive()`. Saat submit, objek salinan datar dibuat (*flat payload*) agar referensi draft form terputus dari state parent.
- **Siklus Hidup & Reset**: Reset form dikendalikan oleh parent dengan menaikkan `formVersion++` yang mengubah `:key` pada `<TicketForm>`, sehingga Vue membuang instance lama dan membuat instance baru dengan input judul terfokus kembali (`onMounted`).

### 6. Matriks Hasil 16 Kasus Uji (TC-01 s/d TC-16)

| Kode | Kasus Uji | Kondisi Awal & Langkah | Hasil yang Diharapkan | Hasil Aktual | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Data Awal | Muat ulang halaman (`refresh`) | Total 3 tiket, open=1, pending=1, closed=1, kategori benar. | Total 3 tiket, open/pending/closed masing-masing 1, kategori "Jaringan" & "Perangkat". | **PASS** |
| **TC-02** | Filter Status | Pilih all, open, pending, closed | Menampilkan berturut-turut 3, 1, 1, 1 kartu. `tickets.length` tetap 3. | Filter menyaring tampilan dengan tepat tanpa mengubah array data sumber. | **PASS** |
| **TC-03** | Perubahan Status | Filter open, klik "Lanjutkan status" pada #1 | Tiket #1 berubah pending, hilang dari filter open, total sumber tetap 3. | Tiket berpindah status, re-render reaktif instan, total tiket tetap 3. | **PASS** |
| **TC-04** | Status Closed | Periksa tombol pada tiket #3 (closed) | Tombol disabled, parent tidak memproses jika menerima ID closed. | Tombol berstatus disabled dan transisi ditolak oleh handler parent. | **PASS** |
| **TC-05** | Empty State | Tiket open dipindahkan ke pending, pilih filter open | Muncul teks *"Tidak ada tiket sesuai filter."* dengan role `status`. | Empty state tampil bersih menggantikan elemen daftar. | **PASS** |
| **TC-06** | Draft Terpisah | Ketik judul/uraian form tanpa submit | State `tickets` dan daftar kartu tidak berubah sama sekali. | Form draft sepenuhnya terisolasi dalam state `reactive` lokal child. | **PASS** |
| **TC-07** | Submit Sah | Isi form lengkap & submit | Menambah tepat 1 tiket (ID 4), status open, filter kembali all, form kosong. | Tiket bertambah dengan ID unik, filter reset ke all, form bersih terfokus. | **PASS** |
| **TC-08** | Payload Terpisah | Ketik form baru setelah submit berhasil | Data tiket yang baru ditambahkan dan `lastSubmission` tidak terpengaruh. | Parent memegang salinan payload baru, bebas efek samping ketikan form. | **PASS** |
| **TC-09** | Validasi Kosong/Spasi | Isi field dengan spasi atau kosongkan lalu submit | Pesan peringatan muncul, tiket tidak bertambah, form tidak direset. | Validasi mendeteksi input kosong/whitespace, form mempertahankan draft. | **PASS** |
| **TC-10** | Batas Karakter | Cek batas panjang string (subject 150/151, note 1000/1001) | Tepat batas diizinkan, melebihi batas ditolak dengan pesan error. | Validasi panjang string JavaScript UTF-16 ditegakkan secara akurat. | **PASS** |
| **TC-11** | Kategori & Checkbox | Submit tanpa pilih kategori; toggle checkbox | Tanpa kategori ditolak; checkbox menghasilkan boolean `true`/`false`. | Validasi kategori berhasil, nilai urgensi tersimpan sebagai boolean murni. | **PASS** |
| **TC-12** | Lifecycle Form | Tutup form lalu buka; submit sah | Tutup membuang draft, buka memicu autofocus; submit menaikkan `formVersion`. | `v-if` menghancurkan/membuat ulang instance, `onMounted` memfokuskan judul. | **PASS** |
| **TC-13** | Slot & Reuse | Amati kedua BasePanel & badge status | BasePanel menampilkan header/footer berbeda via slot; TicketStatus reusable. | Slot default dan footer terpasang sempurna tanpa duplikasi layout. | **PASS** |
| **TC-14** | Watcher & Title | Perhatikan judul tab browser; refresh halaman | Judul tab berubah: `Helpdesk latihan (3)` -> `(4)`. Refresh kembali ke (3). | Watcher sinkron dengan `tickets.length`, refresh mengembalikan in-memory state. | **PASS** |
| **TC-15** | Audit Mutasi Props | Analisis kode komponen anak | Tidak ada operator mutasi/assignment langsung pada properti objek props. | Mutasi dilarang total; semua pembaruan melalui event emit ke parent. | **PASS** |
| **TC-16** | Build & Escape | Jalankan `npm run build` dan input teks `<b>tag</b>` | Build sukses 0 error; input HTML ditampilkan secara literal (aman dari XSS). | Vite build lulus sempurna; interpolasi teks Vue aman secara default. | **PASS** |

### 7. Batas Validasi Frontend & Catatan Desain
- **Penyimpanan di Memori**: Seluruh state tiket berada di memori browser pengguna (*RAM*). Refresh browser akan mengembalikan data ke `initialTickets` (3 tiket). Tidak ada `fetch`, `axios`, atau API endpoint Laravel yang dipanggil pada modul ini.
- **Validasi Frontend vs Backend**: Pengecekan panjang karakter (150, 5000, 1000) dan kelengkapan field di frontend bertujuan memberikan umpan balik langsung kepada pengguna (*UX*), bukan pengganti Form Request dan otorisasi Sanctum backend pada Pertemuan 5.

