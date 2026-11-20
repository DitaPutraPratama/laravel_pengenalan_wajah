@extends('face.layout')

@section('title', __('face.enroll.title'))
@section('page', 'enroll')

@section('content')
    <div class="flow">
        <div class="flow-header">
            <h1>{{ __('face.enroll.title') }}</h1>
            <p class="muted">{{ __('face.enroll.description') }}</p>
        </div>

        <section data-section="consent">
            <p>{{ __('face.enroll.consent_text') }}</p>
            <label class="check">
                <input type="checkbox" data-consent>
                <span>{{ __('face.enroll.consent_label') }}</span>
            </label>
            <button type="button" class="button" data-start disabled>{{ __('face.enroll.start') }}</button>
        </section>

        @include('face.partials.scanner')
        @include('face.partials.alert')
    </div>
@endsection
