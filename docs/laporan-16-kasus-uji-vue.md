# Laporan 16 Kasus Uji (Test Cases) Vue 3 Composition API & Desain Komponen

Dokumen ini memuat laporan pengujian lengkap 16 Kasus Uji (*Test Cases* TC-01 s/d TC-16) untuk modul **Pertemuan 6: Dasar Vue dan Desain Komponen**. Seluruh pengujian membuktikan arsitektur *Single File Component* (SFC), reaktivitas *state*, pemisahan *props* dan *events*, *lifecycle hooks*, isolasi *draft form*, serta integritas data di memori browser.

---

## 📊 Ringkasan Hasil Pengujian

| Metrik | Hasil | Keterangan |
| :--- | :---: | :--- |
| **Total Kasus Uji** | **16** | TC-01 s/d TC-16 |
| **Status Lulus (PASS)** | **16 (100%)** | Seluruh pengujian memenuhi kriteria desain |
| **Status Gagal (FAIL)** | **0 (0%)** | Tidak ada pelanggaran batas kontrak komponen |
| **Metode Pengujian** | Manual & UI DevTools | Penelusuran State, Props, Event, dan Lifecycle |
| **Lingkungan Uji** | Node v24.21.0 / Vue 3.5.42 / Vite 8.3.0 | Browser Chrome + Vue Devtools v7.x |

---

## 📑 Rincian 16 Kasus Uji (TC-01 s/d TC-16)

### TC-01 — Verifikasi Data Awal (Initial State)
- **Kondisi Awal**: Halaman dimuat ulang (*hard refresh* `F5`).
- **Langkah Pengujian**:
  1. Buka halaman demo `http://127.0.0.1:8000/pertemuan6`.
  2. Periksa teks ringkasan tiket dan daftar kartu yang tampil.
