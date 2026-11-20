# API

Endpoint JSON dipakai oleh JavaScript di halaman aplikasi ini sendiri (satu aplikasi, same-origin). Backend wajib ada karena validasi liveness dan pencocokan harus di server.

## Konvensi
- **Base URL:** `{APP_URL}/api/face`
- **Versioning:** tidak ada di v1 `[ASUMSI]` (API internal).
- **Autentikasi:** endpoint JSON tidak butuh login. Perlindungan lewat token sesi liveness sekali pakai, rate limit per IP, dan CSRF. Endpoint ada di grup `web` Laravel (ADR-020), jadi request wajib membawa header `X-CSRF-TOKEN` dari `<meta name="csrf-token">` dan cookie sesi. `login` dan `enroll` yang berhasil membuat sesi masuk.
- **Header:** request `Accept: application/json`, `Content-Type: application/json`, dan `X-CSRF-TOKEN`. Semua respons `Cache-Control: no-store`.
- **Respons sukses:** `{ "data": { ... } }`
- **Respons error (standar):**
```json
{
  "error": {
    "code": "liveness_failed",
    "message": "Teks umum untuk developer (bukan untuk ditampilkan ke pengguna).",
    "details": {}
  }
}
```
- `details` hanya dipakai untuk `validation_failed` (daftar field) dan `rate_limited` (`retry_after`). Untuk error lain `details` kosong, tidak membocorkan alasan internal.
- **Pagination:** tidak ada. Sengaja tidak ada endpoint yang membuat daftar template.
- **Rate limit** per IP per menit `[ASUMSI]`: sesi 20, enroll 5, login 10. Melebihi batas: HTTP 429 `rate_limited` dengan header `Retry-After`.

## Kode error
| Kode | HTTP | Arti | Fitur |
|---|---|---|---|
| `validation_failed` | 422 | Format input salah (contoh: dimensi embedding tidak sesuai, `consent_version` berbeda dari config) | semua |
| `consent_required` | 422 | `consent` tidak `true` | F-09 |
| `session_invalid` | 422 | Token tidak ditemukan, kedaluwarsa, sudah dipakai, atau tujuan tidak cocok. Juga untuk token CSRF kedaluwarsa (halaman perlu dimuat ulang) | F-01 |
| `liveness_failed` | 422 | Telemetri tidak lolos validasi | F-03 |
| `face_already_registered` | 409 | Wajah sudah terdaftar saat enroll | F-05 |
| `face_not_found` | 404 | Tidak ada akun yang wajahnya cocok saat masuk | F-18 |
| `capacity_reached` | 503 | Jumlah template mencapai batas (`FACE_MAX_TEMPLATES`) | F-13 |
| `rate_limited` | 429 | Melebihi batas permintaan | F-11 |
| `server_error` | 500 | Kesalahan internal tanpa detail (termasuk konfigurasi wajib yang kosong) | semua |

## Objek `liveness` (telemetri)
Dikirim bersama embedding di `enroll` dan `login`.
```json
{
  "session_token": "string, token dari POST /sessions",
  "challenges": [
    { "type": "turn_left",  "duration_ms": 2300, "frames": 55, "peak_yaw": -27.4, "end_yaw": -3.1 },
    { "type": "blink",      "duration_ms": 1850, "frames": 42, "min_ear": 0.12, "max_ear": 0.34 },
    { "type": "turn_right", "duration_ms": 2100, "frames": 50, "peak_yaw": 24.8, "end_yaw": 2.0 }
  ]
}
```
- Konvensi yaw `[ASUMSI]`: positif = toleh ke kanan pengguna, negatif = ke kiri.
- Aturan validasi server (F-03), semua ambang dari `config/face.php`: urutan dan jumlah tantangan sama dengan sesi; `duration_ms` 300–15000; `frames` ≥ 5; blink: `min_ear < 0,2` dan `max_ear > 0,25`; toleh: `|peak_yaw| ≥ 20` dengan arah benar dan `|end_yaw| ≤ 10`; total `duration_ms` tidak boleh melebihi waktu server sejak sesi dibuat (toleransi 2 detik).
- Sesi dianggap terpakai pada permintaan pertama yang membawa tokennya, berhasil atau tidak. Klien harus membuat sesi baru untuk mencoba lagi.

