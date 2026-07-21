{{-- Shared success / error flash banners for admin pages --}}
@if (session('success'))
    <div class="alert-hm alert-hm--success">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert-hm alert-hm--error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
        {{ session('error') }}
    </div>
@endif
