# DECISIONS

Catatan keputusan arsitektur (ADR). Jangan mengubah keputusan lama. Jika berubah, tulis ADR baru dan ubah status yang lama menjadi "Digantikan oleh ADR-xxx".

Status: Diterima / Diterima, menunggu verifikasi / Digantikan.

---

## ADR-001 — Aplikasi Laravel clone-and-modify, bukan package
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Rencana awal adalah package Composer yang bisa dipasang di proyek lain. Pemilik hanya butuh repo di GitHub dan versi online di Vercel untuk testing. Jika ingin dipakai, pemilik tinggal clone dan memodifikasinya.
- **Keputusan:** Satu aplikasi Laravel utuh di root repo. Tidak ada package Composer, `vendor:publish`, workbench, atau folder `demo/` terpisah.
- **Alternatif:** (a) Package Composer dengan aplikasi demo terpisah. (b) Aplikasi mandiri dengan service Python.
- **Konsekuensi:** Struktur dan tugas lebih sederhana. Tidak ada jaminan kompatibilitas untuk proyek orang lain. Memakai di HRIS berarti menyalin atau memodifikasi kode.

## ADR-002 — Identitas anonim tanpa `user_id`
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Pengguna hanya mendaftarkan wajah dan mengecek apakah sudah terdaftar. Pemilik berencana memakai ini untuk absensi HRIS di masa depan.
- **Keputusan:** v1 anonim. Tabel `face_templates` tidak punya `user_id`. Disiapkan kolom nullable `external_ref` dan kolom `purpose` di sesi (F-16) agar absensi bisa ditambahkan nanti.
- **Alternatif:** Menambah `user_id` sekarang. Ditolak karena akan menambah autentikasi dan menyimpang dari tujuan v1.
- **Konsekuensi:** Absensi membutuhkan verifikasi 1:1, identitas, dan liveness yang lebih kuat. Itu di luar v1 dan perlu desain terpisah.

## ADR-003 — Embedding di browser, pencocokan di PHP, tanpa Python
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Stack yang diinginkan adalah PHP dan JavaScript saja.
- **Keputusan:** Deteksi wajah, liveness, dan embedding berjalan di browser. Server menerima embedding dan mencocokkannya di PHP dengan cosine similarity.
- **Alternatif:** Service Python (dlib/InsightFace). Ditolak karena menambah infrastruktur dan di luar batasan stack.
- **Konsekuensi:** Foto tidak pernah dikirim ke server. Kualitas dan keamanan bergantung pada model di browser. Pencocokan 1:N di PHP hanya cocok untuk beberapa ribu wajah (ADR-005).

## ADR-004 — Library JS: `@vladmandic/human`
- **Tanggal:** 2026-11-20
- **Status:** Diterima, menunggu verifikasi (T-004)
- **Konteks:** MediaPipe Face Landmarker hanya menghasilkan landmark, bukan embedding pengenalan wajah. Dibutuhkan satu library untuk landmark, kedip, yaw, dan embedding.
- **Keputusan:** Pakai `@vladmandic/human`.
- **Alternatif:** MediaPipe ditambah model embedding terpisah (face-api.js atau ONNX).
- **Konsekuensi:** Satu dependensi runtime. Dimensi embedding dan lisensi model harus diverifikasi di T-004 (`TODO:`).

## ADR-005 — Database wajib, Neon Postgres, skema portabel
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Fitur cek dan hapus berlaku lintas pengguna. Filesystem Vercel hanya bisa ditulis di `/tmp` dan hilang antar-instance.
- **Keputusan:** Simpan template dan state sesi di database. Demo memakai Neon Postgres, lokal dan test memakai SQLite. Skema memakai tipe portabel (tanpa `jsonb` atau tipe vektor), sehingga MySQL juga bisa dipakai.
- **Alternatif:** (a) Mode client-only dengan IndexedDB. Ditolak karena tidak ada anti-duplikat antar orang dan liveness sepenuhnya bisa dipalsukan. (b) pgvector. Ditolak untuk v1 karena tidak portabel dan embedding terenkripsi tidak bisa dicari di sisi database.
- **Konsekuensi:** Setiap identify membaca semua template (dibatasi `FACE_MAX_TEMPLATES`, default demo 2.000). Dibaca bertahap dengan `lazyById` agar memori kecil.

