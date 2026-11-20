<?php

declare(strict_types=1);

// All UI text (docs/DESIGN.md). Keys under "js" are passed to the browser as JSON.
return [
    'nav' => [
        'label' => 'Menu utama',
        'enroll' => 'Daftar',
        'check' => 'Cek',
        'delete' => 'Hapus',
    ],

    'theme' => [
        // Toggle button with aria-pressed: pressed = dark theme on.
        'toggle' => 'Tema gelap',
    ],

    'footer' => [
        // TODO: link the final privacy policy after legal review (T-038).
        'privacy' => 'Kami hanya menyimpan pola wajah dalam bentuk angka, bukan foto. Anda bisa menghapusnya kapan saja.',
    ],

    'home' => [
        'title' => 'Pendaftaran wajah',
        'intro' => 'Daftarkan wajah Anda sekali, cek apakah wajah sudah terdaftar, atau hapus data wajah Anda.',
        'how' => 'Pemindaian berjalan di browser Anda. Foto tidak pernah dikirim atau disimpan.',
        'enroll' => 'Daftarkan wajah',
        'enroll_desc' => 'Pindai wajah sekali dengan kamera. Setelah itu Anda mendapat kode hapus.',
        'check' => 'Cek wajah',
        'check_desc' => 'Cari tahu apakah wajah Anda sudah ada di data kami.',
        'delete' => 'Hapus data wajah',
        'delete_desc' => 'Hapus pola wajah Anda dengan kode hapus yang Anda simpan.',
    ],

    'enroll' => [
        'title' => 'Daftarkan wajah',
        'description' => 'Butuh sekitar satu menit: setujui penyimpanan, lalu ikuti tiga gerakan di depan kamera.',
        // TODO: final consent text after legal review (UU PDP, T-038). Bump FACE_CONSENT_VERSION when it changes.
        'consent_text' => 'Kami menyimpan pola wajah Anda dalam bentuk angka, bukan foto. Data ini dipakai untuk mencegah satu wajah terdaftar dua kali. Anda bisa menghapusnya kapan saja dengan kode hapus.',
        'consent_label' => 'Saya setuju pola wajah saya disimpan untuk keperluan di atas.',
        'start' => 'Mulai pindai wajah',
        'success_title' => 'Pendaftaran berhasil',
        'success_text' => 'Simpan kode hapus ini. Kode hanya ditampilkan sekali dan tidak bisa dilihat lagi.',
        'copy' => 'Salin kode',
        'copied' => 'Kode sudah disalin.',
        'saved_label' => 'Saya sudah menyimpan kode ini',
        'done' => 'Selesai',
    ],

    'check' => [
        'title' => 'Cek wajah',
        'intro' => 'Pindai wajah Anda untuk mengetahui apakah wajah ini sudah terdaftar.',
        'start' => 'Mulai pindai wajah',
        'registered' => 'Wajah ini sudah terdaftar.',
        'not_registered' => 'Wajah ini belum terdaftar.',
        'go_delete' => 'Hapus data wajah',
        'go_enroll' => 'Daftarkan wajah',
    ],

    'delete' => [
        'title' => 'Hapus data wajah',
        'intro' => 'Masukkan kode hapus yang Anda simpan saat mendaftar.',
        'code_label' => 'Kode hapus',
        'next' => 'Lanjut',
        'confirm_text' => 'Data wajah akan dihapus permanen dan tidak bisa dikembalikan.',
        'confirm' => 'Hapus data wajah saya',
        'cancel' => 'Batal',
        'success' => 'Data wajah Anda sudah dihapus.',
        'back_home' => 'Kembali ke Beranda',
    ],

    'camera_explain' => 'Kami butuh akses kamera depan untuk memindai wajah. Browser akan meminta izin.',

    'js' => [
        'loading' => 'Menyiapkan pemindai wajah. Pertama kali bisa memakan waktu beberapa detik.',
        'camera_request' => 'Izinkan akses kamera di browser Anda.',
        'camera_denied' => 'Kamera tidak bisa diakses. Izinkan kamera di pengaturan browser, lalu muat ulang halaman.',
        'camera_not_found' => 'Kamera tidak ditemukan. Sambungkan kamera atau pakai perangkat lain.',
        'model_failed' => 'Pemindai wajah gagal dimuat. Periksa koneksi internet, lalu muat ulang halaman.',
        'position' => [
            'none' => 'Wajah tidak terlihat.',
            'multiple' => 'Hanya satu wajah yang boleh terlihat.',
            'far' => 'Wajah terlalu jauh, dekatkan.',
            'near' => 'Wajah terlalu dekat, jauhkan.',
            'off_center' => 'Posisikan wajah di dalam lingkaran.',
            'ok' => 'Tahan posisi sebentar.',
        ],
        'challenge' => [
            'blink' => 'Kedipkan mata perlahan.',
            'turn_right' => 'Toleh perlahan ke kanan, lalu kembali ke depan.',
            'turn_left' => 'Toleh perlahan ke kiri, lalu kembali ke depan.',
        ],
        'step' => 'Langkah :current dari :total',
        'hold_still' => 'Lihat ke depan dan tahan sebentar.',
        'processing' => 'Memeriksa…',
        'deleting' => 'Menghapus…',
        'retry' => 'Coba lagi',
        'restart' => 'Mulai lagi',
        'home' => 'Ke Beranda',
        'go_delete' => 'Hapus data wajah',
        'errors' => [
            'validation_failed' => 'Ada data yang tidak valid. Muat ulang halaman dan coba lagi.',
            'consent_required' => 'Anda perlu menyetujui penyimpanan pola wajah untuk melanjutkan.',
            'session_invalid' => 'Sesi pemindaian berakhir. Mulai lagi.',
            'liveness_failed' => 'Pemeriksaan gerakan belum berhasil. Coba lagi dan ikuti instruksi dengan perlahan.',
            'face_already_registered' => 'Wajah ini sudah terdaftar.',
            // Add "atau hapus lewat pindai wajah" once F-08 exists (T-032).
            'delete_code_invalid' => 'Kode tidak ditemukan. Periksa lagi huruf dan angkanya, lalu coba lagi.',
            'face_not_found' => 'Wajah ini tidak ditemukan di data kami.',
            'capacity_reached' => 'Pendaftaran sedang ditutup karena kuota demo penuh.',
            'rate_limited' => 'Terlalu banyak percobaan. Coba lagi dalam {n} detik.',
            'server_error' => 'Terjadi masalah di server. Coba lagi beberapa saat lagi.',
        ],
    ],
];
