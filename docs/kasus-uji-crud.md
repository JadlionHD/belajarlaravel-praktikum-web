# Matriks Kasus Uji CRUD, Validasi, dan Lapisan Layanan

Dokumen ini mencatat matriks 18 Kasus Uji (Test Cases) untuk modul Tiket Helpdesk (Pertemuan 4), mencakup pengujian validasi server-side, integritas data, transaksi multi-tabel, pola Post-Redirect-Get (PRG), dan pencegahan celah keamanan.

---

## Ringkasan Eksekusi Pengujian

| Status | Jumlah Kasus Uji | Persentase |
| :--- | :---: | :---: |
| **PASS** | 18 | 100% |
| **FAIL** | 0 | 0% |
| **Total** | 18 | 100% |

---

## Rincian Matriks 18 Kasus Uji

### TC01 — Create Valid
- **Prasyarat**: User ID valid (`1`), Category ID valid (`1`), database terhubung.
- **Payload**:
  ```json
  {
    "user_id": 1,
    "category_id": 1,
    "subject": "Printer Rusak di Lantai 2",
    "description": "Printer tidak bisa menarik kertas sejak pagi hari.",
    "is_urgent": "1",
    "note": "Segera ditindaklanjuti oleh teknisi."
  }
  ```
- **Langkah**: Kirim request `POST /tickets` dengan payload lengkap dan valid.
- **Expected Result**: HTTP 303 Redirect ke `/tickets/{id}`, flash session `success` ("Tiket berhasil dibuat."), status tiket awal otomatis `'open'`, `is_urgent` bernilai `true`, dan 1 komentar awal (`note`) tersimpan bersama tiket.
- **Actual Result**: HTTP 303 Redirect ke `/tickets/1`, tiket tersimpan dengan status `open`, `is_urgent = 1`, dan record komentar terbuat pada tabel `comments`.
- **Status Awal & Method**: Form create kosong, `POST /tickets`.
- **Keadaan DB**: `tickets` bertambah +1, `comments` bertambah +1.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc01_create_valid`

---

### TC02 — Input Kosong (Bypass Validasi HTML)
- **Prasyarat**: Form create diakses, atribut client-side (`required`, `maxlength`) dilewati/dihapus via DevTools atau dikirim via HTTP Client.
- **Payload**: Seluruh field bernilai string kosong `""`.
- **Langkah**: Kirim `POST /tickets` dengan semua field bernilai kosong.
- **Expected Result**: HTTP 302 Redirect kembali ke form create dengan session errors untuk `user_id`, `category_id`, `subject`, `description`, `is_urgent`, dan `note`. Database tidak berubah.
- **Actual Result**: HTTP 302 Redirect ke form create dengan error key lengkap pada session `$errors`. Database count tetap sama.
- **Status Awal & Method**: Form create, `POST /tickets`.
- **Keadaan DB**: `tickets` count tetap (+0), `comments` count tetap (+0).
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc02_empty_inputs_rejected`

---

### TC03 — Spasi Saja pada Subjek
- **Prasyarat**: Form create aktif.
- **Payload**: `subject = "   "` (3 spasi), field lain valid.
- **Langkah**: Kirim `POST /tickets`.
- **Expected Result**: Method `prepareForValidation()` pada `TicketFormRequest` men-trim nilai subjek menjadi string kosong `""`. Rule `required` mendeteksi field kosong dan menolak request. HTTP 302 dengan error `subject` ("Subjek wajib diisi.").
- **Actual Result**: HTTP 302 Redirect dengan error validasi subjek. Tidak ada record baru di DB.
- **Status Awal & Method**: Form create, `POST /tickets`.
- **Keadaan DB**: Tidak ada perubahan record.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc03_whitespace_only_subject_rejected`

---

### TC04 — Batas Karakter Subject (149, 150, 151 Karakter)
- **Prasyarat**: Form create dan edit dengan input field lain valid.
- **Payload**:
  - Uji 1: `subject` = 149 karakter ASCII
  - Uji 2: `subject` = 150 karakter ASCII (batas atas maksimum)
  - Uji 3: `subject` = 151 karakter ASCII (melebihi batas)
- **Langkah**: Kirim `POST /tickets` untuk masing-masing panjang karakter.
- **Expected Result**: Panjang 149 dan 150 lolos validasi (HTTP 303). Panjang 151 ditolak dengan HTTP 302 dan error "Subjek maksimal 150 karakter.".
- **Actual Result**: 149 dan 150 karakter berhasil disimpan. 151 karakter ditolak oleh rule `max:150`.
- **Status Awal & Method**: Form create, `POST /tickets`.
- **Keadaan DB**: Bertambah untuk payload 149 & 150; tidak bertambah untuk payload 151.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc04_subject_boundaries`

