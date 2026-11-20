<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · {{ config('app.name') }}</title>
    {{-- Apply a saved theme before first paint so the page does not flash. --}}
    <script nonce="{{ Vite::cspNonce() }}">try { const t = localStorage.getItem('theme'); if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t; } catch (e) {}</script>
    @vite(['resources/css/app.css', 'resources/js/face/main.js'])
</head>
<body>
    <header class="site-header">
        <div class="inner">
            <a class="brand" href="{{ route('face.login') }}">{{ config('app.name') }}</a>
            <nav class="nav" aria-label="{{ __('face.nav.label') }}">
                @auth
                    <a href="{{ route('face.dashboard') }}" @if (request()->routeIs('face.dashboard')) aria-current="page" @endif>{{ __('face.nav.dashboard') }}</a>
                    <form method="post" action="{{ route('face.logout') }}">
                        @csrf
                        <button type="submit">{{ __('face.nav.logout') }}</button>
                    </form>
                @else
                    @foreach (['login', 'enroll'] as $page)
                        <a href="{{ route("face.{$page}") }}" @if (request()->routeIs("face.{$page}")) aria-current="page" @endif>{{ __("face.nav.{$page}") }}</a>
                    @endforeach
                @endauth
            </nav>
            <button type="button" class="theme-toggle" data-theme-toggle aria-label="{{ __('face.theme.toggle') }}" title="{{ __('face.theme.toggle') }}">
                <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
                <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
        </div>
    </header>

    {{-- Config for resources/js/face. The match threshold is never sent to the browser (ADR-011). --}}
    <main id="face-app"
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
        data-glasses-max-edge="{{ config('face.glasses_max_edge') }}"
        data-messages="{{ json_encode(__('face.js')) }}">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="inner">
            <p>{{ __('face.footer.privacy') }}</p>
        </div>
    </footer>
</body>
</html>