## ADR-006 — Embedding disimpan terenkripsi, biner, bukan JSON
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Rencana awal menyimpan embedding sebagai JSON. Data biometrik sensitif sehingga harus dienkripsi, dan hasil enkripsi bukan JSON.
- **Keputusan:** Embedding dinormalisasi L2, dikemas sebagai float32 biner, lalu dienkripsi dengan `Crypt::encryptString` dan disimpan di kolom `text`. Dekripsi hanya saat pencocokan dan tidak di-cache antar request.
- **Alternatif:** (a) JSON plaintext. Ditolak karena tidak aman. (b) Hanya enkripsi at-rest dari penyedia database. Ditolak karena pemilik memilih enkripsi di aplikasi.
- **Konsekuensi:** Mengganti `APP_KEY` membuat semua template tidak terbaca. Ukuran per template ±8 KB untuk 1024 dimensi `[ASUMSI]`. Dekripsi menambah waktu identify (M-3, diukur di T-033).

## ADR-007 — State liveness di database, token sekali pakai
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** `/tmp` Vercel tidak dibagi antar-instance, sehingga file dan memori tidak bisa menyimpan state.
- **Keputusan:** Sesi liveness disimpan di `face_liveness_sessions`. Hanya hash token yang disimpan. Sesi dikonsumsi pada permintaan pertama yang membawa token, berhasil atau gagal. Sesi kedaluwarsa dibersihkan secara oportunistik saat membuat sesi baru.
- **Alternatif:** Cache file atau Redis. Ditolak karena tidak tersedia atau menambah layanan.
- **Konsekuensi:** Setiap percobaan ulang butuh sesi baru. Tidak ada scheduler, jadi pembersihan bergantung pada trafik.

## ADR-008 — Validasi telemetri liveness di server (plausibility check)
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Cek liveness di browser saja bisa dipalsukan. Server tidak bisa memastikan kamera nyata dipakai.
- **Keputusan:** Browser mengirim telemetri per tantangan. Server memeriksa urutan, durasi, jumlah frame, ambang EAR/yaw, dan kecocokan dengan waktu server.
- **Alternatif:** (a) Hanya token dan timeout. Terlalu mudah dipalsukan. (b) Beberapa embedding dari frame berbeda yang harus konsisten. Ditunda karena lebih mahal.
- **Konsekuensi:** Lebih sulit dipalsukan dibanding opsi (a), tetapi tidak setara SDK liveness komersial. Batasan ini wajib tertulis di README.

## ADR-009 — Hosting Vercel dengan `vercel-php@0.9.0`, tanpa rencana cadangan
- **Tanggal:** 2026-11-20
- **Status:** Diterima, menunggu verifikasi (T-003)
- **Konteks:** Pemilik ingin versi online untuk testing di Vercel. `vercel-php` adalah runtime komunitas dan kompatibilitasnya belum diverifikasi.
- **Keputusan:** Deploy ke Vercel (region `sin1`) dengan Neon Postgres. Session dan cache di database, queue `sync`, log ke stderr. Jika T-003 gagal, hentikan dan putuskan hosting pengganti bersama pemilik.
- **Alternatif:** Railway/Fly.io (Docker) atau VPS. Tidak dipilih untuk saat ini.
- **Konsekuensi:** Ada risiko runtime tidak dirawat atau tidak mendukung versi PHP yang dibutuhkan. Migrasi dijalankan manual.

## ADR-010 — Versi PHP ≥ 8.2 dan Laravel ≥ 11
- **Tanggal:** 2026-11-20
- **Status:** Diterima, menunggu verifikasi (T-003)
- **Konteks:** Dibutuhkan versi yang masih didukung dan kompatibel dengan runtime Vercel.
- **Keputusan:** PHP ≥ 8.2 dan Laravel ≥ 11. Versi pastinya ditetapkan setelah T-003.
- **Alternatif:** PHP 8.1 dan Laravel 10.
- **Konsekuensi:** Jika runtime hanya mendukung versi lebih rendah, ADR ini perlu diganti.