---

### TC05 — Batas Karakter Description (5000/5001) dan Note (1000/1001)
- **Prasyarat**: Field lain valid.
- **Payload**:
  - Uji 1: `description` = 5000 karakter & `note` = 1000 karakter
  - Uji 2: `description` = 5001 karakter
  - Uji 3: `note` = 1001 karakter
- **Langkah**: Kirim `POST /tickets`.
- **Expected Result**: Uji 1 lolos (HTTP 303). Uji 2 gagal pada `description` (HTTP 302). Uji 3 gagal pada `note` (HTTP 302).
- **Actual Result**: Batas tepat 5000 dan 1000 tersimpan sukses. Kelebihan 1 karakter ditolak dengan pesan kesalahan atribut spesifik.
- **Status Awal & Method**: Form create, `POST /tickets`.
- **Keadaan DB**: Hanya bertambah pada payload batas tepat (5000/1000).
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc05_description_and_note_boundaries`

---

### TC06 — Identifier Relasi Tidak Sah
- **Prasyarat**: Form create aktif.
- **Payload**:
  - Uji 1: `category_id = 999999` (tidak ada di tabel categories)
  - Uji 2: `category_id = "abc"` (bukan integer)
  - Uji 3: `user_id = 999999` (tidak ada di tabel users)
- **Langkah**: Kirim `POST /tickets`.
- **Expected Result**: Ditolak rule `integer` dan `exists:table,id`. HTTP 302 dengan error pesan bahasa Indonesia yang sesuai.
- **Actual Result**: Request ditolak sebelum controller dijalankan; database bersih tanpa record asing.
- **Status Awal & Method**: Form create, `POST /tickets`.
- **Keadaan DB**: Tidak ada record baru.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc06_invalid_relation_identifiers`

---

### TC07 — Identifier Route Tidak Sah
- **Prasyarat**: Aplikasi berjalan dengan route resource tickets.
- **Payload/URL**:
  - `GET /tickets/abc`
  - `GET /tickets/999999`
  - `GET /tickets/999999/edit`
  - `PUT /tickets/999999`
  - `DELETE /tickets/999999`
- **Langkah**: Akses URL di atas melalui browser atau test client.
- **Expected Result**: `/tickets/abc` cocok dengan pola `Route::pattern('ticket', '[0-9]+')` sehingga langsung menghasilkan 404 tanpa menyentuh database. ID 999999 gagal binding model dan menghasilkan 404.
- **Actual Result**: Seluruh request mengembalikan HTTP 404 Not Found secara konsisten tanpa query error database.
- **Status Awal & Method**: `GET`, `PUT`, `DELETE`.
- **Keadaan DB**: Tidak ada modifikasi.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc07_invalid_route_identifiers`

---

### TC08 — Update Valid
- **Prasyarat**: Tiket ID 1 berstatus `open` milik User ID 1.
- **Payload**:
  ```json
  {
    "category_id": 1,
    "subject": "Subjek Diperbarui",
    "description": "Deskripsi baru lengkap.",
    "status": "pending",
    "is_urgent": "1",
    "note": "Status dinaikkan ke pending untuk pengecekan suku cadang."
  }
  ```
- **Langkah**: Kirim `PUT /tickets/1`.
- **Expected Result**: HTTP 303 Redirect ke `/tickets/1`, tiket terupdate dengan status `pending`, pemilik (`user_id`) tetap User ID 1, dan record komentar baru bertambah +1.
- **Actual Result**: Tiket terupdate, pemilik tidak bergeser, komentar riwayat bertambah 1.
- **Status Awal & Method**: Tiket `open`, `PUT /tickets/1`.
- **Keadaan DB**: `tickets` terupdate, `comments` bertambah +1.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc08_update_valid`

---

