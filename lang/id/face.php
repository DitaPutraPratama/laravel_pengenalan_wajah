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
        'privacy' => 'Foto tidak disimpan. Yang disimpan hanya pola wajah dalam bentuk angka, dan bisa Anda hapus.',
    ],

    'home' => [
        'title' => 'Pendaftaran wajah',
        'intro' => 'Daftar, cek, atau hapus data wajah Anda.',
        'enroll' => 'Daftarkan wajah',
        'enroll_desc' => 'Satu wajah, satu pendaftaran. Anda akan dapat kode hapus.',
        'check' => 'Cek wajah',
        'check_desc' => 'Lihat apakah wajah Anda sudah terdaftar.',
        'delete' => 'Hapus data wajah',
        'delete_desc' => 'Pakai kode hapus dari saat Anda mendaftar.',
    ],

    'enroll' => [
        'title' => 'Daftarkan wajah',
        'description' => 'Setujui, lalu ikuti tiga gerakan di depan kamera.',
        // TODO: final consent text after legal review (UU PDP, T-038). Bump FACE_CONSENT_VERSION when it changes.
        'consent_text' => 'Kami menyimpan pola wajah Anda (angka, bukan foto) agar satu wajah tidak terdaftar dua kali. Anda bisa menghapusnya kapan saja dengan kode hapus.',
        'consent_label' => 'Saya setuju pola wajah saya disimpan.',
        'start' => 'Mulai pindai',
        'success_title' => 'Wajah terdaftar',
        'success_text' => 'Simpan kode hapus ini. Kode hanya muncul sekali.',
        'copy' => 'Salin kode',
        'copied' => 'Tersalin.',
        'saved_label' => 'Kode sudah saya simpan',
        'done' => 'Selesai',
    ],

    'check' => [
        'title' => 'Cek wajah',
        'intro' => 'Pindai wajah untuk melihat apakah sudah terdaftar.',
        'start' => 'Mulai pindai',
        'registered' => 'Wajah ini sudah terdaftar.',
        'not_registered' => 'Wajah ini belum terdaftar.',
        'go_delete' => 'Hapus data wajah',
        'go_enroll' => 'Daftarkan wajah',
    ],

    'delete' => [
        'title' => 'Hapus data wajah',
        'intro' => 'Masukkan kode hapus dari saat Anda mendaftar.',
        'code_label' => 'Kode hapus',
        'next' => 'Lanjut',
        'confirm_text' => 'Data wajah akan dihapus permanen.',
        'confirm' => 'Hapus permanen',
        'cancel' => 'Batal',
        'success' => 'Data wajah sudah dihapus.',
        'back_home' => 'Ke Beranda',
    ],

    'camera_explain' => 'Perlu izin kamera depan.',

    'js' => [
        'loading' => 'Memuat pemindai…',
        'camera_request' => 'Izinkan akses kamera.',
        'camera_denied' => 'Kamera diblokir. Izinkan di pengaturan browser, lalu muat ulang halaman.',
        'camera_not_found' => 'Kamera tidak ditemukan.',
        'model_failed' => 'Pemindai gagal dimuat. Cek koneksi internet, lalu muat ulang halaman.',
        'position' => [
            'none' => 'Wajah tidak terlihat',
            'multiple' => 'Hanya satu wajah',
            'far' => 'Terlalu jauh, dekatkan',
            'near' => 'Terlalu dekat, mundur sedikit',
            'off_center' => 'Masukkan wajah ke lingkaran',
            'ok' => 'Tahan sebentar',
        ],
        'challenge' => [
            'blink' => 'Kedipkan mata pelan',
            'turn_right' => 'Toleh ke kanan, lalu kembali',
            'turn_left' => 'Toleh ke kiri, lalu kembali',
        ],
        'step' => 'Langkah :current dari :total',
        'hold_still' => 'Lihat ke depan',
        'processing' => 'Memeriksa…',
        'deleting' => 'Menghapus…',
        'retry' => 'Coba lagi',
        'restart' => 'Mulai lagi',
        'home' => 'Ke Beranda',
        'go_delete' => 'Hapus data wajah',
        'errors' => [
            'validation_failed' => 'Data tidak valid. Muat ulang halaman.',
            'consent_required' => 'Centang persetujuan dulu.',
            'session_invalid' => 'Sesi habis. Mulai lagi.',
            'liveness_failed' => 'Gerakan tidak terbaca. Coba lagi lebih pelan.',
            'face_already_registered' => 'Wajah ini sudah terdaftar.',
            // Add "atau hapus lewat pindai wajah" once F-08 exists (T-032).
            'delete_code_invalid' => 'Kode tidak ditemukan. Cek lagi kodenya.',
            'face_not_found' => 'Wajah tidak ditemukan.',
            'capacity_reached' => 'Kuota demo penuh. Pendaftaran ditutup.',
            'rate_limited' => 'Terlalu banyak percobaan. Tunggu {n} detik.',
            'server_error' => 'Server bermasalah. Coba lagi nanti.',
        ],
    ],
];