- **Expected Result**:
  - Total tiket yang tampil adalah 3 dari 3 tiket.
  - Terdapat tepat 1 tiket berstatus *open* (#1 Wi-Fi putus), 1 tiket *pending* (#2 Proyektor redup), dan 1 tiket *closed* (#3 Kabel LAN rusak).
  - Nama kategori terpetakan dengan benar ("Jaringan" untuk ID 1, "Perangkat" untuk ID 2).
- **Actual Result**: Sesuai ekspektasi. Tampil 3 tiket dengan komposisi status 1 open, 1 pending, 1 closed. Nama kategori tampil akurat.
- **Status**: **PASS (LULUS)**

---

### TC-02 — Pengujian Filter Status & Kemurnian State
- **Kondisi Awal**: Berada pada data awal (total 3 tiket).
- **Langkah Pengujian**:
  1. Ubah dropdown filter status secara berurutan: `Semua` (`all`), `Terbuka` (`open`), `Diproses` (`pending`), `Selesai` (`closed`).
  2. Amati jumlah kartu yang ditampilkan dan periksa `tickets.length` di Vue Devtools.
- **Expected Result**:
  - Filter `all`: menampilkan 3 kartu ("Menampilkan 3 dari 3 tiket").
  - Filter `open`: menampilkan 1 kartu (#1).
  - Filter `pending`: menampilkan 1 kartu (#2).
  - Filter `closed`: menampilkan 1 kartu (#3).
  - Nilai data sumber `tickets.value.length` di `App.vue` tetap 3 pada setiap perubahan filter.
- **Actual Result**: Kartu tiket tersaring secara reaktif sesuai opsi terpilih. Array data sumber tidak pernah termutasi atau terpotong.
- **Status**: **PASS (LULUS)**

---

### TC-03 — Perubahan Status Tiket & Reaktivitas Filter
- **Kondisi Awal**: Pilih dropdown filter status ke `Terbuka` (`open`). Kartu tiket #1 sedang tampil.
- **Langkah Pengujian**:
  1. Klik tombol "Lanjutkan status" pada tiket #1.
  2. Amati daftar kartu tiket pada filter saat ini.
  3. Ubah filter status ke `Diproses` (`pending`).
- **Expected Result**:
  - Tiket #1 langsung hilang dari tampilan filter `open` karena statusnya berubah menjadi `pending`.
  - Saat filter diubah ke `pending`, tiket #1 muncul berdampingan dengan tiket #2.
  - Total tiket di memori tetap 3 ("Menampilkan 2 dari 3 tiket" pada filter pending).
- **Actual Result**: Status tiket berhasil bertransisi `open` -> `pending`. Re-render terjadi instan tanpa reload browser.
- **Status**: **PASS (LULUS)**

---

### TC-04 — Proteksi Tiket Berstatus Selesai (Closed)
- **Kondisi Awal**: Filter status diatur ke `Semua` atau `Selesai`.
- **Langkah Pengujian**:
  1. Amati tombol "Lanjutkan status" pada tiket #3 yang berstatus `closed`.
  2. Periksa atribut tombol pada elemen DOM.
  3. Uji pengiriman event `advance(3)` secara manual.
- **Expected Result**:
  - Tombol pada tiket #3 memiliki atribut `:disabled="true"` sehingga tidak dapat diklik oleh pengguna.
  - Handler `advanceTicket` pada parent memiliki klausul `if (!ticket || !next[ticket.status]) return` sehingga mengabaikan ID jika status sudah `closed`.
- **Actual Result**: Tombol disabled secara visual dan fungsional. Transisi status tiket closed ditolak.
- **Status**: **PASS (LULUS)**

---

### TC-05 — Tampilan Pesan Kosong (Empty State)
- **Kondisi Awal**: Pindahkan tiket #1 dari `open` ke `pending` (seperti pada TC-03).
- **Langkah Pengujian**:
  1. Atur dropdown filter ke `Terbuka` (`open`).
  2. Amati tampilan pada area daftar tiket.
- **Expected Result**:
  - Daftar kartu `<ul>` tidak dirender.
  - Elemen `<p role="status">Tidak ada tiket sesuai filter.</p>` ditampilkan menggantikan daftar tiket.
- **Actual Result**: Pesan empty state tampil dengan atribut `role="status"` yang ramah aksesibilitas.
- **Status**: **PASS (LULUS)**

---

### TC-06 — Isolasi Draft Form Lokal dari State Parent
- **Kondisi Awal**: Buka form tambah tiket.
- **Langkah Pengujian**:
  1. Ketik nilai pada judul ("Percobaan draft"), uraian ("Deskripsi pengujian"), pilih kategori, dan isi catatan awal.
  2. **Jangan klik tombol submit**.
  3. Buka Vue Devtools dan periksa state `tickets` pada komponen `App`.
- **Expected Result**:
  - State `tickets` pada `App` tetap berjumlah 3.
  - Daftar kartu tiket tidak berubah dan tidak mengalami re-render yang merusak data.
  - Perubahan input hanya tersimpan di dalam objek `form` `reactive()` milik `TicketForm`.
- **Actual Result**: Draft sepenuhnya terisolasi secara lokal di dalam komponen `TicketForm`.
- **Status**: **PASS (LULUS)**

---

### TC-07 — Pembuatan Tiket Sah (Valid Submission)
- **Kondisi Awal**: Form tiket baru terbuka. Filter status berada pada opsi selain `all` (misal: `closed`).
- **Langkah Pengujian**:
  1. Isi Judul: `Printer lantai 2 offline`.
  2. Isi Uraian: `Lampu indikator berkedip merah dan tidak merespons perintah cetak.`.
  3. Pilih Kategori: `Perangkat` (ID: 2).
  4. Centang Checkbox: `Mendesak`.
  5. Isi Catatan Awal: `Sudah dicoba restart manual tetapi belum berhasil.`.
  6. Klik tombol "Tambah tiket".
- **Expected Result**:
  - Tiket baru ditambahkan ke array `tickets` dengan ID unik berurutan (#4).
  - Status tiket baru otomatis ditentukan server/parent menjadi `'open'`.
  - Filter status otomatis direset kembali ke `'all'`.
  - Pesan konfirmasi muncul: *"Tiket #4 ditambahkan ke state lokal."*.
  - Form dikosongkan dan input judul otomatis terfokus kembali.
  - Total tiket menjadi 4 dari 4.
- **Actual Result**: Tiket baru berhasil terbit dengan status `open`, ID 4, filter kembali `all`, dan instance form direset bersih.
- **Status**: **PASS (LULUS)**

---

### TC-08 — Pemutusan Referensi Payload Form dari State Parent
- **Kondisi Awal**: Sesaat setelah TC-07 berhasil dijalankan.
- **Langkah Pengujian**:
  1. Ketik teks baru pada input judul yang sudah kosong: `Input gangguan baru`.
  2. Ketik teks baru pada textarea deskripsi: `Uraian gangguan baru`.
  3. Buka Vue Devtools dan periksa objek tiket #4 di array `tickets` milik `App` serta `lastSubmission`.
- **Expected Result**:
  - Data tiket #4 yang baru tersimpan di parent tidak mengalami perubahan teks.
  - `lastSubmission` tetap memuat salinan objek dari pengiriman sebelumnya.
- **Actual Result**: Terbukti objek payload hasil emit berupa *plain object copy*, sehingga ketikan baru pada draft form tidak memutasi data tiket di parent.
- **Status**: **PASS (LULUS)**

---

### TC-09 — Penolakan Validasi Input Kosong atau Hanya Spasi (Whitespace)
- **Kondisi Awal**: Form tambah tiket terbuka.
- **Langkah Pengujian**:
  1. Isi judul dengan spasi saja (`   `), deskripsi kosong, catatan kosong.
  2. Klik tombol "Tambah tiket".
- **Expected Result**:
  - Muncul pesan error lokal di atas form: *"Lengkapi judul, uraian, kategori, dan catatan sesuai batas."* dengan `role="alert"`.
  - Tidak ada event `submit` yang di-emit ke parent.
  - Total tiket pada parent tetap dan draft form tidak direset.
- **Actual Result**: Validasi client-side mendeteksi input kosong dan string whitespace. Form mempertahankan nilai yang diketik tanpa memutasi state parent.
- **Status**: **PASS (LULUS)**

---

### TC-10 — Pengujian Batas Maksimum Karakter (Boundary Testing)
- **Kondisi Awal**: Form tambah tiket terbuka.
- **Langkah Pengujian**:
  1. Uji Judul: masukkan string sepanjang 150 karakter (lolos), lalu uji 151 karakter (ditolak).
  2. Uji Uraian: masukkan string sepanjang 5000 karakter (lolos), lalu uji 5001 karakter (ditolak).
  3. Uji Catatan Awal: masukkan string 1000 karakter (lolos), lalu uji 1001 karakter (ditolak).
- **Expected Result**:
  - String tepat pada batas maksimum diizinkan untuk di-submit.
  - String yang melebihi batas (151, 5001, 1001) ditolak oleh fungsi validasi dengan pesan error.
- **Actual Result**: Batas karakter berbasis perhitungan `String.length` JavaScript bekerja presisi sesuai spesifikasi kontrak.
- **Status**: **PASS (LULUS)**

---

### TC-11 — Validasi Pemilihan Kategori & Tipe Data Boolean Urgensi
- **Kondisi Awal**: Form tambah tiket terbuka.
- **Langkah Pengujian**:
  1. Isi judul, deskripsi, dan catatan secara valid, tetapi biarkan dropdown kategori pada posisi *"Pilih kategori"* (nilai string kosong `""`). Klik submit.
  2. Pilih kategori "Jaringan" (ID: 1), biarkan checkbox mendesak tidak dicentang. Klik submit.
  3. Ulangi dengan mencentang checkbox mendesak. Klik submit.
- **Expected Result**:
  - Kategori kosong ditolak dengan pesan error.
  - Checkbox tidak dicentang menghasilkan nilai boolean literal `false`.
  - Checkbox dicentang menghasilkan nilai boolean literal `true` dan memunculkan label `<strong>Mendesak</strong>` pada kartu.
- **Actual Result**: Kategori numerik tervalidasi terhadap daftar kategori yang sah. Urgensi tersimpan sebagai boolean murni.
- **Status**: **PASS (LULUS)**

---

### TC-12 — Siklus Hidup Form (Lifecycle, Unmount/Mount, & Key Reset)
- **Kondisi Awal**: Form terbuka dan diisi draft sebagian.
- **Langkah Pengujian**:
  1. Klik tombol "Tutup form (draft hilang)".
  2. Periksa hierarki pohon komponen di Vue Devtools.
  3. Klik tombol "Buka form".
  4. Amati isi form dan posisi kursor teks.
- **Expected Result**:
  - Saat form ditutup, direktif `v-if` menghancurkan (*unmount*) komponen `TicketForm` dari DOM.
  - Komponen `TicketForm` hilang dari daftar komponen DevTools.
  - Saat form dibuka kembali, instance baru dibuat (*mounted*), draft kembali kosong, dan input judul langsung aktif terfokus (`focus()`) via hook `onMounted()`.
- **Actual Result**: Siklus hidup mount, unmount, dan autofocus terbukti bekerja secara sempurna.
- **Status**: **PASS (LULUS)**

---

### TC-13 — Penggunaan Ulang Komponen (Component Reuse) & Slot
- **Kondisi Awal**: Halaman terbuka penuh.
- **Langkah Pengujian**:
  1. Periksa penggunaan komponen `BasePanel` pada daftar tiket dan pada form penambahan tiket.
  2. Periksa penggunaan komponen `TicketStatus` pada setiap kartu tiket.
- **Expected Result**:
  - `BasePanel` digunakan dua kali dengan konten yang berbeda melalui slot default dan footer yang berbeda melalui named slot `#footer`.
  - `BasePanel` tidak memiliki kode khusus yang bergantung pada tiket (*zero coupling*).
  - `TicketStatus` digunakan pada setiap kartu tiket untuk merender label dan gaya warna yang seragam.
- **Actual Result**: Kedua komponen berhasil digunakan kembali (*reusable*) secara elegan tanpa duplikasi kode markup.
- **Status**: **PASS (LULUS)**

---

### TC-14 — Efek Samping Eksternal (Watcher & Title Tab Browser)
- **Kondisi Awal**: Buka tab browser pada halaman aplikasi.
- **Langkah Pengujian**:
  1. Amati judul tab browser (*document.title*) saat halaman pertama kali dimuat.
  2. Tambahkan satu tiket baru melalui form.
  3. Ubah-ubah dropdown filter status.
  4. Lakukan refresh browser (`F5`).
- **Expected Result**:
  - Judul tab awal menampilkan `Helpdesk latihan (3)`.
  - Setelah tiket ditambah, watcher sinkron memperbarui judul tab menjadi `Helpdesk latihan (4)`.
  - Mengubah dropdown filter tidak mengubah judul tab (karena yang dipantau adalah `tickets.value.length`, bukan `filteredTickets`).
  - Refresh browser mengembalikan data ke 3 dan judul tab kembali ke `Helpdesk latihan (3)`.
- **Actual Result**: Watcher bekerja responsif hanya terhadap dependensi jumlah tiket sumber. State in-memory pulih saat refresh.
- **Status**: **PASS (LULUS)**

---

### TC-15 — Audit Kepatuhan Props (Immutability Props)
- **Kondisi Awal**: Melakukan peninjauan statis terhadap kode sumber komponen anak.
- **Langkah Pengujian**:
  1. Periksa `TicketCard.vue`: pastikan tidak ada kode seperti `props.ticket.status = '...'`.
  2. Periksa `TicketList.vue`: pastikan tidak ada `props.tickets.push(...)` atau pemutasi array lainnya.
  3. Periksa `TicketFilter.vue`: pastikan nilai dibaca melalui `:value="props.modelValue"` dan perubahan dikirim via event `update:modelValue`.
- **Expected Result**:
  - Seluruh komponen anak mematuhi aturan aliran data satu arah (*one-way data flow*).
  - Mutasi array atau objek hanya dilakukan di dalam `App.vue`.
- **Actual Result**: Lulus audit. Tidak ditemukan mutasi props baik langsung maupun pada objek nested.
- **Status**: **PASS (LULUS)**

---

### TC-16 — Kompilasi Produksi (Vite Build) & Pencegahan Kerentanan XSS
- **Kondisi Awal**: Terminal repositori aktif.
- **Langkah Pengujian**:
  1. Jalankan perintah kompilasi produksi `npm run build`.
  2. Pada form tiket, masukkan judul yang memuat tag HTML: `<script>alert('xss')</script><b>Gangguan</b>`.
  3. Submit tiket dan amati kartu yang dirender di layar.
- **Expected Result**:
  - `npm run build` selesai tanpa error kompilasi atau warning lint.
  - Teks HTML pada judul tiket ditampilkan secara literal sebagai teks biasa (di-*escape* otomatis oleh sintaks kurung kurawal ganda `{{ }}` Vue), tidak dieksekusi sebagai script atau tag tebal.
- **Actual Result**: Build sukses dalam waktu ~4 detik. Output HTML ter-escape secara aman dan bebas dari celah injeksi XSS.
- **Status**: **PASS (LULUS)**
