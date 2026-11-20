@extends('face.layout')

@section('title', __('face.home.title'))
@section('page', 'home')

@section('content')
    <h1>{{ __('face.home.title') }}</h1>
    <p>{{ __('face.home.intro') }}</p>
    <p>{{ __('face.home.how') }}</p>
    <div class="stack">
        <a class="button" href="{{ route('face.enroll') }}">{{ __('face.home.enroll') }}</a>
        <a class="button secondary" href="{{ route('face.check') }}">{{ __('face.home.check') }}</a>
        <a class="button secondary" href="{{ route('face.delete') }}">{{ __('face.home.delete') }}</a>
    </div>
@endsection
