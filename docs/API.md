# API

Endpoint JSON dipakai oleh JavaScript di halaman aplikasi ini sendiri (satu aplikasi, same-origin). Backend wajib ada karena validasi liveness dan pencocokan harus di server.

## Konvensi
- **Base URL:** `{APP_URL}/api/face`
- **Versioning:** tidak ada di v1 `[ASUMSI]` (API internal). Jika kelak dibuka untuk integrasi lain, pindah ke `/api/v1/face`.
- **Autentikasi:** tidak ada. Perlindungan lewat token sesi liveness sekali pakai dan rate limit per IP.
- **Header:** request `Accept: application/json` dan `Content-Type: application/json`. Semua respons `Cache-Control: no-store`.
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
- **Rate limit** per IP per menit `[ASUMSI]`: sesi 20, enroll 5, identify 10, hapus 5 (bucket gabungan untuk dua endpoint hapus). Melebihi batas: HTTP 429 `rate_limited` dengan header `Retry-After`.

## Kode error
| Kode | HTTP | Arti | Fitur |
|---|---|---|---|
| `validation_failed` | 422 | Format input salah (contoh: dimensi embedding tidak sesuai, `consent_version` berbeda dari config) | semua |
| `consent_required` | 422 | `consent` tidak `true` | F-09 |
| `session_invalid` | 422 | Token tidak ditemukan, kedaluwarsa, sudah dipakai, atau tujuan tidak cocok | F-01 |
| `liveness_failed` | 422 | Telemetri tidak lolos validasi | F-03 |
| `face_already_registered` | 409 | Wajah sudah terdaftar saat enroll | F-05 |
| `delete_code_invalid` | 404 | Kode hapus tidak cocok | F-07 |
| `face_not_found` | 404 | Tidak ada template yang cocok saat hapus via wajah | F-08 |
| `capacity_reached` | 503 | Jumlah template mencapai batas (`FACE_MAX_TEMPLATES`) | F-13 |
| `rate_limited` | 429 | Melebihi batas permintaan | F-11 |
| `server_error` | 500 | Kesalahan internal tanpa detail (termasuk konfigurasi wajib yang kosong) | semua |

## Objek `liveness` (telemetri)
Dikirim bersama embedding di `enroll`, `identify`, dan `delete/face`.
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
`purpose`: `enroll` | `identify` | `delete`.

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
Mendaftarkan wajah. **Fitur:** F-02, F-03, F-05, F-07, F-09, F-10. **Auth:** token sesi (`purpose=enroll`). **Rate limit:** 5/menit.

Request:
```json
{
  "embedding": [0.0123, -0.0456, "... sebanyak FACE_EMBEDDING_DIM float"],
  "consent": true,
  "consent_version": "1.0",
  "liveness": { "session_token": "...", "challenges": [ "..." ] }
}
```
Respons 201 (kode hapus hanya muncul di sini sekali):
```json
{ "data": { "delete_code": "H7K2M9QXW4PZ3N8RT5VB6YDCFA" } }
```
Urutan pemeriksaan: rate limit → `validation_failed` (termasuk `consent_version` berbeda dari `FACE_CONSENT_VERSION`) → `consent_required` → `session_invalid` (sesi dikonsumsi) → `liveness_failed` → `capacity_reached` → `face_already_registered` → simpan.
Error: 409 `face_already_registered`, 422 `validation_failed` / `consent_required` / `session_invalid` / `liveness_failed`, 503 `capacity_reached`, 429 `rate_limited`.

### POST `/api/face/identify`
Mengecek apakah wajah sudah terdaftar. **Fitur:** F-06, F-03. **Auth:** token sesi (`purpose=identify`). **Rate limit:** 10/menit.

Request:
```json
{
  "embedding": [0.0123, "..."],
  "liveness": { "session_token": "...", "challenges": [ "..." ] }
}
```
Respons 200:
```json
{ "data": { "registered": true } }
```
Respons tidak memuat skor, jarak, atau identitas. Error: 422 `validation_failed` / `session_invalid` / `liveness_failed`, 429 `rate_limited`.

### POST `/api/face/delete/code`
Menghapus template dengan kode hapus. **Fitur:** F-07. **Auth:** kepemilikan kode (tanpa sesi liveness). **Rate limit:** 5/menit (bucket hapus).

Request:
```json
{ "delete_code": "H7K2M9QXW4PZ3N8RT5VB6YDCFA" }
```
Respons 200:
```json
{ "data": { "deleted": true } }
```
Error: 404 `delete_code_invalid`, 422 `validation_failed`, 429 `rate_limited`.

### POST `/api/face/delete/face`
Menghapus template dengan scan wajah. **Fitur:** F-08, F-03. **Auth:** token sesi (`purpose=delete`). **Rate limit:** 5/menit (bucket hapus).

Request:
```json
{
  "embedding": [0.0123, "..."],
  "liveness": { "session_token": "...", "challenges": [ "..." ] }
}
```
Respons 200:
```json
{ "data": { "deleted": true } }
```
Hanya satu template (similarity tertinggi di atas threshold) yang dihapus.
Error: 404 `face_not_found`, 422 `validation_failed` / `session_invalid` / `liveness_failed`, 429 `rate_limited`.

## Route halaman (bukan JSON)
| Method | Path | Isi | Fitur |
|---|---|---|---|
| GET | `/` | Beranda dan penjelasan singkat | F-12 |
| GET | `/enroll` | Consent dan pendaftaran wajah | F-02, F-09, F-12 |
| GET | `/check` | Cek wajah terdaftar | F-06, F-12 |
| GET | `/delete` | Hapus via kode atau scan wajah | F-07, F-08, F-12 |
| GET | `/up` | Health check bawaan Laravel | F-14 |

Konfigurasi untuk JavaScript (dimensi embedding, ambang EAR dan yaw, timeout tantangan, `FACE_MODEL_URL`) dikirim lewat atribut `data-*` di halaman Blade, bukan lewat endpoint JSON. Threshold pencocokan **tidak pernah** dikirim ke klien.