## Endpoint

### POST `/api/face/sessions`
Membuat sesi liveness. **Fitur:** F-01. **Auth:** tidak ada. **Rate limit:** 20/menit.

Request:
```json
{ "purpose": "enroll" }
```
`purpose`: `enroll` | `login`.

Respons 201:
```json
{
  "data": {
    "session_token": "k3Xr...token-acak-minimal-128-bit",
    "challenges": ["turn_left", "blink", "turn_right"],
    "expires_in": 120,
    "challenge_timeout": 15
  }
}
```
Error: 422 `validation_failed`, 429 `rate_limited`.

### POST `/api/face/enroll`
Mendaftarkan wajah sebagai akun baru lalu langsung masuk. **Fitur:** F-02, F-03, F-05, F-09, F-10, F-18. **Auth:** token sesi (`purpose=enroll`). **Rate limit:** 5/menit.

Request:
```json
{
  "embedding": [0.0123, -0.0456, "... sebanyak FACE_EMBEDDING_DIM float"],
  "consent": true,
  "consent_version": "1.0",
  "liveness": { "session_token": "...", "challenges": [ "..." ] }
}
```
Respons 201 (sesi masuk dibuat, ID sesi diganti):
```json
{ "data": { "redirect": "https://contoh.test/dashboard" } }
```
Urutan pemeriksaan: rate limit → `validation_failed` (termasuk `consent_version` berbeda dari `FACE_CONSENT_VERSION`) → `consent_required` → `session_invalid` (sesi dikonsumsi) → `liveness_failed` → `capacity_reached` → `face_already_registered` → simpan → masuk.
Error: 409 `face_already_registered`, 422 `validation_failed` / `consent_required` / `session_invalid` / `liveness_failed`, 503 `capacity_reached`, 429 `rate_limited`.

### POST `/api/face/login`
Masuk dengan wajah. **Fitur:** F-18, F-03. **Auth:** token sesi (`purpose=login`). **Rate limit:** 10/menit.

Request:
```json
{
  "embedding": [0.0123, "..."],
  "liveness": { "session_token": "...", "challenges": [ "..." ] }
}
```
Respons 200 (sesi masuk sebagai akun dengan similarity tertinggi di atas threshold):
```json
{ "data": { "redirect": "https://contoh.test/dashboard" } }
```
Respons tidak memuat skor, jarak, atau identitas. Error: 404 `face_not_found`, 422 `validation_failed` / `session_invalid` / `liveness_failed`, 429 `rate_limited`.

## Route halaman dan form (bukan JSON)
Form memakai `@csrf`. Rute `guest` mengalihkan akun yang sudah masuk ke `/dashboard`. Rute `auth` mengalihkan tamu ke `/`.

| Method | Path | Akses | Isi | Fitur |
|---|---|---|---|---|
| GET | `/` | guest | Masuk dengan pindai wajah | F-18 |
| GET | `/enroll` | guest | Consent dan pendaftaran wajah | F-02, F-09 |
| GET | `/dashboard` | auth | Nama, tombol absen, riwayat 30 hari terakhir, hapus data | F-19, F-20, F-21 |
| PUT | `/account/name` | auth | Simpan nama (`name` wajib, maks 100) | F-19 |
| POST | `/attendance` | auth | Absen hari ini. Kedua kalinya di hari yang sama tidak mengubah apa pun. Ditolak diam-diam jika nama belum diisi | F-20 |
| DELETE | `/attendance` | auth | Hapus semua riwayat absen akun ini | F-21 |
| DELETE | `/account` | auth | Hapus akun (template, nama, semua absen), lalu keluar | F-21 |
| POST | `/logout` | auth | Keluar | F-18 |
| GET | `/up` | — | Health check bawaan Laravel | F-14 |

Konfigurasi untuk JavaScript (dimensi embedding, ambang EAR dan yaw, timeout tantangan, `FACE_MODEL_URL`) dikirim lewat atribut `data-*` di halaman Blade, bukan lewat endpoint JSON. Threshold pencocokan **tidak pernah** dikirim ke klien.
