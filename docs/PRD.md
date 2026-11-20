# PRD — Face Registry (nama kerja)

Status: Draft v0.2 · Tanggal: 2026-11-20

## 1. Ringkasan produk
Aplikasi web Laravel untuk mendaftarkan wajah secara anonim dengan tantangan liveness, mengecek apakah sebuah wajah sudah terdaftar, dan menghapus data wajah oleh pemiliknya. Pengalaman pengguna meniru pendaftaran wajah di dompet digital: wajah masuk ke lingkaran, ada cahaya merah/biru/hijau, lalu diminta kedip, toleh kanan, dan toleh kiri. Repo ditujukan untuk di-clone dan dimodifikasi pemiliknya (bukan package), dengan demo online di Vercel.

## 2. Latar belakang dan masalah
- Pemilik proyek sedang membangun HRIS dan memperkirakan butuh face recognition untuk absensi, tetapi fitur HRIS belum jelas. `[ASUMSI]` Proyek ini dipakai sebagai fondasi dan tempat bereksperimen sebelum fitur absensi dibangun.
- Masalah yang diselesaikan di v1: mencegah satu orang mendaftar berkali-kali, tanpa menyimpan identitas atau foto.
- Tujuan saat ini: alat untuk menguji fitur face recognition yang akan dipakai proyek lain. Kesederhanaan lebih diutamakan daripada ketahanan terhadap penyalahgunaan (lihat ADR-016).
- `TODO:` riset singkat solusi sejenis (SDK komersial, repo open source) untuk perbandingan.

## 3. Tujuan dan metrik keberhasilan
| ID | Metrik | Target | Catatan |
|---|---|---|---|
| M-1 | Waktu enroll sampai selesai (median) | ≤ 45 detik | `[ASUMSI]` laptop, webcam 720p |
| M-2 | False accept / false reject | ≤ 1% / ≤ 5% | `[ASUMSI]` `TODO:` dataset dan prosedur uji |
| M-3 | Latensi identify p95 dengan 2.000 template | ≤ 1,5 detik | `[ASUMSI]` diukur di Vercel |
| M-4 | Foto/frame wajah tersimpan | 0 | Diperiksa lewat review DB dan storage |
| M-5 | Waktu dari clone sampai enroll berjalan di lokal | ≤ 20 menit | `[ASUMSI]` mengikuti README |
| M-6 | Coverage test pada `app/Services/Face` | ≥ 80% | `[ASUMSI]` |

## 4. Target pengguna dan persona
- **Raka, pemilik/developer proyek.** Meng-clone repo, memodifikasinya untuk kebutuhan HRIS, men-deploy ke Vercel untuk uji online. Butuh kode sederhana dan mudah diubah.
- **Sari, pengguna akhir.** Mendaftarkan wajah lewat HP atau laptop, ingin cepat dan tahu datanya bisa dihapus.
- **Dimas, penguji demo.** Mencoba alur online, termasuk mencoba menipu dengan foto, untuk menilai keamanan.

## 5. Daftar fitur
Prioritas MoSCoW. Semua angka dalam acceptance criteria bertanda `[ASUMSI]` dan bisa diubah lewat config.

### F-01 Sesi liveness di server — Must
**Story:** Sebagai sistem, saya membuat sesi liveness sekali pakai agar hasil enroll/identify/hapus tidak bisa diputar ulang.
- **Given** klien meminta sesi dengan tujuan `enroll`, `identify`, atau `delete`, **When** server memprosesnya, **Then** server menyimpan sesi di database dengan token acak ≥ 128 bit, urutan 3 tantangan (kedip, toleh kanan, toleh kiri) yang teracak, dan kedaluwarsa 120 detik `[ASUMSI]`.
- **Given** token sudah dipakai atau kedaluwarsa, **When** dipakai lagi, **Then** server menolak dengan HTTP 422 dan kode `session_invalid`.
- **Given** sebuah token sudah dibawa dalam satu permintaan (berhasil atau gagal), **When** token dipakai lagi, **Then** server menolak dengan HTTP 422 `session_invalid`. Percobaan ulang butuh sesi baru.

### F-02 Enroll wajah — Must
**Story:** Sebagai pengguna, saya mendaftarkan wajah dengan kamera agar sistem mengenali saya.
- **Given** izin kamera diberikan, **When** wajah berada di dalam lingkaran (lebar wajah 40–70% diameter lingkaran) selama ≥ 1 detik `[ASUMSI]`, **Then** tantangan liveness dimulai.
- **Given** izin kamera ditolak, tidak ada wajah, atau lebih dari satu wajah, **When** halaman memantau kamera, **Then** muncul instruksi yang jelas dan alur tidak berlanjut.
- **Given** semua tantangan lulus, **When** browser mengirim data, **Then** payload hanya berisi embedding, telemetri, dan token sesi, tanpa gambar.

