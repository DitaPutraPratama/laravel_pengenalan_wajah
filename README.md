# Face Registry

Aplikasi Laravel untuk mendaftarkan wajah secara anonim, mengecek apakah wajah sudah terdaftar, dan menghapus data wajah. Pemindaian (kedip, toleh kanan, toleh kiri) berjalan di browser. Server hanya menerima embedding (deretan angka), menyimpannya terenkripsi, dan mencocokkannya di PHP. Tidak ada foto yang dikirim atau disimpan, dan tidak butuh Python.

Ini bukan package. Clone, ubah sesuai kebutuhan, lalu deploy.

## Prasyarat
- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_sqlite` (lokal) atau `pdo_pgsql` (Postgres)
- Composer 2
- Node.js 20.19+ atau 22.12+, dan npm
- Browser dengan kamera. Kamera hanya jalan di `localhost` atau HTTPS.
- Koneksi internet, karena model wajah dimuat dari CDN jsDelivr

## Menjalankan di lokal
```bash
git clone <url-repo> face-registry
cd face-registry
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
composer dev
```
Buka http://localhost:8000. `composer dev` menjalankan server Laravel, Vite, queue, dan log sekaligus.

Halaman yang tersedia:
- `/`: beranda
- `/enroll`: daftar wajah
- `/check`: cek wajah
- `/delete`: hapus data dengan kode hapus

Buka lewat `localhost`, bukan lewat IP LAN. Kalau dibuka lewat IP LAN, browser memblokir kamera karena koneksinya bukan HTTPS.

Kalau muncul error "No application encryption key" padahal `.env` sudah benar, cek apakah ada file `.env.local`. `vercel link` dan `vercel env pull` membuat file itu, dan Laravel memuatnya menggantikan `.env` saat `APP_ENV=local`. Hapus file itu, lalu jalankan ulang `composer dev`.

## Cara kerja
1. Browser meminta sesi ke `POST /api/face/sessions`. Server membuat token sekali pakai dan urutan tiga gerakan yang diacak.
2. Browser memuat [Human](https://github.com/vladmandic/human), memastikan wajah ada di lingkaran, lalu meminta pengguna melakukan gerakan sesuai urutan dari server.
3. Browser mengirim embedding (1024 angka) dan telemetri gerakan ke `/api/face/enroll` atau `/api/face/identify`.
4. Server memeriksa token, kewajaran telemetri, lalu mencocokkan embedding dengan semua template di database (cosine similarity).
5. Saat enroll berhasil, server memberi kode hapus satu kali. Database hanya menyimpan hash kode itu.

## File penting
| File | Fungsi |
|---|---|
| `config/face.php` | Semua pengaturan: threshold, dimensi embedding, URL model, ambang kedip dan toleh, batas template, rate limit |
| `routes/api.php` | Empat endpoint JSON: `sessions`, `enroll`, `identify`, `delete/code` |
| `routes/web.php` | Empat halaman |
| `app/Services/Face/FaceTemplateService.php` | Alur enroll, identify, dan hapus, dengan urutan pemeriksaan sesuai `docs/API.md` |
| `app/Services/Face/FaceMatcher.php` | Pencocokan 1:N. Skor tidak pernah keluar dari class ini |
| `app/Services/Face/EmbeddingCodec.php` | Normalisasi, pengemasan float32, enkripsi embedding |
| `app/Services/Face/LivenessSessionService.php` | Membuat dan memakai sesi sekali pakai |
| `app/Services/Face/LivenessValidator.php` | Memeriksa telemetri gerakan dari browser |
| `app/Services/Face/DeleteCodeService.php` | Membuat kode hapus dan hash-nya |
| `app/Http/Middleware/SecurityHeaders.php` | CSP, izin kamera, `no-store` di API |
| `app/Http/Middleware/EnsureFaceConfigured.php` | Menolak request API kalau variabel `FACE_*` wajib masih kosong |
| `bootstrap/app.php` | Mengubah semua error `/api/*` ke format JSON standar |
| `resources/js/face/scanner.js` | Kamera, Human, dan urutan pemindaian |
| `resources/js/face/liveness.js` | Deteksi kedip dan toleh |
| `resources/js/face/geometry.js` | Hitungan EAR (mata), arah toleh, dan posisi wajah di lingkaran |
| `resources/js/face/main.js` | Logika tiap halaman dan toggle tema |
| `resources/views/face/` | Halaman Blade |
| `lang/id/face.php` | Semua teks UI |
| `resources/css/app.css` | Gaya dan token warna |
| `vercel.json`, `api/index.php` | Konfigurasi deploy Vercel |

## Konfigurasi
Semua lewat `.env`, dibaca oleh `config/face.php`. Empat variabel ini wajib diisi:

| Variabel | Isi |
|---|---|
| `FACE_MATCH_THRESHOLD` | Batas similarity untuk dianggap wajah yang sama. Nilai `0.9` masih sementara, lihat Kalibrasi |
| `FACE_EMBEDDING_DIM` | `1024` untuk model `faceres` dari Human |
| `FACE_MODEL_VERSION` | Label versi model. Template dengan versi lain tidak ikut dicocokkan |
| `FACE_MODEL_URL` | Folder model di CDN (versi di-pin) |

Variabel lain yang punya nilai default di `config/face.php`:
- `FACE_EAR_CLOSED`: ambang kedip
- `FACE_YAW_PEAK`: sudut toleh
- `FACE_CIRCLE_MIN_RATIO`: ukuran wajah minimal di lingkaran
- `FACE_RATE_*`: rate limit per menit
- `FACE_MAX_TEMPLATES`: batas jumlah wajah terdaftar

## Kalibrasi threshold
Nilai threshold harus disetel dengan wajah sungguhan sebelum dipakai:
1. Set `FACE_LOG_SIMILARITY=true` di `.env` lokal.
2. Daftarkan satu wajah, lalu cek wajah yang sama dan wajah orang lain.
3. Lihat `best_similarity` di `storage/logs/laravel.log`, lalu pilih threshold di antara skor wajah yang sama dan wajah berbeda.
4. Kembalikan ke `false`. Skor hanya ditulis ke log, tidak pernah dikirim ke browser.

## Test dan lint
```bash
composer test     # Pest (PHP)
npm test          # Vitest (JS)
composer lint     # Pint
npm run lint      # ESLint
```

## Deploy ke Vercel
1. Push ke GitHub, lalu impor repo di Vercel. `vercel.json` sudah mengatur runtime `vercel-php@0.9.0` (PHP 8.5) dan region `sin1`.
2. Buat database Postgres (misalnya Neon).
3. Isi env di Vercel:
   - `APP_KEY` (buat dengan `php artisan key:generate --show`)
   - `APP_URL`
   - `DB_CONNECTION=pgsql` dan `DB_URL`
   - semua variabel `FACE_*`
4. Jalankan migrasi dari komputer Anda:
   ```bash
   DB_CONNECTION=pgsql DB_URL="<connection-string>" php artisan migrate --force
   ```
5. Saat ini `vercel.json` memakai session cookie dan cache di memori, supaya halaman bisa tampil tanpa database. Setelah database tersedia, ganti `SESSION_DRIVER` dan `CACHE_STORE` di `vercel.json` ke `database` (ADR-009).

## Batasan
- Pemeriksaan gerakan ini buatan sendiri dan tidak setara SDK liveness komersial. Server hanya memeriksa kewajaran data dari browser, jadi foto atau video yang dibuat dengan cermat masih bisa lolos. Jangan pakai untuk keperluan finansial atau berisiko tinggi tanpa penguatan.
- Pencocokan membaca semua template di PHP, cocok untuk beberapa ribu wajah. Batas demo 2.000.
- Dua pendaftaran wajah yang sama pada waktu bersamaan bisa sama-sama lolos (ADR-016).
- Mengganti `APP_KEY` membuat semua embedding tersimpan tidak bisa dibaca lagi.
- Belum dibuat: efek cahaya merah/biru/hijau (F-04) dan hapus lewat pindai wajah (F-08).

## Privasi
Data wajah termasuk data pribadi sensitif menurut UU PDP. Proyek ini hanya menyimpan embedding terenkripsi, meminta persetujuan, dan menyediakan penghapusan. Teks persetujuan masih draf dan belum direview hukum (T-038). Kepatuhan hukum menjadi tanggung jawab Anda jika memakai data nyata.

## Dokumentasi
- `docs/PRD.md`: fitur dan acceptance criteria
- `docs/ARCHITECTURE.md`: arsitektur dan deployment
- `docs/API.md`: endpoint dan format error
- `docs/DATABASE.md`: skema
- `docs/DESIGN.md`: layar, teks, token desain
- `docs/DECISIONS.md`: alasan keputusan teknis (ADR)

## Kredit
Proyek ini dibangun di atas karya berikut:

| Library | Dipakai untuk | Lisensi |
|---|---|---|
| [Human](https://github.com/vladmandic/human) oleh Vladimir Mandic | Deteksi wajah, mesh, iris, sudut kepala, dan embedding di browser | MIT |
| [TensorFlow.js](https://github.com/tensorflow/tfjs) 4.22, dibundel di dalam Human | Menjalankan model di browser (WebGL/WebGPU) | Apache-2.0 |
| Model wajah Human (`blazeface`, `facemesh`, `iris`, `faceres`) | Model yang dimuat dari CDN | Lihat [daftar model dan kredit](https://github.com/vladmandic/human/wiki/Models). Lisensi tiap model belum diverifikasi di proyek ini (T-004) |
| [jsDelivr](https://www.jsdelivr.com) | CDN untuk file model | |
| [Laravel](https://laravel.com) | Framework backend | MIT |
| [Vite](https://vite.dev) dan [laravel-vite-plugin](https://github.com/laravel/vite-plugin) | Build aset frontend | MIT |
| [Pest](https://pestphp.com) dan [PHPUnit](https://phpunit.de) | Test PHP | MIT, BSD-3-Clause |
| [Vitest](https://vitest.dev) | Test JS | MIT |
| [Laravel Pint](https://github.com/laravel/pint) | Format kode PHP | MIT |
| [ESLint](https://eslint.org) | Lint JS | MIT |
| [vercel-php](https://github.com/vercel-community/php) | Runtime PHP di Vercel | MIT |

## Lisensi
MIT, lihat `LICENSE`.
