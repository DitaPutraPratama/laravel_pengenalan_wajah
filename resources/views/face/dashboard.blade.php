@extends('face.layout')

@section('title', __('face.dashboard.title'))
@section('page', 'dashboard')

@section('content')
    <div class="flow">
        <div class="flow-header">
            <h1>{{ $account->name === null ? __('face.dashboard.title') : __('face.dashboard.greeting', ['name' => $account->name]) }}</h1>
            <p class="muted">{{ $account->name === null ? __('face.dashboard.name_first') : __('face.dashboard.intro') }}</p>
        </div>

        @include('face.partials.status')

        <div class="sections">
            @if ($account->name !== null)
                <section aria-labelledby="attendance-title">
                    <h2 id="attendance-title">{{ __('face.dashboard.today') }}</h2>
                    @if ($today)
                        <p class="result">{{ __('face.dashboard.attended_at', ['time' => $today->created_at->format('H.i')]) }}</p>
                    @else
                        {{-- Photo-only scan, checked 1:1 against this account (ADR-021). Driven by resources/js/face/main.js. --}}
                        <div class="stack" data-section="attend">
                            <p class="muted">{{ __('face.dashboard.attend_hint') }}</p>
                            <button type="button" class="button" data-start>{{ __('face.dashboard.attend') }}</button>
                        </div>
                        @include('face.partials.scanner')
                        @include('face.partials.alert')
                    @endif
                </section>

                <section aria-labelledby="history-title">
                    <h2 id="history-title">{{ __('face.dashboard.history') }}</h2>
                    @if ($history->isEmpty())
                        <p class="muted">{{ __('face.dashboard.history_empty') }}</p>
                    @else
                        <ul class="history">
                            @foreach ($history as $row)
                                <li><span>{{ $row->date->translatedFormat('l, j F Y') }}</span><span class="muted">{{ $row->created_at->format('H.i') }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            <form method="post" action="{{ route('face.name') }}" novalidate>
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="name">{{ __('face.dashboard.name_label') }}</label>
                    <input id="name" class="input" name="name" value="{{ old('name', $account->name) }}"
                        autocomplete="name" maxlength="100" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')
                        <p id="name-error" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="button {{ $account->name === null ? '' : 'outline' }}">{{ __('face.dashboard.save_name') }}</button>
            </form>

            {{-- Destructive actions: <details> is the confirmation step, so no script is needed. --}}
            <section aria-labelledby="danger-title">
                <h2 id="danger-title">{{ __('face.dashboard.danger') }}</h2>
                <details class="confirm">
                    <summary>{{ __('face.dashboard.clear_attendance') }}</summary>
                    <form method="post" action="{{ route('face.attendance.clear') }}">
                        @csrf
                        @method('DELETE')
                        <p>{{ __('face.dashboard.clear_attendance_text') }}</p>
                        <button type="submit" class="button destructive">{{ __('face.dashboard.clear_attendance_confirm') }}</button>
                    </form>
                </details>
                <details class="confirm">
                    <summary>{{ __('face.dashboard.delete_account') }}</summary>
                    <form method="post" action="{{ route('face.account.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <p>{{ __('face.dashboard.delete_account_text') }}</p>
                        <button type="submit" class="button destructive">{{ __('face.dashboard.delete_account_confirm') }}</button>
                    </form>
                </details>
            </section>
        </div>
    </div>
@endsection
