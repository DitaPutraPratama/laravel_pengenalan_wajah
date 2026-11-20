{{-- One-line result of the last form action (flashed "status"). --}}
@if (session('status'))
    <p class="notice" role="status">{{ session('status') }}</p>
@endif
