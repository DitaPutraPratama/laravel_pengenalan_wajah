@extends('face.layout')

@section('title', __('face.delete.title'))
@section('page', 'delete')

@section('content')
    <div class="flow">
        <div class="flow-header">
            <h1>{{ __('face.delete.title') }}</h1>
            <p class="muted">{{ __('face.delete.intro') }}</p>
        </div>

        {{-- The "Pindai wajah" tab (F-08) stays hidden until T-031/T-032. --}}
        <form data-section="code" novalidate>
            <div class="field">
                <label for="delete-code">{{ __('face.delete.code_label') }}</label>
                <input id="delete-code" class="input" name="delete_code" data-code-input
                    autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="64" required>
            </div>
            <button type="submit" class="button">{{ __('face.delete.next') }}</button>
        </form>

        <section data-section="confirm" hidden>
            <p>{{ __('face.delete.confirm_text') }}</p>
            <div class="stack">
                <button type="button" class="button destructive" data-confirm>{{ __('face.delete.confirm') }}</button>
                <button type="button" class="button outline" data-cancel>{{ __('face.delete.cancel') }}</button>
            </div>
        </section>

        @include('face.partials.alert')

        <section data-section="done" hidden>
            <p class="result">{{ __('face.delete.success') }}</p>
            <a class="button" href="{{ route('face.home') }}">{{ __('face.delete.back_home') }}</a>
        </section>
    </div>
@endsection