### F-03 Tantangan liveness dan validasi telemetri — Must
**Story:** Sebagai pemilik, saya ingin pemalsuan sederhana (foto, video putar ulang) lebih sulit.
- **Given** tantangan kedip, **When** EAR < 0,2 selama ≥ 2 frame lalu > 0,25 `[ASUMSI]`, **Then** tantangan lulus.
- **Given** tantangan toleh, **When** yaw ≥ 20° ke arah yang diminta lalu kembali ≤ 10° `[ASUMSI]`, **Then** tantangan lulus.
- **Given** telemetri dikirim, **When** server memvalidasi, **Then** server memeriksa urutan sesuai sesi, durasi tiap tantangan 0,3–15 detik, minimal 5 frame per tantangan, dan nilai EAR/yaw masuk akal. Jika gagal: HTTP 422 `liveness_failed`.

### F-04 Efek cahaya merah, biru, hijau — Should
**Story:** Sebagai pengguna, saya ingin pengalaman yang mirip aplikasi dompet digital.
- **Given** pengguna berada di tahap persiapan, **When** halaman menampilkan efek, **Then** area di luar lingkaran berganti merah, biru, hijau berurutan, masing-masing ±0,5 detik dengan transisi halus.
- **Given** perangkat memakai `prefers-reduced-motion`, **When** halaman dimuat, **Then** efek dimatikan.
- Efek hanya visual: tidak ada analisis pantulan dan tidak mempengaruhi lulus/gagal.

### F-05 Anti-duplikat saat enroll — Must
**Story:** Sebagai pemilik, saya ingin satu wajah hanya terdaftar sekali.
- **Given** embedding baru cocok dengan template yang ada (similarity ≥ threshold), **When** enroll, **Then** server menolak dengan HTTP 409 `face_already_registered`, tidak membuat record, dan tidak membocorkan skor.

### F-06 Cek wajah terdaftar (1:N) — Must
**Story:** Sebagai pengguna, saya ingin tahu apakah wajah saya sudah terdaftar.
- **Given** sesi liveness `identify` lulus, **When** embedding dibandingkan dengan semua template, **Then** server membalas `{"registered": true|false}` tanpa skor atau identitas.

### F-07 Hapus data dengan kode hapus — Must
**Story:** Sebagai pengguna, saya ingin menghapus data wajah saya kapan saja.
- **Given** enroll berhasil, **When** respons dikirim, **Then** respons berisi `delete_code` satu kali (≥ 128 bit entropi, mis. 26 karakter base32) dan database hanya menyimpan hash-nya.
- **Given** kode hapus valid, **When** dikirim ke endpoint hapus, **Then** template dihapus permanen (hard delete) dengan HTTP 200.
- **Given** kode tidak valid, **When** dikirim, **Then** HTTP 404 `delete_code_invalid` tanpa petunjuk tambahan.

### F-08 Hapus data dengan scan wajah — Should
**Story:** Sebagai pengguna yang kehilangan kode hapus, saya ingin menghapus data dengan memindai wajah.
- **Given** sesi liveness `delete` lulus dan embedding cocok, **When** diproses, **Then** hanya satu template (yang paling mirip) dihapus.
- **Given** tidak ada yang cocok, **When** diproses, **Then** HTTP 404 `face_not_found`.
- Siapa pun yang wajahnya cocok dapat menghapus template tersebut. Ini diterima untuk tujuan testing (lihat bagian 9).

### F-09 Consent eksplisit — Must
**Story:** Sebagai pengguna, saya ingin tahu dan menyetujui pemakaian data biometrik saya.
- **Given** halaman enroll dibuka, **When** pengguna belum mencentang persetujuan, **Then** tombol mulai nonaktif.
- **Given** enroll dikirim tanpa `consent=true`, **When** server memvalidasi, **Then** HTTP 422 `consent_required`.
- Server menyimpan `consent_version` pada template. `TODO:` isi teks consent final (perlu review UU PDP).
- **Given** `consent_version` yang dikirim berbeda dari `FACE_CONSENT_VERSION`, **When** server memvalidasi, **Then** HTTP 422 `validation_failed`.

