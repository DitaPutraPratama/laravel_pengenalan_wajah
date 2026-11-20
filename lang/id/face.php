<?php

declare(strict_types=1);

// All UI text (docs/DESIGN.md). Keys under "js" are passed to the browser as JSON.
return [
    'nav' => [
        'label' => 'Menu utama',
        'login' => 'Masuk',
        'enroll' => 'Daftar',
        'dashboard' => 'Absen',
        'logout' => 'Keluar',
    ],

    'theme' => [
        // Toggle button with aria-pressed: pressed = dark theme on.
        'toggle' => 'Tema gelap',
    ],

    'footer' => [
        // TODO: link the final privacy policy after legal review (T-038).
        'privacy' => 'Tidak ada foto yang disimpan.',
    ],

    'login' => [
        'title' => 'Masuk dengan wajah',
        'intro' => 'Lepas kacamata, lalu ikuti tiga gerakan di depan kamera.',
        'start' => 'Pindai wajah',
        'no_account' => 'Belum punya akun?',
        'go_enroll' => 'Daftar dengan wajah',
    ],

    'enroll' => [
        'title' => 'Daftar dengan wajah',
        'description' => 'Wajah Anda menjadi akun. Lepas kacamata, lalu ikuti tiga gerakan di depan kamera.',
        // TODO: final consent text after legal review (UU PDP, T-038). Bump FACE_CONSENT_VERSION when it changes.
        'consent_text' => 'Pola wajah (angka, bukan foto) disimpan untuk masuk dan absen. Akun dan semua data bisa Anda hapus kapan saja.',
        'consent_label' => 'Saya setuju',
        'start' => 'Mulai pindai',
    ],

    'dashboard' => [
        'title' => 'Isi nama Anda',
        'greeting' => 'Halo, :name',
        'name_first' => 'Isi nama dulu sebelum absen.',
        'intro' => 'Absen sekali sehari, jam bebas.',
        'name_label' => 'Nama',
        'name_required' => 'Isi nama Anda.',
        'name_too_long' => 'Nama maksimal 100 karakter.',
        'save_name' => 'Simpan nama',
        'name_saved' => 'Nama tersimpan.',
        'today' => 'Hari ini',
        'attend' => 'Absen sekarang',
        'attend_hint' => 'Kamera akan mengambil foto wajah Anda untuk dicocokkan. Lepas kacamata.',
        'attended' => 'Absen tercatat.',
        'already_attended' => 'Anda sudah absen hari ini.',
        'attended_at' => 'Sudah absen pukul :time',
        'history' => 'Riwayat absen',
        'history_empty' => 'Belum ada absen.',
        'danger' => 'Hapus data',
        'clear_attendance' => 'Hapus semua riwayat absen',
        'clear_attendance_text' => 'Semua riwayat absen dihapus permanen. Akun tetap ada.',
        'clear_attendance_confirm' => 'Hapus riwayat permanen',
        'attendance_cleared' => 'Riwayat absen sudah dihapus.',
        'delete_account' => 'Hapus akun',
        'delete_account_text' => 'Pola wajah, nama, dan semua riwayat absen dihapus permanen. Anda bisa daftar lagi nanti.',
        'delete_account_confirm' => 'Hapus akun permanen',
        'account_deleted' => 'Akun dan semua data sudah dihapus.',
    ],

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
            'glasses' => 'Lepas kacamata',
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
        'retry' => 'Coba lagi',
        'restart' => 'Mulai lagi',
        'go_login' => 'Masuk',
        'go_enroll' => 'Daftar',
        'errors' => [
            'validation_failed' => 'Data tidak valid. Muat ulang halaman.',
            'consent_required' => 'Centang persetujuan dulu.',
            'session_invalid' => 'Sesi habis. Mulai lagi.',
            'liveness_failed' => 'Gerakan tidak terbaca. Coba lagi lebih pelan.',
            'glasses' => 'Kacamata terdeteksi. Lepas kacamata, lalu coba lagi.',
            'face_already_registered' => 'Wajah ini sudah terdaftar. Silakan masuk.',
            'face_not_found' => 'Wajah belum terdaftar. Daftar dulu, atau coba lagi dengan cahaya lebih terang.',
            'face_mismatch' => 'Wajah tidak cocok dengan akun ini. Coba lagi dengan cahaya lebih terang dan wajah menghadap kamera.',
            'capacity_reached' => 'Kuota demo penuh. Pendaftaran ditutup.',
            'rate_limited' => 'Terlalu banyak percobaan. Tunggu {n} detik.',
            'server_error' => 'Server bermasalah. Coba lagi nanti.',
        ],
    ],
];
