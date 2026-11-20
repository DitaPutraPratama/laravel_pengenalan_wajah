@extends('face.layout')

@section('title', __('face.login.title'))
@section('page', 'login')

@section('content')
    <div class="flow">
        <div class="flow-header">
            <h1>{{ __('face.login.title') }}</h1>
            <p class="muted">{{ __('face.login.intro') }}</p>
        </div>

        @include('face.partials.status')

        <section data-section="intro">
            <button type="button" class="button" data-start>{{ __('face.login.start') }}</button>
            <p class="muted">{{ __('face.login.no_account') }} <a href="{{ route('face.enroll') }}">{{ __('face.login.go_enroll') }}</a></p>
        </section>

        @include('face.partials.scanner')
        @include('face.partials.alert')
    </div>
@endsection