### F-10 Enkripsi embedding — Must
**Story:** Sebagai pemilik, saya ingin embedding tidak terbaca jika database bocor.
- **Given** template disimpan, **When** menulis ke database, **Then** kolom embedding berisi hasil `Crypt::encryptString` dan tidak ada vektor plaintext.
- Dekripsi hanya terjadi saat pencocokan, dan hasil dekripsi tidak di-cache antar request.

### F-11 Rate limiting — Must
**Story:** Sebagai pemilik, saya ingin endpoint sulit disalahgunakan untuk menebak wajah.
- **Given** satu IP, **When** melebihi batas per menit (sesi 20, enroll 5, identify 10, hapus 5) `[ASUMSI]`, **Then** HTTP 429 `rate_limited` dengan header `Retry-After`.

### F-12 Halaman web enroll, cek, dan hapus — Must
**Story:** Sebagai pengguna, saya memakai halaman web sederhana berbahasa Indonesia.
- **Given** pengguna membuka `/`, `/enroll`, `/check`, atau `/delete` lewat HTTPS, **When** halaman dimuat, **Then** halaman tampil dengan teks dari `lang/id` dan memiliki state loading, error, dan sukses.
- Semua teks UI berbahasa Indonesia (satu bahasa untuk v1).

### F-13 Konfigurasi lewat env — Must
**Story:** Sebagai pemilik, saya ingin mengubah parameter tanpa mengubah kode.
- **Given** nilai di `.env`, **When** aplikasi berjalan, **Then** threshold, `embedding_dimensions`, timeout sesi, batas template (default demo 2.000), dan rate limit dibaca dari `config/face.php`.
- Nilai default threshold: `TODO:` tentukan lewat uji M-2.
- **Given** jumlah template sudah mencapai `FACE_MAX_TEMPLATES`, **When** enroll, **Then** HTTP 503 `capacity_reached` dan tidak ada record baru.
- **Given** `FACE_MATCH_THRESHOLD`, `FACE_EMBEDDING_DIM`, `FACE_MODEL_VERSION`, atau `FACE_MODEL_URL` kosong, **When** request masuk ke `/api/face/*`, **Then** respons `server_error` dan log menyebut nama variabel yang kosong (tanpa nilai rahasia).

### F-14 Deploy GitHub dan Vercel — Must
**Story:** Sebagai pemilik, saya ingin menguji versi online.
- **Given** push ke branch `main`, **When** Vercel membangun, **Then** aplikasi dapat dibuka lewat HTTPS dan kamera bisa diminta.
- **Given** database Neon sudah dibuat, **When** `php artisan migrate --force` dijalankan manual, **Then** semua tabel terbentuk dan enroll berjalan end-to-end.
- Session dan cache memakai database, queue `sync`, log ke stderr.

### F-15 README clone-and-modify dan batasan keamanan — Must
**Story:** Sebagai developer, saya ingin tahu cara menjalankan dan batas keamanannya.
- **Given** pembaca README, **When** mengikuti langkahnya, **Then** aplikasi berjalan di lokal (M-5) dan README menyatakan bahwa liveness ini tidak setara SDK komersial dan tidak cocok untuk keperluan finansial atau risiko tinggi tanpa penguatan.

### F-16 Kesiapan ekstensi absensi — Could
**Story:** Sebagai pemilik, saya ingin mudah menambah absensi HRIS nanti.
- **Given** migrasi dijalankan, **When** tabel dibuat, **Then** `face_templates` punya kolom nullable `external_ref` dan `face_liveness_sessions` punya kolom `purpose`.
- Tidak ada endpoint v1 yang memakai `external_ref`. Verifikasi 1:1 untuk absensi tidak dibangun di v1.

### F-17 Tolak wajah berkacamata — Must
**Story:** Sebagai pemilik, saya ingin pengguna melepas kacamata agar embedding konsisten antar-pemindaian.
- **Given** wajah di lingkaran memakai kacamata, **When** halaman memeriksa posisi, **Then** muncul "Lepas kacamata" dan tantangan tidak dimulai.
- **Given** kacamata terlihat saat embedding akhir diambil, **When** batas waktu habis, **Then** pemindaian gagal dengan pesan kacamata dan tidak ada data yang dikirim.
- Deteksi berjalan di browser saja (garis horizontal di batang hidung, ambang `FACE_GLASSES_MAX_EDGE`, 12 `[ASUMSI]`, 0 = mati). Server tidak bisa memeriksanya karena tidak menerima gambar, jadi pemeriksaan ini bisa dilewati. Kacamata tanpa bingkai bisa lolos.

