<?php

declare(strict_types=1);

/*
 * Face registry settings. Every value can be overridden from .env.
 * The four "required" keys have no default on purpose: /api/face/* answers
 * server_error until they are set (see EnsureFaceConfigured).
 */
return [
    // Required. Cosine similarity at or above this value counts as the same face.
    // Never sent to the browser.
    'match_threshold' => env('FACE_MATCH_THRESHOLD'),
    'embedding_dim' => env('FACE_EMBEDDING_DIM'),
    'model_version' => env('FACE_MODEL_VERSION'),
    'model_url' => env('FACE_MODEL_URL'),

    'consent_version' => env('FACE_CONSENT_VERSION', '1.0'),
    'max_templates' => (int) env('FACE_MAX_TEMPLATES', 2000),

    // Writes the best similarity of each match to the log (never the embedding).
    // Local calibration only: keep false anywhere real people enroll.
    'log_similarity' => (bool) env('FACE_LOG_SIMILARITY', false),

    'session' => [
        'ttl' => (int) env('FACE_SESSION_TTL', 120),
        'challenge_timeout' => (int) env('FACE_CHALLENGE_TIMEOUT', 15),
        // 1 in N session requests also deletes expired sessions.
        'cleanup_lottery' => (int) env('FACE_SESSION_CLEANUP_LOTTERY', 20),
    ],

    'liveness' => [
        'ear_closed' => (float) env('FACE_EAR_CLOSED', 0.2),
        'ear_open' => (float) env('FACE_EAR_OPEN', 0.25),
        'yaw_peak' => (float) env('FACE_YAW_PEAK', 20),
        'yaw_return' => (float) env('FACE_YAW_RETURN', 10),
        'min_duration_ms' => (int) env('FACE_MIN_DURATION_MS', 300),
        'max_duration_ms' => (int) env('FACE_MAX_DURATION_MS', 15000),
        'min_frames' => (int) env('FACE_MIN_FRAMES', 5),
        'clock_tolerance_ms' => (int) env('FACE_CLOCK_TOLERANCE_MS', 2000),
    ],

    // Browser-only guidance for the camera circle (F-02).
    'circle' => [
        'min_ratio' => (float) env('FACE_CIRCLE_MIN_RATIO', 0.4),
        'max_ratio' => (float) env('FACE_CIRCLE_MAX_RATIO', 0.7),
        'hold_ms' => (int) env('FACE_CIRCLE_HOLD_MS', 1000),
    ],

    // Browser-only (F-17): largest horizontal edge (0–255) allowed on the nose bridge before the
    // face counts as wearing glasses. Lower = stricter, 0 = off. [ASUMSI] calibrate on real devices.
    'glasses_max_edge' => (float) env('FACE_GLASSES_MAX_EDGE', 12),

    // Browser-only: frames in a row with EAR below ear_closed that count as closed eyes.
    'blink_closed_frames' => (int) env('FACE_BLINK_CLOSED_FRAMES', 2),

    // Requests per minute per IP (F-11).
    'rate_limits' => [
        'sessions' => (int) env('FACE_RATE_SESSIONS', 20),
        'enroll' => (int) env('FACE_RATE_ENROLL', 5),
        'login' => (int) env('FACE_RATE_LOGIN', 10),
    ],
];
