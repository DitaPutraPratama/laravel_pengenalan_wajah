@extends('face.layout')

@section('title', __('face.home.title'))
@section('page', 'home')

@section('content')
    <div class="home">
        <div class="home-intro">
            <h1>{{ __('face.home.title') }}</h1>
            <p>{{ __('face.home.intro') }}</p>
        </div>

        {{-- Enrolling is the main task, so only its button is primary. --}}
        <div class="tasks">
            <section class="task">
                <h2>{{ __('face.home.enroll') }}</h2>
                <p class="muted">{{ __('face.home.enroll_desc') }}</p>
                <a class="button" href="{{ route('face.enroll') }}">{{ __('face.home.enroll') }}</a>
            </section>
            <section class="task">
                <h2>{{ __('face.home.check') }}</h2>
                <p class="muted">{{ __('face.home.check_desc') }}</p>
                <a class="button outline" href="{{ route('face.check') }}">{{ __('face.home.check') }}</a>
            </section>
            <section class="task">
                <h2>{{ __('face.home.delete') }}</h2>
                <p class="muted">{{ __('face.home.delete_desc') }}</p>
                <a class="button outline" href="{{ route('face.delete') }}">{{ __('face.home.delete') }}</a>
            </section>
        </div>
    </div>
@endsection