## 6. Alur pengguna utama
1. **Enroll:** buka `/enroll` → baca dan setujui consent → izinkan kamera → posisikan wajah di lingkaran → cahaya merah/biru/hijau → ikuti tantangan (urutan acak) → berhasil → kode hapus ditampilkan sekali.
2. **Cek wajah:** buka `/check` → izinkan kamera → posisi dan tantangan → hasil "terdaftar" atau "belum terdaftar".
3. **Hapus:** buka `/delete` → pilih "pakai kode" atau "scan wajah" → konfirmasi → data terhapus.

## 7. Kebutuhan non-fungsional
- **Performa:** lihat M-1 dan M-3. Embedding dihitung di browser dan model dimuat ≤ 10 detik pada koneksi 10 Mbps `[ASUMSI]`.
- **Keamanan:** sesi liveness satu kali pakai di database, hash kode hapus (`hash_equals`), enkripsi embedding (F-10), rate limiting (F-11), tanpa skor di respons. Batasan: validasi liveness bersifat plausibility check, bukan jaminan anti-spoof.
- **Privasi:** hanya embedding disimpan, consent eksplisit, hak hapus (F-07, F-08), embedding dan kode hapus tidak masuk log. Tidak ada retensi otomatis (keputusan pemilik: pengguna yang memilih menghapus). `TODO:` kebijakan privasi final.
- **Aksesibilitas:** teks instruksi tertulis di setiap tantangan, `prefers-reduced-motion` dihormati, kontras warna WCAG AA, bisa dipakai dengan keyboard untuk tombol dan form.
- **Platform:** Chrome, Edge, Firefox, dan Safari versi terbaru (desktop, Android, iOS) dengan kamera depan dan HTTPS `[ASUMSI]`. `TODO:` uji nyata di perangkat low-end.
- **Skala demo:** maksimal 2.000 template (F-13).

## 8. Out of scope (v1)
- Package Composer atau library yang didistribusikan.
- Verifikasi 1:1 dan absensi, login, user_id, panel admin.
- Analisis pantulan cahaya warna.
- Retensi atau penghapusan otomatis.
- Multi-bahasa dan SDK mobile native.
- Vector database atau pgvector.

## 9. Asumsi, risiko, dan pertanyaan terbuka
**Asumsi**
- `[ASUMSI]` Semua angka di acceptance criteria dan metrik adalah nilai awal dan akan disetel lewat pengujian.
- `[ASUMSI]` Demo dipakai untuk eksperimen, bukan data pengguna sungguhan.

**Risiko**
| Risiko | Dampak | Mitigasi |
|---|---|---|
| `vercel-php` adalah runtime komunitas, kompatibilitas versi PHP/Laravel belum diverifikasi | Deploy gagal atau berhenti dirawat | Spike di Tahap 0. Jika gagal, ganti hosting (keputusan dicatat di `DECISIONS.md`) |
| Liveness di browser bisa dipalsukan | Spoofing foto/video | Validasi telemetri server (F-03), tulis batasan di README (F-15) |
| Dekripsi semua template di setiap request | Latensi dan memori tinggi di serverless | Batas 2.000 template (F-13), ukur dengan M-3 |
| Identify bisa dipakai untuk menebak wajah | Kebocoran privasi | Tanpa skor, rate limit (F-11) |
| Lisensi model wajah belum dicek | Konflik lisensi | `TODO:` verifikasi lisensi model `@vladmandic/human` |
| Absensi butuh identitas dan 1:1, sedangkan v1 anonim | Perubahan desain besar nanti | F-16 menyiapkan kolom. Desain absensi dibahas terpisah |
| Data bisa dihapus orang lain lewat pindai wajah (F-08) | Pendaftaran seseorang hilang | **Diterima.** Untuk testing cukup daftar ulang. Ditulis di README (ADR-016) |
| Dua enroll wajah yang sama bersamaan lolos cek duplikat | Template ganda | **Diterima** untuk v1 (vektor tidak bisa diberi unique constraint) (ADR-016) |

**Pertanyaan terbuka**
- `TODO:` Versi PHP dan Laravel final yang cocok dengan `vercel-php@0.9.0`.
- `TODO:` Dimensi embedding final dari `@vladmandic/human` (diverifikasi saat spike).
- `TODO:` Nilai default threshold dan dataset uji M-2.
- `TODO:` Lokasi file model: public di Vercel atau CDN.
- `TODO:` Nama proyek final.
- `TODO:` Teks consent dan kebijakan privasi (perlu review hukum).