## ADR-011 — Respons tanpa skor dan rate limiting
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Endpoint cek wajah bisa dipakai untuk menebak apakah wajah seseorang terdaftar.
- **Keputusan:** Respons hanya `registered: true|false`. Skor dan jarak tidak pernah dikirim. Rate limit per IP: sesi 20, enroll 5, identify 10, hapus 5 per menit `[ASUMSI]`.
- **Alternatif:** Mengembalikan skor kepercayaan. Ditolak karena membantu serangan tebakan.
- **Konsekuensi:** Penyetelan threshold hanya lewat uji internal (T-034). Rate limit per IP bisa melewati NAT bersama, nilainya perlu disesuaikan.

## ADR-012 — Model wajah dari CDN dengan versi di-pin
- **Tanggal:** 2026-11-20
- **Status:** Diterima, menunggu verifikasi (T-004)
- **Konteks:** File model besar akan membebani bundle dan waktu eksekusi fungsi PHP di Vercel.
- **Keputusan:** Model dimuat dari CDN dengan versi di-pin lewat `FACE_MODEL_URL`. Tidak melewati fungsi PHP.
- **Alternatif:** Self-host di `public/` (butuh route Vercel dan menambah ukuran deploy).
- **Konsekuensi:** CDN melihat IP pengguna. CSP harus mengizinkan domain CDN. `TODO:` putuskan self-host untuk penggunaan di luar demo.

## ADR-013 — UI Bahasa Indonesia, Blade dan JavaScript tanpa framework
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Pemilik memilih UI Indonesia saja dan desain sederhana yang mudah dipahami.
- **Keputusan:** Halaman Blade dengan ES modules JavaScript. Teks di `lang/id/face.php`. Tanpa framework SPA, Livewire, atau Vue.
- **Alternatif:** Livewire atau Vue. Ditolak untuk v1 karena menambah kompleksitas.
- **Konsekuensi:** Mudah dimodifikasi. Tidak ada dukungan multi-bahasa di v1.

## ADR-014 — Tanpa retensi otomatis
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Pemilik memutuskan tidak ada penghapusan otomatis. Pengguna sendiri yang memilih menghapus.
- **Keputusan:** Tidak ada perintah atau job penghapusan berdasarkan umur data. Hak hapus lewat F-07 dan F-08.
- **Alternatif:** `retention_days` yang bisa diatur.
- **Konsekuensi:** Data bertahan sampai dihapus pengguna. Pemilik perlu menilai kesesuaian dengan UU PDP (`TODO:` review hukum di T-038).

## ADR-015 — Lisensi MIT
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Repo open source di GitHub dan dimaksudkan untuk di-clone.
- **Keputusan:** Lisensi MIT.
- **Alternatif:** Apache 2.0 (ada patent grant) atau AGPL-3.0.
- **Konsekuensi:** Lisensi model wajah juga harus kompatibel (`TODO:` verifikasi di T-004).

## ADR-016 — Risiko yang diterima untuk fase testing
- **Tanggal:** 2026-11-20
- **Status:** Diterima
- **Konteks:** Aplikasi dipakai untuk menguji fitur face recognition yang akan dipakai proyek lain. Pemilik memilih desain paling sederhana.
- **Keputusan:** Tidak ada perlindungan tambahan terhadap (a) penghapusan data oleh orang lain lewat pindai wajah (F-08), dan (b) dua enroll wajah yang sama bersamaan. Satu threshold untuk enroll, cek, dan hapus. Jika data seseorang terhapus, ia cukup mendaftar ulang.
- **Alternatif:** Threshold hapus lebih ketat (`FACE_DELETE_THRESHOLD`), flag config untuk mematikan F-08, atau verifikasi tambahan.
- **Konsekuensi:** Tidak cocok untuk data nyata. Risiko ditulis di README. Ditinjau ulang bila kode ini dipakai sebagai dasar HRIS (ADR-002).
