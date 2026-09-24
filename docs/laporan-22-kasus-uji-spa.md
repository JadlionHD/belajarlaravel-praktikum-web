# Laporan 22 Kasus Uji (Test Cases) SPA, Vue Router, Pinia, dan Konsumsi API

Dokumen ini memuat laporan pengujian lengkap 22 Kasus Uji (*Test Cases* TC-01 s/d TC-22) untuk modul **Pertemuan 7: SPA, Vue Router, Pinia, dan Konsumsi API**. Seluruh pengujian memverifikasi navigasi *nested route*, *navigation guard*, *session authentication Sanctum*, *state management* Pinia minimalis, *cancellation token* & urutan respons (*sequence counter*), penanganan 5 *state* antarmuka (*idle, loading, empty, success, error*), serta ketahanan terhadap kegagalan jaringan dan batas otorisasi Laravel.

---

## 📊 Ringkasan Hasil Pengujian

| Metrik | Hasil | Keterangan |
| :--- | :---: | :--- |
| **Total Kasus Uji** | **22** | TC-01 s/d TC-22 |
| **Status Lulus (PASS)** | **22 (100%)** | Seluruh pengujian memenuhi kontrak integrasi dan kriteria desain |
| **Status Gagal (FAIL)** | **0 (0%)** | Tidak ada kebocoran state atau anomali navigasi |
| **Metode Pengujian** | Integrasi Jaringan, DevTools, dan Unit/Feature Test | Penelusuran Network, State Pinia, dan Console |
| **Lingkungan Uji** | Node v24.21.0 / Vue 3.5.42 / Vue Router 4.6.4 / Pinia 3.0.4 / Axios 1.20.0 / PHP 8.5.10 / Laravel 12.x | Browser Chrome + Vue Devtools v7.x |

---

## 📑 Rincian 22 Kasus Uji (TC-01 s/d TC-22)

### TC-01 — Guest dan Navigation Guard
- **Kondisi Awal**: Browser dalam keadaan bersih (cookie sesi dihapus/guest).
- **Langkah Pengujian**:
  1. Akses langsung URL `http://localhost:5173/tickets/new`.
  2. Amati URL browser dan tampilan halaman.
  3. Lakukan login dengan kredensial sah (`ani@example.test` / `LatihanWeb2!2026`).
- **Expected Result**:
  - Router guard mendeteksi status guest dan mengalihkan navigasi ke `/login?redirect=%2Ftickets%2Fnew`.
  - Setelah login berhasil, aplikasi otomatis mengarahkan kembali pengguna ke `/tickets/new`.
- **Actual Result**: Sesuai ekspektasi. Pengguna dialihkan ke login, dan setelah sesi tercipta pengguna mendarat di halaman pembuatan form tiket.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-02 — Validasi Kredensial Login Gagal
- **Kondisi Awal**: Berada di halaman `/login`.
- **Langkah Pengujian**:
  1. Masukkan password salah: `ani@example.test` dengan password `SalahPassword123!`.
  2. Klik tombol "Login".
- **Expected Result**:
  - Backend merespons status `401 Unauthorized` dengan pesan `{"message": "Kredensial tidak valid."}`.
  - Halaman login menampilkan pesan peringatan lokal *"Email atau password salah."*.
  - Field password dibersihkan otomatis, tombol login kembali aktif, dan tidak terjadi redirect loop.
- **Actual Result**: Pesan kesalahan tampil jelas di atas form, input password ter-reset, dan tombol dapat ditekan kembali.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-03 — Pemulihan Sesi Saat Refresh (Hard Reload)
- **Kondisi Awal**: Pengguna terautentikasi dan berada di URL detail `http://localhost:5173/tickets/1`.
- **Langkah Pengujian**:
  1. Tekan tombol `F5` / *Hard Reload* browser.
  2. Amati urutan request di panel DevTools Network.
- **Expected Result**:
  - Router guard menjalankan `auth.restore()`.
  - Request `GET /api/v1/me` dikirim mendahului pengambilan data detail.
  - Nama pengguna ("Ani") tampil di navbar, lalu data detail tiket ID 1 dimuat sukses.
- **Actual Result**: Sesi dipulihkan secara instan tanpa kedip login, dilanjutkan pembacaan data tiket.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-04 — Penanganan Kegagalan Jaringan Saat Pemeriksaan Sesi
- **Kondisi Awal**: Pengguna berada di rute protected, lalu koneksi server backend dimatikan sementara.
- **Langkah Pengujian**:
  1. Lakukan refresh halaman.
  2. Amati navigasi router.
  3. Hidupkan kembali server backend, lalu klik tombol "Coba periksa lagi".