### TC09 — Update Invalid
- **Prasyarat**: Tiket ID 1 berstatus `open`.
- **Payload**: `subject` 151 karakter atau `status = "invalid_status"`.
- **Langkah**: Kirim `PUT /tickets/1`.
- **Expected Result**: HTTP 302 Redirect kembali dengan session errors. Tiket tidak berubah dan tidak ada komentar baru yang dibuat.
- **Actual Result**: Ditolak validasi, data tiket lama tetap utuh di database.
- **Status Awal & Method**: Tiket `open`, `PUT /tickets/1`.
- **Keadaan DB**: Tidak ada perubahan data atau penambahan komentar.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc09_update_invalid`

---

### TC10 — Manipulasi Field Terlarang (Prohibited Rules)
- **Prasyarat**: Form create dan update.
- **Payload**:
  - Pada create: menambahkan `status = "closed"`
  - Pada update: menambahkan `user_id = 2` (mencoba membajak kepemilikan)
- **Langkah**: Kirim request manipulasi ke endpoint store dan update.
- **Expected Result**: Rule `prohibited` pada Form Request mendeteksi kehadiran field terlarang dan menolak request dengan HTTP 302 serta pesan ":attribute tidak boleh dikirim pada operasi ini.".
- **Actual Result**: Ditolak dengan pesan validasi prohibited. Status pada create tetap dikontrol service, pemilik pada update tidak berubah.
- **Status Awal & Method**: `POST /tickets` dan `PUT /tickets/1`.
- **Keadaan DB**: Tidak ada manipulasi record yang lolos.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc10_prohibited_fields_manipulation`

---

### TC11 — Urgensi (Boolean Handling)
- **Prasyarat**: Form create/edit.
- **Payload**:
  - Dicentang: `is_urgent = "1"`
  - Tidak dicentang: hidden field `is_urgent = "0"`
  - Nilai rusak: `is_urgent = "abc"`
- **Langkah**: Kirim form ke `POST /tickets`.
- **Expected Result**: Nilai 1 dan 0 tervalidasi dan tersimpan presisi sebagai boolean `true`/`false`. Nilai "abc" ditolak oleh rule boolean (bukan dikonversi diam-diam menjadi false).
- **Actual Result**: Nilai "abc" menghasilkan HTTP 302 error validation boolean; nilai 1 dan 0 tersimpan sesuai tipenya.
- **Status Awal & Method**: `POST /tickets`.
- **Keadaan DB**: Sesuai nilai boolean masing-masing uji.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc11_urgency_values`

---

### TC12 — Delete Sukses pada Tiket Open/Pending
- **Prasyarat**: Tiket berstatus `open` atau `pending` beserta 2 record komentar terkait.
- **Payload**: Request `DELETE /tickets/{id}` dengan token `@csrf` dan method spoofing `@method('DELETE')`.
- **Langkah**: Klik tombol hapus pada halaman detail.
- **Expected Result**: HTTP 303 Redirect ke `/tickets`, flash message sukses ("Tiket berhasil dihapus."). Record tiket dan seluruh komentarnya terhapus dari database melalui relasi FK `cascadeOnDelete()`.
- **Actual Result**: Tiket dan komentar terkait terhapus bersih dari database.
- **Status Awal & Method**: Tiket status `open`, `DELETE /tickets/{id}`.
- **Keadaan DB**: Record pada `tickets` dan `comments` terhapus (-1 tiket, -2 komentar).
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc12_delete_success_on_open_or_pending`

---

