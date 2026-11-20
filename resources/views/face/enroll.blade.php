@extends('face.layout')

@section('title', __('face.enroll.title'))
@section('page', 'enroll')

@section('content')
    <h1>{{ __('face.enroll.title') }}</h1>

    <section data-section="consent">
        <p>{{ __('face.enroll.consent_text') }}</p>
        <p class="muted">{{ __('face.camera_explain') }}</p>
        <label class="check">
            <input type="checkbox" data-consent>
            <span>{{ __('face.enroll.consent_label') }}</span>
        </label>
        <button type="button" class="button" data-start disabled>{{ __('face.enroll.start') }}</button>
    </section>

    @include('face.partials.scanner')
    @include('face.partials.alert')

    <section data-section="success" hidden>
        <h2 tabindex="-1" data-success-title>{{ __('face.enroll.success_title') }}</h2>
        <p>{{ __('face.enroll.success_text') }}</p>
        <p class="code" data-code></p>
        <button type="button" class="button secondary" data-copy>{{ __('face.enroll.copy') }}</button>
        <p class="muted" data-copied data-text="{{ __('face.enroll.copied') }}" aria-live="polite"></p>
        <label class="check">
            <input type="checkbox" data-saved>
            <span>{{ __('face.enroll.saved_label') }}</span>
        </label>
        <a class="button" href="{{ route('face.home') }}" data-done aria-disabled="true">{{ __('face.enroll.done') }}</a>
    </section>
@endsection