- **Expected Result**:
  - Guard menangkap exception jaringan dan mengarahkan ke `/session-error?redirect=...`.
  - Halaman menampilkan pesan bahwa server bermasalah dan bukan bukti logout.
  - Tombol retry menguji ulang sesi dan berhasil masuk setelah server pulih.
- **Actual Result**: Navigasi beralih ke SessionErrorView secara elegan tanpa redirect tak terhingga, dan retry berhasil memulihkan halaman asal.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-05 — Konsistensi Nested Route & Layout
- **Kondisi Awal**: Login aktif di dashboard helpdesk.
- **Langkah Pengujian**:
  1. Navigasi berturut-turut: `/tickets` ➜ `/tickets/new` ➜ `/tickets/1`.
  2. Gunakan tombol Back dan Forward pada browser.
- **Expected Result**:
  - Elemen bersama pada `HelpdeskLayout` (header h1, navbar, identitas user, tombol logout) tetap stabil (*persistent*) tanpa re-render penuh.
  - Konten child di bawah `<RouterView />` berganti sesuai rute aktif.
- **Actual Result**: Layout induk tidak pernah berkedip atau terlepas; pergantian komponen anak berjalan mulus.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-06 — Catch-All SPA 404 & Validasi Parameter ID
- **Kondisi Awal**: Pengguna login.
- **Langkah Pengujian**:
  1. Akses URL sembarang: `/halaman-ngawur`.
  2. Akses URL tiket dengan parameter non-numerik: `/tickets/abc`.
- **Expected Result**:
  - Rute `:id(\\d+)` menolak `/tickets/abc`.
  - Kedua URL ditangkap oleh catch-all route `/:pathMatch(.*)*` yang menampilkan `NotFoundView`.
  - Tidak ada request API salah ke backend yang terkirim.
- **Actual Result**: Tampil halaman "Halaman tidak ditemukan" dengan link kembali ke daftar; nol request API bocor.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-07 — Keadaan Loading & Pembersihan Data Usang
- **Kondisi Awal**: Berada di detail tiket ID 1.
- **Langkah Pengujian**:
  1. Aktifkan simulasi jaringan *Slow 3G* di DevTools Network.
  2. Buka tiket ID 2.
  3. Amati tampilan selama request berlangsung.
- **Expected Result**:
  - `state` berubah menjadi `loading`.
  - Teks *"Memuat data..."* dengan `role="status"` tampil.
  - Konten tiket ID 1 langsung dibersihkan (`data.value = null`) agar tidak tampak sebagai data tiket 2.
- **Actual Result**: Teks memuat tampil segera, dan data lama langsung hilang dari layar hingga data baru siap.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-08 — Keadaan Empty State
- **Kondisi Awal**: Login menggunakan akun pengujian baru yang belum memiliki tiket sama sekali.
- **Langkah Pengujian**:
  1. Buka halaman daftar tiket `/tickets`.
- **Expected Result**:
  - Backend merespons `200 OK` dengan payload `data: []`.
  - `state` berubah menjadi `empty`.
  - Tampil teks *"Belum ada data untuk halaman ini."* disertai tombol tautan *"Buat tiket"*, bukan pesan error.
- **Actual Result**: Komponen `ReadState` menampilkan status empty state secara akurat tanpa indikator kesalahan.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-09 — Navigasi Pagination Server & Pemulihan URL
- **Kondisi Awal**: Akun memiliki minimal 6 tiket (dengan `per_page=5`).
- **Langkah Pengujian**:
  1. Buka `/tickets`.
  2. Klik tombol "Berikutnya".
  3. Periksa URL, lalu tekan tombol "Back" browser.
- **Expected Result**:
  - Halaman 2 memuat URL `/tickets?page=2`.
  - Data tiket 5–6 tampil sesuai pagination server.
  - Tombol Back mengembalikan URL ke `/tickets?page=1` dan memicu pembacaan data halaman 1 secara otomatis.
- **Actual Result**: URL tersinkronisasi dua arah dengan query parameter; metadata pagination `current_page` dan `last_page` dihormati secara tepat.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-10 — Penanganan Race Condition & Pembatalan Request
- **Kondisi Awal**: Simulasi latensi jaringan aktif.
- **Langkah Pengujian**:
  1. Dari detail tiket A, klik cepat navigasi ke detail tiket B sebelum respons A tiba.
  2. Amati status request di DevTools Network dan data yang dirender.
