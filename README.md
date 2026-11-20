# Face Registry

Aplikasi web Laravel untuk mendaftarkan wajah secara anonim dengan pemeriksaan gerakan (kedip, toleh kanan, toleh kiri), mengecek apakah sebuah wajah sudah terdaftar, dan menghapus data wajah. Tanpa Python: backend PHP (Laravel), frontend JavaScript. Hanya pola wajah dalam bentuk angka (embedding) yang disimpan, tidak ada foto.

Repo ini bukan package. Cara pakainya: clone, modifikasi sesuai kebutuhan, lalu deploy.

> **Status:** dalam pengembangan. Perintah di bawah adalah rencana dan difinalkan di tugas T-005 (`docs/TASKS.md`).

## Fitur
- Pendaftaran wajah dengan wajah di dalam lingkaran, efek cahaya merah/biru/hijau, dan tantangan gerakan dalam urutan acak.
- Cek wajah terdaftar (pencocokan 1:N) dan penolakan pendaftaran ganda.
- Hapus data lewat kode hapus (ditampilkan sekali) atau pindai wajah.
- Embedding dienkripsi di database, ada rate limiting, dan persetujuan eksplisit sebelum pendaftaran.

## Prasyarat
- PHP ≥ 8.2 dan Composer
- Node.js dan npm
- Browser dengan kamera. Kamera berjalan di HTTPS atau `localhost`.
- Untuk demo online: akun Vercel dan database Neon Postgres.

## Instalasi
```bash
git clone <URL-repo-anda> face-registry
cd face-registry
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```
Lengkapi nilai `TODO:` di `.env` (`FACE_EMBEDDING_DIM`, `FACE_MODEL_VERSION`, `FACE_MODEL_URL`, `FACE_MATCH_THRESHOLD`). Nilai awalnya dicatat di `docs/DECISIONS.md` setelah spike T-004 dan T-034.

## Menjalankan
```bash
composer dev      # server Laravel dan Vite
```
Buka `http://localhost:8000`. Halaman: `/` (beranda), `/enroll`, `/check`, `/delete`.

## Test dan lint
```bash
composer test     # Pest
npm test          # Vitest
composer lint     # Pint
npm run lint      # ESLint
```

## Deploy ke Vercel (demo)
1. Push repo ke GitHub dan impor ke Vercel. Runtime: `vercel-php@0.9.0`, region `sin1` (lihat `vercel.json`).
2. Buat database di Neon dan isi variabel `DB_*` dan variabel lain dari `.env.example` lewat dashboard Vercel. Gunakan `APP_ENV=production` dan `APP_DEBUG=false`.
3. Jalankan migrasi dari komputer Anda ke database Neon:
   ```bash
   php artisan migrate --force
   ```
4. Buka URL Vercel dan uji pendaftaran, cek, dan hapus.

Detail dan keterbatasan serverless ada di `docs/ARCHITECTURE.md`. Kompatibilitas `vercel-php` diverifikasi di tugas T-003.

## Batasan keamanan (baca sebelum memakai)
- Pemeriksaan gerakan (liveness) di proyek ini adalah buatan sendiri dan **tidak setara dengan SDK liveness komersial**. Validasi server hanya memeriksa kewajaran data dari browser, sehingga serangan dengan foto atau video yang canggih masih mungkin lolos.
- Efek cahaya merah/biru/hijau hanya tampilan. Tidak ada analisis pantulan cahaya.
- **Tidak cocok untuk keperluan finansial atau risiko tinggi** tanpa penguatan tambahan.
- Pencocokan dilakukan di PHP dengan membaca semua template, sehingga cocok untuk beberapa ribu wajah. Demo dibatasi 2.000.
- Siapa pun yang wajahnya cocok bisa menghapus data wajah itu lewat pindai wajah, dan dua pendaftaran wajah yang sama pada waktu bersamaan bisa sama-sama lolos. Ini sengaja dibiarkan karena aplikasi dipakai untuk menguji fitur. Jika data Anda dihapus orang lain, cukup daftar ulang.
- `TODO:` hasil uji spoofing dan angka FAR/FRR dicantumkan di sini setelah T-034.

## Privasi dan data biometrik
Data wajah termasuk data pribadi sensitif (UU PDP Indonesia). Proyek ini hanya menyimpan embedding terenkripsi, meminta persetujuan eksplisit, dan menyediakan penghapusan oleh pemilik data. Tidak ada penghapusan otomatis berdasarkan umur data. Anda bertanggung jawab atas kepatuhan hukum jika memakainya untuk data nyata.
`TODO:` teks persetujuan dan kebijakan privasi final (T-038).

## Menyesuaikan untuk absensi (HRIS)
`TODO:` panduan akan ditulis di T-037. Singkatnya, absensi membutuhkan identitas, verifikasi 1:1, dan liveness yang lebih kuat dari versi ini.

## Dokumentasi
| Dokumen | Isi |
|---|---|
| `CLAUDE.md` | Panduan kerja untuk Claude Code |
| `docs/PRD.md` | Produk, fitur, dan acceptance criteria |
| `docs/ARCHITECTURE.md` | Arsitektur, alur data, deployment |
| `docs/DATABASE.md` | Skema dan aturan migrasi |
| `docs/API.md` | Endpoint JSON dan format error |
| `docs/DESIGN.md` | Desain layar dan teks UI |
| `docs/TASKS.md` | Daftar tugas pengembangan |
| `docs/PROGRESS.md` | Status dan log sesi |
| `docs/DECISIONS.md` | Catatan keputusan (ADR) |

## Lisensi
MIT. Pastikan lisensi model wajah yang dipakai juga kompatibel (`TODO:` verifikasi di T-004).
