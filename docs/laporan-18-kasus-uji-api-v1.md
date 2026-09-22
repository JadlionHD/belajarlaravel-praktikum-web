# Laporan 18 Kasus Uji REST API v1, Autentikasi, dan Otorisasi

Dokumen ini memuat laporan lengkap 18 Kasus Uji (*Test Cases* TC-01 s/d TC-18) untuk modul **Pertemuan 5 - REST API, Autentikasi, dan Otorisasi**. Pengujian mencakup autentikasi token Sanctum, otorisasi kepemilikan (*Policy*), hak akses peran (*Gate Admin*), validasi input, sanitasi data (*Resource allowlist*), proteksi IDOR, *Rate Limiting*, CORS, integritas transaksi/rollback, dan penutupan rute lama.

---

## 📊 Ringkasan Hasil Eksekusi Pengujian

| Metrik | Hasil | Keterangan |
| :--- | :---: | :--- |
| **Total Kasus Uji** | **18** | TC-01 s/d TC-18 |
| **Status PASS** | **18 (100%)** | Seluruh kasus uji lulus validasi |
| **Status FAIL** | **0 (0%)** | Tidak ada kegagalan |
| **Total Assertions** | **105** | Teruji otomatis via Pest Framework |
| **Durasi Eksekusi** | **~2.7 detik** | `vendor/bin/pest tests/Feature/ApiV1TicketTest.php` |

---

## 📑 Rincian 18 Kasus Uji (TC-01 s/d TC-18)

### TC-01 — Autentikasi Login (Valid, Kredensial Salah, dan Validasi)
- **Precondition**: User Ani (`ani@example.test`, password `LatihanWeb2!2026`) terdaftar di database.
- **Method & Path**: `POST /api/v1/auth/login`
- **Actor**: Klien Publik / Belum Terautentikasi
- **Payload Tersanitasi**:
  ```json
  {
    "email": "ani@example.test",
    "password": "[REDACTED]",
    "device_name": "postman-worksheet"
  }
  ```
- **Expected Status & Body**:
  - Login Valid: `200 OK`, header `Cache-Control: no-store, private`, body memuat `token_type: "Bearer"`, `access_token` plain text, `expires_at` (2 jam ke depan), dan objek `user: {id, name}`.
  - Password Salah: `401 Unauthorized` dengan pesan generik `{"message": "Kredensial tidak valid."}`.
  - Email Tidak Terdaftar: `401 Unauthorized` dengan pesan generik identik `{"message": "Kredensial tidak valid."}`.
  - Payload Kosong: `422 Unprocessable Content` validasi form.
- **Actual Result**: `200 OK` (token terbit), `401 Unauthorized` (pesan identik tanpa membocorkan eksistensi email), `422 Unprocessable Content` (payload kosong).
- **Perubahan DB**: Bertambah 1 record token hash baru di tabel `personal_access_tokens`.
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-01` & Postman `Login ani`.

---

### TC-02 — Penolakan Akses Tanpa Autentikasi
- **Precondition**: Tiket ID 1 milik Ani tersedia di database. Klien tidak mengirimkan header `Authorization` atau menyertakan token sembarang yang tidak valid.
- **Method & Path**: `GET /api/v1/tickets`, `POST /api/v1/tickets`, `GET /api/v1/tickets/1`, `PUT /api/v1/tickets/1`, `DELETE /api/v1/tickets/1`
- **Actor**: Tamu Publik / Token Palsu
- **Payload Tersanitasi**: `{}` / Kosong
- **Expected Status & Body**: `401 Unauthorized` (`{"message": "Unauthenticated."}`).
- **Actual Result**: Status `401 Unauthorized` konsisten di seluruh 5 endpoint privat.
- **Perubahan DB**: Tidak ada perubahan data (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-02` & Postman `Tanpa token`.

---

### TC-03 — Identitas dari Server & Penolakan Manipulasi Body
- **Precondition**: Ani terautentikasi dengan Bearer token. Kategori ID 1 (`Jaringan`) tersedia.
- **Method & Path**: `POST /api/v1/tickets`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**:
  - Payload Valid:
    ```json
    {
      "subject": "Wi-Fi ruang kuliah putus",
      "description": "Koneksi putus sejak pagi.",
      "category_id": 1,
      "is_urgent": false,
      "note": "Laporan awal."
    }
    ```
  - Payload Manipulatif: Menyisipkan `"user_id": 2`, `"is_admin": true`, atau `"status": "closed"`.
- **Expected Status & Body**:
  - Payload Valid: `201 Created`, header `Location: http://127.0.0.1:8000/api/v1/tickets/{id}`, respons `TicketResource` dengan owner Ani dan status otomatis `open`.
  - Payload Manipulatif: `422 Unprocessable Content` (field `user_id`, `is_admin`, dan `status` dilarang oleh rule `prohibited`).