- **Expected Result**:
  - Controller untuk request A dibatalkan (`canceled`).
  - Sequence counter naik; respons A yang terlambat dibuang (`own !== sequence`).
  - Hanya data tiket B yang dirender ke antarmuka.
- **Actual Result**: Request A berstatus *(canceled)* di browser Network tab, dan konten akhir yang tampil adalah milik tiket B secara konsisten.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-11 — Pembatalan Saat Unmount Komponen (Scope Disposed)
- **Kondisi Awal**: Berada di `/tickets` saat daftar sedang dalam proses loading.
- **Langkah Pengujian**:
  1. Klik tautan "Buat tiket" sebelum loading daftar selesai.
- **Expected Result**:
  - Hook `onScopeDispose` memanggil `controller.abort()` dan menaikkan sequence counter.
  - Request pembacaan daftar dibatalkan tanpa memunculkan pesan error "Permintaan dibatalkan" di halaman baru.
- **Actual Result**: Halaman form pembuatan tiket terbuka bersih tanpa ada error residu dari pembatalan halaman sebelumnya.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-12 — Penegakan Otorisasi Policy 403 (IDOR Protection)
- **Kondisi Awal**: Login sebagai user Ani (ID 1). Tiket ID 2 adalah milik user Budi.
- **Langkah Pengujian**:
  1. Buka URL `http://localhost:5173/tickets/2`.
- **Expected Result**:
  - Backend menolak dengan status `403 Forbidden`.
  - Komponen menampilkan pesan error: *"Anda tidak berhak mengakses tiket ini."*.
  - Tidak ada data deskripsi atau subjek tiket Budi yang bocor ke browser.
  - Sesi login Ani tetap utuh (tidak ter-logout).
- **Actual Result**: Respons 403 ditangkap `ReadState`, detail tiket tidak ter-render, dan identitas Ani tetap aktif di navbar.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-13 — Resource Not Found 404 (Objek Tidak Ada)
- **Kondisi Awal**: Pengguna login.
- **Langkah Pengujian**:
  1. Buka URL tiket dengan ID yang tidak ada di database: `/tickets/99999`.
- **Expected Result**:
  - Backend merespons status `404 Not Found`.
  - Antarmuka menampilkan pesan error: *"Data tidak ditemukan."* dengan tombol "Coba lagi".
- **Actual Result**: Pesan not found tampil jelas melalui komponen penanganan error.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-14 — Validasi Server 422 & Preservasi Draft
- **Kondisi Awal**: Berada di halaman `/tickets/new`.
- **Langkah Pengujian**:
  1. Isi judul dengan 10 karakter, biarkan field lain kosong.
  2. Submit formulir.
- **Expected Result**:
  - Backend merespons status `422 Unprocessable Content`.
  - Daftar pesan error validasi per field tampil di atas form.
  - Draft isian judul yang sudah diketik tidak hilang.
- **Actual Result**: Error validasi server dirender dalam bentuk daftar `<ul>`, dan nilai input form tetap dipertahankan.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-15 — Pencegahan Klik Ganda & Sukses 201 Created
- **Kondisi Awal**: Form tiket diisi dengan lengkap dan valid.
- **Langkah Pengujian**:
  1. Klik tombol "Simpan tiket" secara cepat dua kali berturut-turut.
- **Expected Result**:
  - Status `busy` aktif seketika dan menonaktifkan `<fieldset :disabled="busy">`.
  - Hanya tepat 1 request `POST /api/v1/tickets` yang terkirim ke server.
  - Respons `201 Created` mengembalikan ID baru dari server, lalu router berpindah ke detail tiket baru tersebut.
- **Actual Result**: Tombol terkunci dengan teks "Menyimpan...", 1 request POST tercipta, dan navigasi otomatis berpindah ke detail tiket ID server.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-16 — Penanganan Sesi Kedaluwarsa 401 Protected
- **Kondisi Awal**: Login aktif pada browser.
- **Langkah Pengujian**:
  1. Hapus cookie sesi `laravel_session` melalui panel Application/Cookies DevTools secara manual.
  2. Klik salah satu tiket untuk membuka halaman detail.
- **Expected Result**:
  - Request API detail ditolak backend dengan status `401 Unauthorized`.
  - Interceptor Axios menangkap error 401 dan memanggil callback `onUnauthorized()`.
  - Store auth dibersihkan (`auth.clear()`), dan browser dialihkan ke `/login?redirect=...`.
