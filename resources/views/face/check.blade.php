@extends('face.layout')

@section('title', __('face.check.title'))
@section('page', 'check')

@section('content')
    <h1>{{ __('face.check.title') }}</h1>

    <section data-section="intro">
        <p>{{ __('face.check.intro') }}</p>
        <p class="muted">{{ __('face.camera_explain') }}</p>
        <button type="button" class="button" data-start>{{ __('face.check.start') }}</button>
    </section>

    @include('face.partials.scanner')
    @include('face.partials.alert')

    <section data-section="registered" hidden>
        <p class="result">{{ __('face.check.registered') }}</p>
        <a class="button secondary" href="{{ route('face.delete') }}">{{ __('face.check.go_delete') }}</a>
    </section>

    <section data-section="not-registered" hidden>
        <p class="result">{{ __('face.check.not_registered') }}</p>
        <a class="button" href="{{ route('face.enroll') }}">{{ __('face.check.go_enroll') }}</a>
    </section>
@endsection
