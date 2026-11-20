@extends('face.layout')

@section('title', __('face.check.title'))
@section('page', 'check')

@section('content')
    <div class="flow">
        <div class="flow-header">
            <h1>{{ __('face.check.title') }}</h1>
            <p class="muted">{{ __('face.check.intro') }}</p>
        </div>

        <section data-section="intro">
            <button type="button" class="button" data-start>{{ __('face.check.start') }}</button>
        </section>

        @include('face.partials.scanner')
        @include('face.partials.alert')

        <section data-section="registered" hidden>
            <p class="result">{{ __('face.check.registered') }}</p>
            <a class="button outline" href="{{ route('face.delete') }}">{{ __('face.check.go_delete') }}</a>
        </section>

        <section data-section="not-registered" hidden>
            <p class="result">{{ __('face.check.not_registered') }}</p>
            <a class="button" href="{{ route('face.enroll') }}">{{ __('face.check.go_enroll') }}</a>
        </section>
    </div>
@endsection
