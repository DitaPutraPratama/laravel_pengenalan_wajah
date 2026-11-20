<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/face/main.js'])
</head>
<body>
    <header class="site-header">
        <a class="brand" href="{{ route('face.home') }}">{{ config('app.name') }}</a>
        <nav aria-label="Utama">
            <a href="{{ route('face.enroll') }}">{{ __('face.nav.enroll') }}</a>
            <a href="{{ route('face.check') }}">{{ __('face.nav.check') }}</a>
            <a href="{{ route('face.delete') }}">{{ __('face.nav.delete') }}</a>
        </nav>
    </header>

    {{-- Config for resources/js/face. The match threshold is never sent to the browser (ADR-011). --}}
    <main id="face-app" class="container"
        data-page="@yield('page')"
        data-model-url="{{ config('face.model_url') }}"
        data-embedding-dim="{{ config('face.embedding_dim') }}"
        data-consent-version="{{ config('face.consent_version') }}"
        data-challenge-timeout="{{ config('face.session.challenge_timeout') }}"
        data-ear-closed="{{ config('face.liveness.ear_closed') }}"
        data-ear-open="{{ config('face.liveness.ear_open') }}"
        data-blink-closed-frames="{{ config('face.blink_closed_frames') }}"
        data-yaw-peak="{{ config('face.liveness.yaw_peak') }}"
        data-yaw-return="{{ config('face.liveness.yaw_return') }}"
        data-min-duration-ms="{{ config('face.liveness.min_duration_ms') }}"
        data-min-frames="{{ config('face.liveness.min_frames') }}"
        data-circle-min-ratio="{{ config('face.circle.min_ratio') }}"
        data-circle-max-ratio="{{ config('face.circle.max_ratio') }}"
        data-circle-hold-ms="{{ config('face.circle.hold_ms') }}"
        data-messages="{{ json_encode(__('face.js')) }}">
        @yield('content')
    </main>

    <footer class="site-footer">
        <p>{{ __('face.footer.privacy') }}</p>
    </footer>
</body>
</html>