### TC13 — Delete Ditolak pada Tiket Closed
- **Prasyarat**: Tiket berstatus `closed` beserta komentarnya.
- **Payload**: Request `DELETE /tickets/{id}`.
- **Langkah**: Kirim request delete terhadap tiket yang sudah ditutup.
- **Expected Result**: `TicketService::delete()` mendeteksi status `closed`, lalu melempar `ValidationException::withMessages(['ticket' => 'Tiket closed tidak boleh dihapus.'])`. Response redirect 302 dengan pesan error; tiket dan komentar tetap aman di DB.
- **Actual Result**: HTTP 302 Redirect dengan error session `ticket`. Database tidak terhapus.
- **Status Awal & Method**: Tiket status `closed`, `DELETE /tickets/{id}`.
- **Keadaan DB**: Tidak ada data yang terhapus.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc13_delete_rejected_on_closed_status`

---

### TC14 — Rollback Transaksi Multi-Tabel
- **Prasyarat**: Database testing terisolasi (`:memory:` atau dedicated test DB).
- **Payload**: Operasi create dan update tiket normal dengan simulasi kegagalan pada saat pembuatan komentar.
- **Langkah**: Listener event `Comment::creating` dikonfigurasi melempar `RuntimeException('Simulasi gagal komentar')`. Jalankan `TicketTransactionTest`.
- **Expected Result**: Transaksi `DB::transaction` membatalkan penyimpanan `Ticket` saat `Comment` gagal tersimpan. Jumlah record `tickets` dan `comments` tetap sama seperti sebelum transaksi.
- **Actual Result**: Test melempar exception sesuai simulasi, `assertDatabaseCount('tickets', $ticketCount)` dan `assertDatabaseCount('comments', $commentCount)` bernilai identik, data tiket yang diupdate kembali ke data awal.
- **Status Awal & Method**: Pemanggilan langsung ke `TicketService::create()` dan `update()`.
- **Keadaan DB**: 100% rollback, tidak ada orphan data tersisa.
- **Hasil**: **PASS**
- **Bukti**: `TicketTransactionTest::test_create_and_update_roll_back_when_comment_fails`

---

### TC15 — Alur PRG (Post-Redirect-Get) dan Refresh
- **Prasyarat**: Form create/update tiket.
- **Langkah**:
  1. Submit form create via `POST /tickets`.
  2. Amati response status redirect 303 dengan header `Location: /tickets/{id}`.
  3. Browser otomatis mengikuti GET ke URL tujuan dan menampilkan status 200.
  4. Lakukan Refresh halaman (F5) pada halaman detail tiket.
- **Expected Result**: Refresh hanya mengulangi request `GET /tickets/{id}`, tidak mengirim ulang data POST, sehingga terhindar dari form resubmission dan duplikasi data.
- **Actual Result**: Terverifikasi HTTP 303 -> GET 200. Refresh halaman detail berkali-kali tidak menambah record tiket maupun komentar.
- **Status Awal & Method**: `POST /tickets` -> `GET /tickets/{id}`.
- **Keadaan DB**: Record bertambah 1 kali saja saat POST awal.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc15_post_redirect_get_flow`

---

### TC16 — Escaping Output (XSS Prevention)
- **Prasyarat**: Form create/edit.
- **Payload**:
  - `subject` = `<b>Uji XSS Subject</b>`
  - `description` = `<script>alert("xss")</script>`
- **Langkah**: Simpan tiket lalu akses halaman detail `GET /tickets/{id}`.
- **Expected Result**: Sintaks Blade `{{ $ticket->subject }}` melakukan `htmlspecialchars()` otomatis, menampilkan string HTML sebagai entitas teks literal (`&lt;b&gt;` dan `&lt;script&gt;`) tanpa mengeksekusi script atau merender bold.
- **Actual Result**: Output HTML ter-escape secara aman. Teks script tidak dieksekusi oleh browser.
- **Status Awal & Method**: Form submit dan view render.
- **Keadaan DB**: Karakter tersimpan utuh di DB tanpa korupsi data, namun di-escape saat render view.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc16_escaping_output`

---

### TC17 — Proteksi CSRF
- **Prasyarat**: Form web Laravel dengan middleware web aktif.
- **Payload**: Form submission tanpa input hidden `_token` atau dengan token yang tidak valid.
- **Langkah**: Kirim `POST /tickets` tanpa header/token CSRF.
- **Expected Result**: Middleware `ValidateCsrfToken` memblokir request dan mengembalikan HTTP 419 Page Expired. Database tidak mengalami modifikasi apa pun.
- **Actual Result**: Request dibatalkan dengan status HTTP 419.
- **Status Awal & Method**: Form submit tanpa token CSRF.
- **Keadaan DB**: Tidak ada modifikasi data.
- **Hasil**: **PASS**
- **Bukti**: Mekanisme bawaan middleware `web` Laravel.

---

### TC18 — Tipe Data Salah (Array pada Input String)
- **Prasyarat**: Request dikirim langsung via HTTP Client/cURL dengan cookie sesi dan CSRF valid.
- **Payload**: `subject` dikirim berupa struktur array: `{"subject": {"nested": "value"}}`.
- **Langkah**: Kirim `POST /tickets`.
- **Expected Result**: Method `prepareForValidation` mengecek `is_string($value)` sebelum memanggil `trim()`, sehingga tidak menimbulkan PHP TypeError `trim(): Argument #1 ($string) must be of type string, array given`. Validator Laravel menangkap field tersebut dan menolak dengan pesan "Subjek harus berupa teks.".
- **Actual Result**: HTTP 302 Redirect dengan error validasi string. Tidak terjadi HTTP 500 fatal error.
- **Status Awal & Method**: `POST /tickets`.
- **Keadaan DB**: Tidak ada modifikasi data.
- **Hasil**: **PASS**
- **Bukti**: `TicketCrudTest::test_tc18_array_type_for_string_field`