- **Actual Result**: Pengguna langsung dialihkan ke login secara otomatis tanpa perulangan redirect tak terbatas (*no infinite loop*).
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-17 — Penolakan CSRF Mismatch 419 & Recovery
- **Kondisi Awal**: Berada di formulir pembuatan tiket.
- **Langkah Pengujian**:
  1. Hapus cookie `XSRF-TOKEN` di browser sebelum menekan tombol simpan.
  2. Tekan tombol simpan.
- **Expected Result**:
  - Backend menolak request POST dengan status `419 Page Expired / CSRF Token Mismatch`.
  - UI menampilkan pesan pemulihan: *"CSRF/session tidak cocok. Muat ulang lalu login kembali bila perlu."*.
  - Tidak terjadi submit ulang liar.
- **Actual Result**: Pesan pemulihan tampil di atas formulir; draft pengguna tidak hilang.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-18 — Penegakan Rate Limiting 429
- **Kondisi Awal**: Berada di form login.
- **Langkah Pengujian**:
  1. Lakukan request login salah sebanyak 6 kali berturut-turut dalam 1 menit.
- **Expected Result**:
  - Percobaan ke-6 ditolak backend dengan status `429 Too Many Requests`.
  - Header respons menyertakan `Retry-After`.
  - UI menampilkan pesan: *"Terlalu banyak permintaan. Tunggu sesuai Retry-After sebelum mencoba lagi."*.
- **Actual Result**: Limiter `api-login` aktif, request dibatasi, dan tidak ada loop pengiriman otomatis di antarmuka.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-19 — Kegagalan Koneksi Offline / 5xx & Mekanisme Retry GET
- **Kondisi Awal**: Buka detail tiket.
- **Langkah Pengujian**:
  1. Putuskan sambungan internet / matikan backend Laravel, lalu klik "Coba lagi".
  2. Amati tampilan kesalahan.
  3. Sambungkan kembali internet / nyalakan backend, lalu klik "Coba lagi".
- **Expected Result**:
  - Kesalahan jaringan menampilkan pesan *"Tidak dapat menghubungi API. Periksa jaringan, server, dan CORS."*.
  - Klik "Coba lagi" memicu pemanggilan ulang fungsi endpoint secara reaktif dan berhasil memuat data tiket.
- **Actual Result**: State error terisolasi pada kartu pembacaan; tombol coba lagi berhasil memulihkan data setelah server online.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-20 — Penanganan Hasil POST yang Belum Pasti
- **Kondisi Awal**: Sedang mengirimkan form tiket baru.
- **Langkah Pengujian**:
  1. Simulasikan kegagalan jaringan tepat setelah paket POST dikirim (koneksi terputus sebelum respons 201 sampai).
- **Expected Result**:
  - UI tidak mengklaim "Data gagal dibuat", melainkan menampilkan peringatan jujur: *"Hasil simpan belum pasti. Periksa daftar sebelum mengirim ulang."*.
  - Form tidak otomatis mengirim ulang request POST untuk mencegah duplikasi data.
- **Actual Result**: Pesan kewaspadaan jaringan tampil di layar; pengguna diarahkan untuk memeriksa daftar terlebih dahulu.
- **Tipe Uji**: Mock Terkontrol
- **Status**: **PASS (LULUS)**

---

### TC-21 — Alur Logout Sesi Penuh
- **Kondisi Awal**: Pengguna sedang login.
- **Langkah Pengujian**:
  1. Klik tombol "Logout" di navbar.
  2. Amati panel Network dan status Pinia.
- **Expected Result**:
  - Request `POST /logout` dikirim ke backend dan merespons `204 No Content`.
  - Cookie sesi di sisi server diinvalisasi (`session()->invalidate()`).
  - Store auth direset (`user: null`, `ready: true`).
  - Browser dialihkan ke `/login`.
- **Actual Result**: Logout berhasil 100%; upaya membuka kembali URL `/tickets` setelah logout langsung ditolak guard dan diarahkan ke login.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---

### TC-22 — Audit Batas State Pinia & Build Produksi
- **Kondisi Awal**: Aplikasi sedang berjalan penuh.
- **Langkah Pengujian**:
  1. Periksa tab Vue & Pinia DevTools.
  2. Jalankan perintah kompilasi produksi `npm run build`.
- **Expected Result**:
  - Store Pinia **hanya** berisi state `user` dan `ready`. Tidak ada tiket, detail, pagination, atau form draft yang tersimpan di store global.
  - Tidak ada mutasi props langsung.
  - Kompilasi `npm run build` selesai dengan status sukses (0 error).
- **Actual Result**: State global terisolasi minimalis sesuai prinsip arsitektur; `npm run build` sukses 100%.
- **Tipe Uji**: Server Nyata
- **Status**: **PASS (LULUS)**

---