- **Actual Result**: `201 Created` untuk input valid; `422 Unprocessable Content` saat klien mencoba memalsukan pemilik atau status.
- **Perubahan DB**: Input valid: tabel `tickets` bertambah +1 (user_id = Ani, status = open), tabel `comments` bertambah +1 (user_id = Ani). Manipulasi: DB tidak berubah (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-03` & Postman `Create ani`, `Pemalsuan pemilik`.

---

### TC-04 — Daftar Privat Pengguna & Paginasi
- **Precondition**: Ani memiliki 2 tiket di database, Budi memiliki 1 tiket.
- **Method & Path**: `GET /api/v1/tickets?per_page=1&page=1`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**: Query parameters `per_page=1&page=1`.
- **Expected Status & Body**: `200 OK`. Body memuat `data`, `links`, dan `meta`. Data hanya berisi tiket milik Ani (`owner.id === user_ani.id`). Tiket milik Budi tidak muncul. `meta.total = 2`. Parameter `per_page=0`, `per_page=51`, atau `page=0` menghasilkan `422 Unprocessable Content`.
- **Actual Result**: `200 OK` (daftar privat terfilter otomatis); `422 Unprocessable Content` saat `per_page` di luar rentang 1–50.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-04` & Postman `Daftar Ani`, `Pagination salah`.

---

### TC-05 — Proteksi IDOR pada Detail Tiket
- **Precondition**: Tiket ID Ani tersimpan di database dengan subjek `"Rahasia Ani"` dan deskripsi rahasia. Budi memiliki token sah miliknya sendiri.
- **Method & Path**: `GET /api/v1/tickets/{ticket_ani}`
- **Actor**: Budi (`token_budi`)
- **Payload Tersanitasi**: None
- **Expected Status & Body**: `403 Forbidden`. Tidak ada data `subject`, `description`, `owner`, atau `category` milik Ani yang dibocorkan ke respons.
- **Actual Result**: Status `403 Forbidden` (`{"message": "This action is unauthorized."}`). Data sensitif Ani tidak keluar.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-05` & Postman `Budi membaca Ani`.

---

### TC-06 — Proteksi IDOR pada Update dan Delete Tiket
- **Precondition**: Tiket ID Ani tersimpan di database (`ticket_ani`) dengan status `open` dan 1 komentar awal.
- **Method & Path**: `PUT /api/v1/tickets/{ticket_ani}` dan `DELETE /api/v1/tickets/{ticket_ani}`
- **Actor**: Budi (`token_budi`)
- **Payload Tersanitasi**:
  ```json
  {
    "subject": "Tiket Dibajak Budi",
    "description": "Deskripsi baru.",
    "category_id": 1,
    "is_urgent": false,
    "status": "pending",
    "note": "Catatan Budi."
  }
  ```
- **Expected Status & Body**: `403 Forbidden` pada kedua method `PUT` dan `DELETE`.
- **Actual Result**: Status `403 Forbidden`.
- **Perubahan DB**: Tiket Ani di database tetap utuh dengan subjek awal, status tetap `open`, dan jumlah komentar tetap 1 (+0 write).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-06` & Postman `Budi mengubah Ani`, `Budi menghapus Ani`.

---

### TC-07 — Pembaruan Tiket oleh Pemilik Sah
- **Precondition**: Tiket milik Ani berstatus `open` dengan 1 komentar awal.
- **Method & Path**: `PUT /api/v1/tickets/{ticket_ani}`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**:
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
- **Expected Status & Body**: `200 OK`. Mengembalikan `TicketResource` terbaru dengan status `pending`. Pemilik tetap Ani. Komentar baru tersimpan. Jika menyertakan `user_id` atau `is_admin`, request ditolak `422`.
- **Actual Result**: `200 OK` (tiket terupdate dan komentar bertambah); `422` saat mencoba manipulasi user_id/is_admin.
- **Perubahan DB**: Record tiket terupdate; record tabel `comments` bertambah +1 (penulis komentar = Ani).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-07` & Postman `Update Ani`.

---

### TC-08 — Validasi Input & Karakter Batas
- **Precondition**: Ani terautentikasi.
- **Method & Path**: `POST /api/v1/tickets`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**: Pengujian nilai ekstrim:
  - `subject`: string kosong, spasi saja (`"   "`), array, 151 karakter.
  - `note`: 1001 karakter.
  - `description`: 5001 karakter.
  - `category_id`: 99999 (tidak ada di tabel categories).
  - `is_urgent`: string non-boolean (`"bukan-boolean"`).
- **Expected Status & Body**: `422 Unprocessable Content` dengan pesan error validasi spesifik untuk masing-masing field.
- **Actual Result**: Status `422 Unprocessable Content` pada seluruh variasi data buruk.
- **Perubahan DB**: Tidak ada record tiket atau komentar baru yang tersimpan (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-08` & Postman `Judul kosong`.

---

### TC-09 — Penanganan ID Tidak Valid & Non-Numerik
- **Precondition**: Ani terautentikasi.
- **Method & Path**: `GET /api/v1/tickets/99999` dan `GET /api/v1/tickets/abc`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**: None
- **Expected Status & Body**: `404 Not Found`. Rute dengan ID `abc` ditolak oleh regex `Route::pattern('ticket', '[0-9]+')`, sedangkan ID `99999` ditolak karena model tidak ditemukan.
- **Actual Result**: Status `404 Not Found` berupa respons JSON bersih tanpa stack trace.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-09` & Postman `ID bukan angka`.

---

### TC-10 — Aturan Tiket Closed & Cascade Delete
- **Precondition**: Ani memiliki 1 tiket berstatus `closed` dan 1 tiket berstatus `open` (beserta 1 komentar).
- **Method & Path**: `DELETE /api/v1/tickets/{id}`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**: None
- **Expected Status & Body**:
  - Hapus Tiket Closed: `422 Unprocessable Content` (`"Tiket closed tidak boleh dihapus."`). Tiket tetap ada di DB.
  - Hapus Tiket Open: `204 No Content` (body kosong). Tiket dan komentarnya terhapus dari DB. Request berikutnya mengembalikan `404`.
- **Actual Result**: `422 Unprocessable Content` untuk tiket closed; `204 No Content` body kosong untuk tiket open.
- **Perubahan DB**: Tiket closed tetap ada; tiket open dan seluruh komentarnya terhapus secara berantai (*cascade on delete*).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-10` & Postman `Close Ani`, `Closed tidak bisa dihapus`, `Delete Ani`, `ID terhapus`.

---

### TC-11 — Hak Akses Peran Admin & Proteksi Tiket Pengguna
- **Precondition**: Ani (`is_admin: false`) dan Admin (`is_admin: true`) terdaftar. Terdapat 3 tiket di database.
- **Method & Path**: `GET /api/v1/reports/summary` dan `PUT /api/v1/tickets/{ticket_ani}`
- **Actor**: Ani (`token_ani`) dan Admin (`token_admin`)
- **Payload Tersanitasi**: None untuk laporan; payload update untuk tiket Ani.
- **Expected Status & Body**:
  - Ani panggil `/reports/summary`: `403 Forbidden` (Gate `view-ticket-summary` menolak non-admin).
  - Admin panggil `/reports/summary`: `200 OK` (`{"data": {"ticket_count": 3}}`).
  - Admin panggil `PUT /tickets/{ticket_ani}`: `403 Forbidden` (Admin tidak memiliki hak mengubah tiket milik pengguna lain).
- **Actual Result**: Sesuai expected (`403` untuk Ani summary, `200` untuk Admin summary, `403` untuk Admin update tiket Ani).
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-11` & Postman `Laporan Ani`, `Laporan admin`, `Admin mengubah Ani`.

---

### TC-12 — Kerahasiaan Data Sensitif (Resource Allowlist)
- **Precondition**: Ani login dan tiket tersedia di database.
- **Method & Path**: `GET /api/v1/me` dan `GET /api/v1/tickets/{id}`
- **Actor**: Ani (`token_ani`)
- **Payload Tersanitasi**: None
- **Expected Status & Body**: `200 OK`. Seluruh atribut sensitif seperti `password`, `remember_token`, `two_factor_secret`, `email`, `is_admin`, dan `access_token` tidak pernah muncul pada body JSON detail tiket maupun profil ringkas.
- **Actual Result**: Respons hanya memuat atribut allowlist `TicketResource` (`id`, `subject`, `description`, `status`, `is_urgent`, `owner`, `category`, `created_at`, `updated_at`).
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-12` & Postman `Detail aman`.

---

### TC-13 — Pencabutan Token Saat Logout (Stateless Revocation)
- **Precondition**: Ani memiliki 2 token aktif (`device-1` dan `device-2`).
- **Method & Path**: `POST /api/v1/auth/logout`
- **Actor**: Ani (`token_device_1`)
- **Payload Tersanitasi**: None
- **Expected Status & Body**: `204 No Content` body kosong. Record token aktif dicabut dari tabel `personal_access_tokens`. Request berikutnya menggunakan Token 1 ditolak dengan `401 Unauthorized`. Token 2 tetap sah (`200 OK`).
- **Actual Result**: `204 No Content`; Token 1 ditolak `401 Unauthorized`; Token 2 tetap dapat mengakses `/api/v1/me` (`200 OK`).
- **Perubahan DB**: 1 record token terkait terhapus dari `personal_access_tokens` (-1).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-13` & Postman `Logout ani`, `Token ani dicabut`.

---

### TC-14 — Penolakan Token Kadaluwarsa (Token Expiry)
- **Precondition**: Token dibuat dengan nilai `expires_at` di masa lalu (`now()->subMinute()`).
- **Method & Path**: `GET /api/v1/me`
- **Actor**: Pengguna dengan Token Kadaluwarsa
- **Payload Tersanitasi**: None
- **Expected Status & Body**: `401 Unauthorized` (`{"message": "Unauthenticated."}`).
- **Actual Result**: Status `401 Unauthorized`.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-14`.

---

### TC-15 — Pembatasan Laju Request (Rate Limiter)
- **Precondition**: Rate limiter `api-login` dibatasi 5 request/menit per IP address.
- **Method & Path**: `POST /api/v1/auth/login`
- **Actor**: Pengguna dari IP yang sama
- **Payload Tersanitasi**: Kredensial login Ani (dikirim 6 kali beruntun).
- **Expected Status & Body**: Request 1 s/d 5 diproses normal. Request ke-6 ditolak dengan status `429 Too Many Requests` dan menyertakan header `Retry-After`.
- **Actual Result**: Request ke-6 mengembalikan status `429 Too Many Requests` dengan header `Retry-After: 60`.
- **Perubahan DB**: Counter cache rate limiter bertambah.
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-15`.

---

### TC-16 — Penegakan Kebijakan CORS
- **Precondition**: Konfigurasi `config/cors.php` menetapkan `allowed_origins` ke `['http://localhost:5173']`.
- **Method & Path**: `OPTIONS /api/v1/tickets` (Preflight Request)
- **Actor**: Browser Client dari Origin `http://localhost:5173` vs `http://localhost:5999`
- **Payload Tersanitasi**: Headers `Access-Control-Request-Method: POST`, `Access-Control-Request-Headers: authorization,content-type`.
- **Expected Status & Body**:
  - Origin `http://localhost:5173`: Menerima header respons `Access-Control-Allow-Origin: http://localhost:5173`.
  - Origin `http://localhost:5999`: Tidak menerima header izin untuk origin 5999.
- **Actual Result**: Header `Access-Control-Allow-Origin` mencocokkan origin yang diizinkan dan menolak origin luar.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-16`.

---

### TC-17 — Integritas Transaksi Multi-Tabel & Rollback
- **Precondition**: Simulasi event `Comment::creating` yang dipaksa melempar `RuntimeException("Simulasi gagal komentar")`.
- **Method & Path**: Eksekusi method `ApiTicketService::create` dan `ApiTicketService::update`
- **Actor**: Ani
- **Payload Tersanitasi**: Data tiket dan catatan yang valid.
- **Expected Status & Body**: Transaksi `DB::transaction()` membatalkan seluruh operasi secara atomik. Pada `create`, tiket baru tidak tersimpan di database. Pada `update`, nilai tiket dikembalikan ke keadaan semula (*revert*).
- **Actual Result**: Exception tertangkap; jumlah baris pada tabel `tickets` dan `comments` tidak bertambah; pembaruan tiket di-rollback total.
- **Perubahan DB**: Rollback sempurna (+0 perubahan permanen).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-17`.

---

### TC-18 — Verifikasi Penutupan Seluruh Rute Tiket Lama
- **Precondition**: Pendaftaran rute resource tiket lama di `routes/web.php` telah dinonaktifkan.
- **Method & Path**: `GET /tickets`, `POST /tickets`, `GET /tickets/1`, `PUT /tickets/1`, `DELETE /tickets/1`
- **Actor**: Pengguna Publik (Tanpa Token)
- **Payload Tersanitasi**: None / Any
- **Expected Status & Body**: `404 Not Found` pada seluruh URL tiket lama (tidak ada endpoint publik yang dapat membaca atau memanipulasi tiket).
- **Actual Result**: Seluruh request mengembalikan status `404 Not Found`.
- **Perubahan DB**: Read-only (+0).
- **Status**: **LULUS (PASS)**
- **Bukti Pengujian**: `ApiV1TicketTest > TC-18`.

---

## 📌 Kesimpulan
Seluruh **18 Kasus Uji** telah berhasil diuji dan diverifikasi secara menyeluruh, membuktikan kepatuhan terhadap seluruh kontrak REST API v1, prinsip *Zero Trust*, otorisasi *Policy/Gate*, dan integritas data transaksional.
